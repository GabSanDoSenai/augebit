<?php
session_start();

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'funcionario') {
    header("Location: ../login.php");
    exit();
}

include '../conexao.php';

$funcionario_id = $_SESSION['usuario_id'];
$funcionario_nome = $_SESSION['usuario_nome'];

// Processar upload de arquivo
if (isset($_POST['upload_arquivo'])) {
    $projeto_id = $conn->real_escape_string($_POST['projeto_id']);
    $upload_error = "";
    $upload_success = "";
    
    if (empty($projeto_id)) {
        $upload_error = "Por favor, selecione um projeto.";
    } elseif (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== 0) {
        $upload_error = "Por favor, selecione um arquivo válido.";
    } else {
        $arquivo = $_FILES['arquivo'];
        $nome_arquivo = basename($arquivo['name']);
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        
        // Validar extensão
        $extensoes_permitidas = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'];
        if (!in_array($extensao, $extensoes_permitidas)) {
            $upload_error = "Tipo de arquivo não permitido.";
        } else {
            // Gerar nome único para o arquivo
            $nome_unico = uniqid() . '_' . $nome_arquivo;
            $caminho_arquivo = '../uploads/' . $nome_unico;
            
            // Criar diretório se não existir
            if (!file_exists('../uploads/')) {
                mkdir('../uploads/', 0777, true);
            }
            
            if (move_uploaded_file($arquivo['tmp_name'], $caminho_arquivo)) {
                // Salvar no banco de dados
                $sql = "INSERT INTO uploads (projeto_id, nome_arquivo, caminho_arquivo, tipo, enviado_por, data_upload) VALUES (?, ?, ?, 'funcionario', ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issi", $projeto_id, $nome_arquivo, $caminho_arquivo, $funcionario_id);
                
                if ($stmt->execute()) {
                    $upload_success = "Arquivo enviado com sucesso!";
                } else {
                    $upload_error = "Erro ao salvar arquivo no banco de dados.";
                    unlink($caminho_arquivo); // Remove arquivo se falhou no banco
                }
                $stmt->close();
            } else {
                $upload_error = "Erro ao fazer upload do arquivo.";
            }
        }
    }
}

// Buscar tarefas do funcionário
$sql_tarefas = "SELECT t.id, t.titulo, t.descricao, t.status, t.prioridade, p.titulo as projeto_nome 
                FROM tarefas t 
                INNER JOIN projetos p ON t.projeto_id = p.id 
                WHERE t.funcionario_id = ? 
                ORDER BY t.prioridade DESC, t.id DESC";
$stmt_tarefas = $conn->prepare($sql_tarefas);
$stmt_tarefas->bind_param("i", $funcionario_id);
$stmt_tarefas->execute();
$result_tarefas = $stmt_tarefas->get_result();

// Buscar documentos dos projetos que participa
$sql_documentos = "SELECT DISTINCT u.id, u.nome_arquivo, u.caminho_arquivo, u.data_upload, p.titulo as projeto_nome 
                   FROM uploads u 
                   INNER JOIN projetos p ON u.projeto_id = p.id 
                   INNER JOIN tarefas t ON t.projeto_id = p.id 
                   WHERE t.funcionario_id = ? AND u.tipo IN ('funcionario', 'cliente', 'gestor')
                   ORDER BY u.data_upload DESC";
$stmt_documentos = $conn->prepare($sql_documentos);
$stmt_documentos->bind_param("i", $funcionario_id);
$stmt_documentos->execute();
$result_documentos = $stmt_documentos->get_result();

// Buscar projetos onde o funcionário participa (para o formulário de upload)
$sql_projetos = "SELECT DISTINCT p.id, p.titulo 
                 FROM projetos p 
                 INNER JOIN tarefas t ON t.projeto_id = p.id 
                 WHERE t.funcionario_id = ? 
                 ORDER BY p.titulo";
$stmt_projetos = $conn->prepare($sql_projetos);
$stmt_projetos->bind_param("i", $funcionario_id);
$stmt_projetos->execute();
$result_projetos = $stmt_projetos->get_result();

// Função para definir cor do status
function getStatusColor($status) {
    switch($status) {
        case 'a_fazer': return '#ffc107';
        case 'em_progresso': return '#007bff';
        case 'concluido': return '#28a745';
        default: return '#6c757d';
    }
}

