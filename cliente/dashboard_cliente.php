<?php
session_start();

// Verificar se o usuário está logado e é do tipo cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../login.php");
    exit();
}

include '../conexao.php';

$cliente_id = $_SESSION['usuario_id'];
$nome_cliente = $_SESSION['usuario_nome'];

// Processar upload de arquivo
if (isset($_POST['enviar_arquivo'])) {
    $projeto_id = (int)$_POST['projeto_id'];
    $mensagem_sucesso = "";
    $mensagem_erro = "";
    
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
        $nome_original = $_FILES['arquivo']['name'];
        $nome_unico = time() . '_' . $nome_original;
        $caminho_destino = '../uploads/' . $nome_unico;
        
        // Verificar se a pasta uploads existe
        if (!is_dir('../uploads')) {
            mkdir('../uploads', 0755, true);
        }
        
        if (move_uploaded_file($arquivo_tmp, $caminho_destino)) {
            // Salvar no banco de dados
            $sql = "INSERT INTO uploads (projeto_id, nome_arquivo, caminho_arquivo, tipo, enviado_por) VALUES (?, ?, ?, 'cliente', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issi", $projeto_id, $nome_original, $caminho_destino, $cliente_id);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Arquivo enviado com sucesso!";
            } else {
                $mensagem_erro = "Erro ao salvar arquivo no banco de dados.";
            }
            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao fazer upload do arquivo.";
        }
    } else {
        $mensagem_erro = "Por favor, selecione um arquivo válido.";
    }
}

// Processar envio de mensagem de chat
if (isset($_POST['enviar_mensagem'])) {
    $projeto_id = (int)$_POST['projeto_mensagem_id'];
    $mensagem = trim($_POST['mensagem']);
    
    if (!empty($mensagem)) {
        // Buscar o ID do gestor/admin (assumindo que o primeiro admin/gestor é o destinatário)
        $sql_gestor = "SELECT id FROM usuarios WHERE tipo IN ('admin', 'gestor') LIMIT 1";
        $resultado_gestor = $conn->query($sql_gestor);
        
        if ($resultado_gestor->num_rows > 0) {
            $gestor = $resultado_gestor->fetch_assoc();
            $destinatario_id = $gestor['id'];
            
            $sql = "INSERT INTO mensagens (remetente_id, destinatario_id, projeto_id, mensagem, origem) VALUES (?, ?, ?, ?, 'usuario')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $cliente_id, $destinatario_id, $projeto_id, $mensagem);
            
            if ($stmt->execute()) {
                $mensagem_chat_sucesso = "Mensagem enviada com sucesso!";
            } else {
                $mensagem_chat_erro = "Erro ao enviar mensagem.";
            }
            $stmt->close();
        }
    }
}

// Buscar projetos do cliente
$sql = "SELECT id, titulo, status, criado_em FROM projetos WHERE cliente_id = ? ORDER BY criado_em DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$projetos = $stmt->get_result();
$stmt->close();

// Buscar uploads do cliente
$sql = "SELECT u.*, p.titulo as projeto_titulo 
        FROM uploads u 
        INNER JOIN projetos p ON u.projeto_id = p.id 
        WHERE u.enviado_por = ? AND u.tipo = 'cliente' 
        ORDER BY u.enviado_em DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$uploads = $stmt->get_result();
$stmt->close();

