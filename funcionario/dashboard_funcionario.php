<?php
session_start();

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'funcionario') {
    header("Location: ../login.php");
    exit();
}

include '../conexao.php';

// Verificar se as variáveis de sessão existem antes de usá-las
$funcionario_id = $_SESSION['usuario_id'] ?? null;
$funcionario_nome = $_SESSION['usuario_nome'] ?? 'Funcionário';

// Inicializar variáveis para evitar warnings
$result_tarefas = null;
$result_documentos = null;
$result_projetos = null;
$upload_error = "";
$upload_success = "";

// Processar upload de arquivo
if (isset($_POST['upload_arquivo'])) {
    $projeto_id = $conn->real_escape_string($_POST['projeto_id'] ?? '');
    
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
if ($funcionario_id) {
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
    WHERE t.funcionario_id = ?
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
}

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #9999FF;
            --secondary-color: #f8f9fa;
            --accent-color: #6c5ce7;
            --text-color: #2d3436;
            --light-text: #636e72;
            --success-color: #00b894;
            --warning-color: #fdcb6e;
            --error-color: #d63031;
            --info-color: #0984e3;
            --border-radius: 12px;
            --box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

     * {
    box-sizing: border-box;
    margin: 0;
    overflow-x: hidden;
    padding: 0;
}

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }
        
          /* HEADER CORRETO */
        header {
            
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between; /* Isso alinha logo à esquerda e menu à direita */
            align-items: center;
           
        }

        .logo-header {
            display: flex;
            align-items: center;
        }

        .logo-header img {
            width: 70px;
            height: 80px;
            margin-right: 15px;
        }

        .logo-header h1 {
            font-size: 30px;
            font-weight: 300;
            color: var(--dark-purple);
        }

        /* MENU ALINHADO À DIREITA */
        nav ul {
            display: flex;
            gap: 50px;
            list-style: none;
        }

        nav a {
            color: var(--dark-purple);
            text-decoration: none;
            font-size: 19px;
            font-weight: 450;
            transition: all 0.3s ease;
        }

        nav a:hover {
            color: var(--primary-color);
        }

        /* RESPONSIVIDADE */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                padding: 1rem;
            }
            
            .logo-header {
                margin-bottom: 1rem;
            }
            
            nav ul {
                gap: 20px;
            }
        }
   


        .active {
            color: #3E236A;
        }

        .acount {
            color: #3E236A;
        }

        .contato {
            color: #3E236A;
        }
       
        section {
            padding: 30px;
        }
        
        section:last-child {
            border-bottom: none;
        }
.header{
margin-left:530px;
}
        /* TÍTULO DO DASHBOARD */
        .dashboard-title {
            text-align: center;
            margin-left:30px;
            color: #45266B;
        }

        .dashboard-title h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
             color: #45266B;
        }

        .dashboard-title p {
            font-size: 1.2rem;
             color: #45266B;
        }