// Função para definir cor da prioridade
function getPriorityColor($prioridade) {
    switch($prioridade) {
        case 'alta': return '#dc3545';
        case 'media': return '#fd7e14';
        case 'baixa': return '#28a745';
        default: return '#6c757d';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Funcionário</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .header {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background-color: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }

        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }

        .update-form {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .update-form select {
            padding: 0.25rem 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
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

        .btn-download {
            background-color: #17a2b8;
            color: white;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .btn-download:hover {
            background-color: #138496;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }

        .upload-form {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .container {
                padding: 0 0.5rem;
            }

            .section {
                padding: 1rem;
            }

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard - Funcionário</h1>
        <div class="user-info">
            <span>Bem-vindo, <?php echo htmlspecialchars($funcionario_nome); ?>!</span>
            <a href="../logout.php" class="logout-btn">Sair</a>
        </div>
    </div>

    <div class="container">
        <!-- Mensagens de Sistema -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Seção de Tarefas -->
        <div class="section">
            <h2>Minhas Tarefas</h2>
            
            <?php if ($result_tarefas->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Projeto</th>
                            <th>Status</th>
                            <th>Prioridade</th>
                            <th>Atualizar Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tarefa = $result_tarefas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tarefa['titulo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($tarefa['descricao']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['projeto_nome']); ?></td>
                                <td>
                                    <span class="status-badge" style="background-color: <?php echo getStatusColor($tarefa['status']); ?>">
                                        <?php 
                                        switch($tarefa['status']) {
                                            case 'a_fazer': echo 'Á_Fazer'; break;
                                            case 'em_progresso': echo 'Em_Progresso'; break;
                                            case 'concluido': echo 'Concluído'; break;
                                            default: echo ucfirst($tarefa['status']);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge" style="background-color: <?php echo getPriorityColor($tarefa['prioridade']); ?>">
                                        <?php echo ucfirst($tarefa['prioridade']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="atualizar_tarefa.php" class="update-form">
                                        <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                        <select name="novo_status" required>
                                            <option value="">Selecione...</option>
                                            <option value="a_fazer" <?php echo ($tarefa['status'] == 'a_fazer') ? 'selected' : ''; ?>>A Fazer</option>
                                            <option value="em_progresso" <?php echo ($tarefa['status'] == 'em_progresso') ? 'selected' : ''; ?>>Em Progresso</option>
                                            <option value="concluido" <?php echo ($tarefa['status'] == 'concluido') ? 'selected' : ''; ?>>Concluído</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary">Atualizar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    Nenhuma tarefa encontrada.
                </div>
            <?php endif; ?>
        </div>

        <!-- Seção de Documentos -->
        <div class="section">
            <h2>Documentos dos Projetos</h2>
            
            <?php if ($result_documentos->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Arquivo</th>
                            <th>Projeto</th>
                            <th>Data de Upload</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($documento = $result_documentos->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($documento['nome_arquivo']); ?></td>
                                <td><?php echo htmlspecialchars($documento['projeto_nome']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($documento['data_upload'])); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($documento['caminho_arquivo']); ?>" 
                                       class="btn btn-download" 
                                       download="<?php echo htmlspecialchars($documento['nome_arquivo']); ?>"
                                       target="_blank">
                                        Download
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    Nenhum documento encontrado.
                </div>
            <?php endif; ?>
        </div>

        <!-- Seção de Upload de Arquivos -->
        <div class="section">
            <h2>Enviar Arquivo</h2>
            
            <?php if (isset($upload_error) && !empty($upload_error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($upload_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($upload_success) && !empty($upload_success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($upload_success); ?>
                </div>
            <?php endif; ?>

            <div class="upload-form">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="projeto_id">Projeto</label>
                        <select name="projeto_id" id="projeto_id" required>
                            <option value="">Selecione um projeto...</option>
                            <?php 
                            $result_projetos->data_seek(0); // Reset result pointer
                            while ($projeto = $result_projetos->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $projeto['id']; ?>">
                                    <?php echo htmlspecialchars($projeto['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="arquivo">Arquivo</label>
                        <input type="file" name="arquivo" id="arquivo" required 
                               accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar">
                        <small style="color: #6c757d; font-size: 0.875rem;">
                            Formatos permitidos: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, GIF, ZIP, RAR
                        </small>
                    </div>
                    
                    <button type="submit" name="upload_arquivo" class="btn btn-success">
                        Enviar Arquivo
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php
    $stmt_tarefas->close();
    $stmt_documentos->close();
    $stmt_projetos->close();
    $conn->close();
    ?>
</body>
</html>