// Buscar projetos para o formulário de upload
$sql = "SELECT id, titulo FROM projetos WHERE cliente_id = ? ORDER BY titulo";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$projetos_upload = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - AugeBit</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .logout-btn {
            float: right;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .section {
            background: white;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .section-header h2 {
            color: #495057;
            font-size: 1.5em;
        }

        .section-content {
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status.em_andamento {
            background-color: #fff3cd;
            color: #856404;
        }

        .status.aprovado {
            background-color: #d4edda;
            color: #155724;
        }

        .status.finalizado {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status.ajustes {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 2px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #1e7e34;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #117a8b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .upload-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .chat-form {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 40px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .logout-btn {
                float: none;
                display: block;
                margin-top: 10px;
                text-align: center;
            }
            
            table {
                font-size: 0.9em;
            }
            
            .btn {
                display: block;
                margin: 5px 0;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <a href="../logout.php" class="logout-btn">Sair</a>
            <h1>Dashboard Cliente</h1>
            <p>Bem-vindo, <?php echo htmlspecialchars($nome_cliente); ?>!</p>
        </div>
        <!-- Seção de Projetos -->
         <a href="solicitar_projeto.php">soliticar projeto</a>
        <div class="section">
            <div class="section-header">
                <h2>Meus Projetos</h2>
            </div>
            <div class="section-content">
                <?php if ($projetos->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Status</th>
                                <th>Data de Criação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($projeto = $projetos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($projeto['titulo']); ?></td>
                                    <td>
                                        <span class="status <?php echo $projeto['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $projeto['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($projeto['criado_em'])); ?></td>
                                    <td>
                                        <a href="#" class="btn btn-primary" onclick="alert('Funcionalidade em desenvolvimento')">Ver Tarefas</a>
                                        <a href="#" class="btn btn-info" onclick="alert('Funcionalidade em desenvolvimento')">Ver Documentos</a>
                                        <?php if ($projeto['status'] === 'finalizado'): ?>
                                            <a href="#" class="btn btn-success" onclick="alert('Funcionalidade em desenvolvimento')">Ver Entrega Final</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>Você ainda não possui projetos cadastrados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Seção de Arquivos Enviados -->
        <div class="section">
            <div class="section-header">
                <h2>Meus Arquivos Enviados</h2>
            </div>
            <div class="section-content">
                <?php if ($uploads->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome do Arquivo</th>
                                <th>Projeto</th>
                                <th>Data de Envio</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($upload = $uploads->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($upload['nome_arquivo']); ?></td>
                                    <td><?php echo htmlspecialchars($upload['projeto_titulo']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($upload['enviado_em'])); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($upload['caminho_arquivo']); ?>" 
                                           class="btn btn-info" target="_blank">Visualizar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>Você ainda não enviou nenhum arquivo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulário de Upload -->
        <div class="section">
            <div class="section-header">
                <h2>Enviar Novo Arquivo</h2>
            </div>
            <div class="section-content">
                <?php if (isset($mensagem_sucesso)): ?>
                    <div class="alert alert-success"><?php echo $mensagem_sucesso; ?></div>
                <?php endif; ?>
                
                <?php if (isset($mensagem_erro)): ?>
                    <div class="alert alert-danger"><?php echo $mensagem_erro; ?></div>
                <?php endif; ?>

                <div class="upload-form">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="projeto_id">Selecione o Projeto:</label>
                            <select name="projeto_id" id="projeto_id" class="form-control" required>
                                <option value="">-- Selecione um projeto --</option>
                                <?php 
                                $projetos_upload->data_seek(0);
                                while ($projeto = $projetos_upload->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $projeto['id']; ?>">
                                        <?php echo htmlspecialchars($projeto['titulo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="arquivo">Selecione o Arquivo:</label>
                            <input type="file" name="arquivo" id="arquivo" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="enviar_arquivo" class="btn btn-success">Enviar Arquivo</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Formulário de Chat/Mensagem -->
        <div class="section">
            <div class="section-header">
                <h2>Enviar Mensagem para o Gestor</h2>
            </div>
            <div class="section-content">
                <?php if (isset($mensagem_chat_sucesso)): ?>
                    <div class="alert alert-success"><?php echo $mensagem_chat_sucesso; ?></div>
                <?php endif; ?>
                
                <?php if (isset($mensagem_chat_erro)): ?>
                    <div class="alert alert-danger"><?php echo $mensagem_chat_erro; ?></div>
                <?php endif; ?>

                <div class="chat-form">
                    <form method="POST">
                        <div class="form-group">
                            <label for="projeto_mensagem_id">Projeto Relacionado:</label>
                            <select name="projeto_mensagem_id" id="projeto_mensagem_id" class="form-control" required>
                                <option value="">-- Selecione um projeto --</option>
                                <?php 
                                $projetos_upload->data_seek(0);
                                while ($projeto = $projetos_upload->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $projeto['id']; ?>">
                                        <?php echo htmlspecialchars($projeto['titulo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="mensagem">Sua Mensagem:</label>
                            <textarea name="mensagem" id="mensagem" class="form-control" 
                                      placeholder="Digite sua mensagem aqui..." required></textarea>
                        </div>
                        
                        <button type="submit" name="enviar_mensagem" class="btn btn-primary">Enviar Mensagem</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>