.header{
margin-top: 90px; 
}
       .header h1{
        color: #45266B;
       }
        .header span{
        color: #45266B;
       margin-left:120px;
       }
        .container {
            max-width: 1400px;
            margin: 0 auto 2.5rem;
            padding: 0 1.5rem;
        }

        .section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }


        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.6rem 1.2rem;
            text-decoration: none;
            border-radius: 50px;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        .section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .section:hover {
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: var(--primary-color);
            margin-bottom: 1.8rem;
            font-weight: 600;
            font-size: 1.5rem;
            position: relative;
            padding-bottom: 0.8rem;
        }

        .section h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .alert {
            padding: 1.2rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-success {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
            overflow: hidden;
        }

        th, td {
            padding: 1.2rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        th {
            background-color: var(--secondary-color);
            font-weight: 600;
            color: var(--primary-color);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: rgba(74, 107, 255, 0.03);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge {
            color: white;
        }

        .priority-badge {
            color: white;
        }

        .update-form {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .update-form select {
            padding: 0.6rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            min-width: 150px;
            transition: var(--transition);
            background-color: var(--secondary-color);
        }

        .update-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-primary {
            background-color:rgb(69, 38, 107);
            color: white;
        }

        .btn-primary:hover {
            background-color:rgb(69, 38, 107);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 107, 255, 0.2);
        }

        .btn-success {
            background-color: rgb(69, 38, 107);
            color: white;
        }

        .btn-success:hover {
            background-color:#9999FF;
            transform: translateY(-2px);
        }

        .btn-download {
            background-color: var(--info-color);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-download:hover {
            background-color: #9999FF;
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--secondary-color);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }

        .upload-form {
            background-color: var(--secondary-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            border: 1px dashed rgba(0, 0, 0, 0.1);
        }

        .no-data {
            text-align: center;
            color: var(--light-text);
            padding: 3rem;
            font-size: 1.1rem;
        }

        .no-data i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--light-text);
            opacity: 0.5;
        }

        /* Status colors */
        .status-a_fazer {
            background-color: var(--warning-color);
        }

        .status-em_progresso {
            background-color: var(--info-color);
        }

        .status-concluido {
            background-color: var(--success-color);
        }

        /* Priority colors */
        .priority-alta {
            background-color: var(--error-color);
        }

        .priority-media {
            background-color: var(--warning-color);
        }

        .priority-baixa {
            background-color: var(--success-color);
        }

        @media (max-width: 992px) {
            .container {
                padding: 0 1rem;
            }
            
            .section {
                padding: 1.5rem;
            }
            
            th, td {
                padding: 1rem 0.8rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            .update-form {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }
            
            .update-form select {
                width: 100%;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .container {
                margin: 1.5rem auto;
            }
            
            .section {
                padding: 1.2rem;
            }
            
            .upload-form {
                padding: 1.5rem;
            }
        }
        .rodape {
  background-color: #9999FF;
  width: 100vw; /* Usa a largura total da viewport */
  min-height: 300px;
  padding: 60px 15px;
  color: #ffffff;
  display: flex;
  justify-content: space-around;
  align-items: center;
  flex-wrap: wrap;
  gap: 40px;
  position: relative;
  margin-top: 80px;
  left: 0;
  box-sizing: border-box;
}


.logo-rodape {
  text-align: center;
  display: flex;
  padding-left: 50px;
  flex-direction: column;
  align-items: center;
}

.logo-rodape img {
  width: 100px;
  margin-left: 20px;
  margin-bottom: 15px;
}

.logo-rodape h1 {
  font-size: 1.8rem;
  font-family: 'Poppins';
  margin-bottom: 5px;
  color: #ffffff;
  font-weight: 500;
  margin-left: 25px;
}

.logo-rodape p {
  font-size: 0.9rem;
  margin-left: 25px;
  font-family: 'Poppins';
   font-weight: 300;
}

.pages {
  margin-left: -110px;
}

.pages p {
  margin-bottom: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  font-size: 0.9rem;
  font-family: 'Poppins';
  font-weight: 400;
}

.pages p::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 1px;
  background: #ffffff;
  transition: all 0.3s ease;
}

.pages p:hover::after {
  width: 100%;
}

.redescociais {
  display: flex;
  gap: 20px;
  align-items: center;
}

.redescociais img {
  width: 25px;
  height: 25px;
  filter: brightness(0) invert(1);
  transition: all 0.3s ease;
  cursor: pointer;
}

.redescociais img:hover {
  transform: scale(1.2);
}
    </style>
   
</head>
<body>
   <header>
        <div class="logo-header">    
            <img src="../assets/img/augebit.logo.png" alt="Logo da empresa"> 
           
        </div>    
        <nav>       
            <ul>         
                <li><a href="../index.php" class="active">Home</a></li>         
                <li><a href="#projects-section" class="acount">Projetos</a></li>         
                <li><a href="../logout.php" class="contato">Sair</a></li>    
            </ul>     
        </nav>   
    </header>
    <div class="header">
        <h1>Dashboard - Funcionário</h1>
        <div class="user-info">
            <span>Bem-vindo, <?php echo htmlspecialchars($funcionario_nome); ?>!</span>
            <a href="../logout.php" class="logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                </svg>
                Sair
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Mensagens de Sistema -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Seção de Tarefas -->
        <div class="section">
            <h2>Minhas Tarefas</h2>
            
            <?php if ($result_tarefas && $result_tarefas->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Projeto</th>
                            <th>Status</th>
                            <th>Prioridade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tarefa = $result_tarefas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tarefa['titulo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($tarefa['descricao']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['projeto_nome']); ?></td>
                                <td>
                                    <span class="badge status-<?php echo $tarefa['status']; ?>">
                                        <?php 
                                        switch($tarefa['status']) {
                                            case 'a_fazer': echo 'A Fazer'; break;
                                            case 'em_progresso': echo 'Em Progresso'; break;
                                            case 'concluido': echo 'Concluído'; break;
                                            default: echo ucfirst($tarefa['status']);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge priority-<?php echo $tarefa['prioridade']; ?>">
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
                                        <button type="submit" class="btn btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                            </svg>
                                            Atualizar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                    <p>Nenhuma tarefa encontrada.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Seção de Documentos -->
        <div class="section">
            <h2>Documentos dos Projetos</h2>
            
            <?php if ($result_documentos && $result_documentos->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Arquivo</th>
                            <th>Projeto</th>
                            <th>Data de Upload</th>
                            <th>Ação</th>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                        </svg>
                                        Download
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
                        <path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8zm0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <p>Nenhum documento encontrado.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Seção de Upload de Arquivos -->
        <div class="section">
            <h2>Enviar Arquivo</h2>
            
            <?php if (!empty($upload_error)): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    <?php echo htmlspecialchars($upload_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($upload_success)): ?>
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
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
                            if ($result_projetos) {
                                $result_projetos->data_seek(0);
                                while ($projeto = $result_projetos->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $projeto['id']; ?>">
                                    <?php echo htmlspecialchars($projeto['titulo']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="arquivo">Arquivo</label>
                        <input type="file" name="arquivo" id="arquivo" required 
                               accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar">
                        <small style="color: var(--light-text); font-size: 0.875rem;">
                            Formatos permitidos: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, GIF, ZIP, RAR
                        </small>
                    </div>
                    
                    <button type="submit" name="upload_arquivo" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                        </svg>
                        Enviar Arquivo
                    </button>
                </form>
            </div>
        </div>
    </div>
<footer class="rodape">
    <div class="pages">
      <p>Home</p>
      <p>Quem Somos</p>
      <p>Nossos serviços</p>
      <p>Entre em contato</p>
    </div>
    <div class="logo-rodape">
      <img src="../assets/img/logobranca.png" alt="Logo Augebit">
      <h1>AUGEBIT</h1>
      <p>Industrial design</p>
    </div>
    <div class="redescociais">
      <img src="../assets/img/emailbranco.png" alt="Email">
      <img src="../assets/img/instabranco.png" alt="Instagram">
      <img src="../assets/img/linkedinbranco.png" alt="Linkedin">
      <img src="../assets/img/zapbranco.png" alt="Whatsapp">
    </div>
  </footer>
    <?php
    // Fechar conexões se existirem
    if (isset($stmt_tarefas)) $stmt_tarefas->close();
    if (isset($stmt_documentos)) $stmt_documentos->close();
    if (isset($stmt_projetos)) $stmt_projetos->close();
    if (isset($conn)) $conn->close();
    ?>
</body>
</html>