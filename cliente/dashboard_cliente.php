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
            // Salvar no banco de dados - removido o campo 'tipo' e 'enviado_por'
            $sql = "INSERT INTO uploads (projeto_id, nome_arquivo, caminho_arquivo) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $projeto_id, $nome_original, $caminho_destino);
            
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
        // Buscar o ID do gestor/admin (assumindo que o primeiro admin/funcionario é o destinatário)
        $sql_gestor = "SELECT id FROM usuarios WHERE tipo IN ('admin', 'funcionario') LIMIT 1";
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

// Buscar uploads do cliente - corrigida a consulta SQL
$sql = "SELECT u.*, p.titulo as projeto_titulo 
FROM uploads u 
INNER JOIN projetos p ON u.projeto_id = p.id 
WHERE p.cliente_id = ? 
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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - AugeBit</title>
    <style>
        /* RESET & BASE STYLES */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f8f9fa;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-SemiBold.ttf') format('truetype');
            font-weight: 600;
        }
           
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-Regular.ttf') format('truetype');
            font-weight: 450;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-Medium.ttf') format('truetype');
            font-weight: 500;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-Italic.ttf') format('truetype');
            font-weight: 400;
            font-style: italic;
        }
        
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-ExtraLight.ttf') format('truetype');
            font-weight: 200;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            position: relative;
            z-index: 10;
            padding: 10px 0;
        }

        .logo-header {
            display: flex;
            align-items: center;
        }

        .logo-header img {
            position: relative;
            width: 70px;
            height: 80px;
            top: -16px; 
            left:30px
        }

        .logo-header h1 {
            position:relative; 
            font-size: 30px;
            font-family: 'Poppins';
            font-weight: 300;
            left: 30px;
        }

        nav ul {
            padding-right: 100px;
            display: flex;
            gap: 80px;
            list-style: none;
        }

        nav a {
            color: #3E236A;
            text-decoration: none;
            font-size: 19px;
            font-family: 'Poppins';
            transition: all 0.3s ease;
            font-weight: 450;
        }

        nav a:hover {
            color: #9999FF;
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
        
        .greeting {
            position: relative;
            height: 300px;
            display: flex;
            left: 500px;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding-top: 60px;
        }
p{
    font-family:'Poppins';
    font-weight: 200; 
}
        .new-project-btn {
            height: 30px;
            width: 250px;
            padding: 20px;
            font-family: 'Poppins';
            font-weight: 450;
            font-size: 15px;
            color: white;
            border-radius: 30px;
            background-color: #9999FF;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 58px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .new-project-btn:hover {
            background-color: #3E236A;
        }
        
        .greeting h1 {
            font-size: 40px;
            font-weight: 200;
            font-family: 'Poppins';
            padding-top: 100px; 
            padding-left:76px;
        }
        
        .username {
            color: #6741d9;
            font-weight: 500;
            font-family: 'Poppins';
            font-size: 40px;
        }
        
        /* PROJECTS SECTION */
        .projects-section {
            margin-top: 30px;
        }
        
        .projects-section h1 {
            font-size: 2rem;
            font-weight: 500;
            color: #3E236A;
            margin-bottom: 30px;
            font-family: 'Poppins';
            margin-left:30px
        }
        
        .project-cards {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .project-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .project-card h2 {
            font-size: 1.4rem;
            font-weight: 500;
            color: #3E236A;
            margin-bottom: 15px;
            font-family: 'Poppins';
        }
        
        .project-card p {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .project-card .access-btn {
            display: inline-block;
            background: none;
            color: #6741d9;
            font-weight: 500;
            text-decoration: none;
            padding: 8px 0;
            font-family: 'Poppins';
            font-size: 1rem;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .project-card .access-btn:hover {
            border-bottom-color: #6741d9;
        }
        
        /* CONTACT SECTION */
        .contact h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .contact p {
            color: #868e96;
            font-size: 1rem;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            nav ul {
                padding-right: 20px;
                gap: 30px;
            }
            
            .greeting {
                height: 200px;
            }
            
            .greeting h1 {
                font-size: 32px;
                padding-top: 50px;
                padding-left:30px
            }
            
            .username {
                font-size: 32px;
            }
        }
        
        @media (max-width: 600px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            nav ul {
                padding: 20px 0;
                gap: 20px;
                flex-direction: column;
            }
            
            section {
                padding: 20px;
            }
            
            .projects-section h1 {
                font-size: 1.6rem;
            }
            
            .project-card h2 {
                font-size: 1.2rem;
            }
        }

        .contact {
            display: flex;
            align-items: center;
            margin-bottom: 30px; 
            margin-top:30px;
        }

        .contact .text {
            flex: 1;
            position: absolute;
            left:180px;
        }
        .contact p{
            position: relative; 
            font-family:'Poppins';
            font-weight:400;
            color: #3E236A;
            padding-left: 99px; 
        }

        .contact h2{
            position:relative; 
            font-family: 'Poppins';
            font-weight:600px;
            color:  #3E236A;
            font-size:40px;
            padding-left:90px; 
        }
        .contact button {
            position:relative; 
            left: 900px;
            height: 30px;
            width:190px;
            padding: 20px;
            font-family: 'Poppins';
            font-weight: 400;
            font-size:15px;
            color: white;
            border-radius: 30px;
            background-color: #9999FF;
            border: none; 
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .contact button:hover {
            background-color: #3E236A;
        }
        .contato {
            margin-top: 20px;
        }

        /* Seção Contatos */
        .contatos {
            text-align: center;
            justify-content: center;
            padding: 90px 70px;
            height: 300px;
        }

        .contato-section {
            position: relative;
            z-index: 10; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
        }
        .contato-section h3{
            font-family:"Poppins";
            font-weight:500; 
        }

        .trabalhos p{
            font-family: ' Poppins';
            font-weight:300;
        }
        .texto h3 {
            position: relative;
            color: #3e206e;
            font-size: 1.8rem;
            text-align: left;
            text-transform: none;
            letter-spacing: normal;
        }

        .redes {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .rede {
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: var(--transicao);
            padding: 20px;
            border-radius: 10px;
        }

        .rede:hover {
            background: rgba(126, 117, 227, 0.1);
            transform: translateY(-5px);
        }

        .rede img {
            width: 40px;
            height: 40px;
            margin-bottom: 10px;
        }

        .rede p {
            font-weight: 400;
            color:  #9999FF;
            font-size: 0.9rem;
            font-family: 'Poppins';
        }

        /* Status styles */
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            font-family: 'Poppins';
        }

        .status.em_andamento {
            background-color: #fff3cd;
            color: #856404;
        }

        .status.aprovado {
            background-color: #d4edda;
            color: #9999FF;
        }

        .status.finalizado {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status.ajustes {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-family: 'Poppins';
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #3E236A;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Button styles */
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
            font-family: 'Poppins';
        }

        .btn-primary {
            background-color: #9999FF;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3E236A;
        }

        .btn-success {
            background-color: #9999FF;
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

        /* Form styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #3E236A;
            font-family: 'Poppins';
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
            font-family: 'Poppins';
        }

        .form-control:focus {
            outline: none;
            border-color: #9999FF;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Alert styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
            font-family: 'Poppins';
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
            font-family: 'Poppins';
        }

        /* Footer styles */
        .rodape {
            background-color: #9999FF;
            width: 100vw;
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
        .pages a {
            display: block;
            margin-bottom: 15px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            font-family:"Poppins";
            font-weight:300; 
            transition: all 0.4s cubic-bezier(0.65, 0, 0.35, 1);
            position: relative;
        }

        .pages a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: white;
            transition: all 0.4s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .pages a:hover::after {
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

        @media (max-width: 768px) {
            .pages a {
                margin-bottom: 10px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-header">    
            <img src="../assets/img/augebit.logo.png" alt="Logo da empresa"> 
            <h1>AUGEBIT</h1>
        </div>    
        <nav>       
            <ul>         
                <li><a href="../index.php" class="active">Home</a></li>         
                <li><a href="#projects-section" class="acount">Projetos</a></li>         
                <li><a href="../logout.php" class="contato">Sair</a></li>    
            </ul>     
        </nav>   
    </header>    
    
    <div class="dashboard">
        <section class="greeting">
            <h1>Olá <span class="username"><?php echo htmlspecialchars($nome_cliente); ?></span></h1>
            <a href="solicitar_projeto.php" class="new-project-btn">Solicitar novo projeto</a>
        </section>
        
        <section id="projects-section" class="projects-section">
            <h1>Seus Projetos</h1>
            
            <div class="project-cards">
                <?php if ($projetos->num_rows > 0): ?>
                    <?php while ($projeto = $projetos->fetch_assoc()): ?>
                        <div class="project-card">
                            <h2><?php echo htmlspecialchars($projeto['titulo']); ?></h2>
                            <p><strong>Status:</strong> <span class="status <?php echo $projeto['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $projeto['status'])); ?>
                            </span></p>
                            <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i', strtotime($projeto['criado_em'])); ?></p>
                            
                            <div class="actions">
                                <a href="#" class="btn btn-primary" onclick="alert('Funcionalidade em desenvolvimento')">Ver Tarefas</a>
                                <a href="#" class="btn btn-info" onclick="alert('Funcionalidade em desenvolvimento')">Ver Documentos</a>
                                <?php if ($projeto['status'] === 'finalizado'): ?>
                                    <a href="#" class="btn btn-success" onclick="alert('Funcionalidade em desenvolvimento')">Ver Entrega Final</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="project-card">
                        <h2>Nenhum projeto encontrado</h2>
                        <p>Você ainda não possui projetos cadastrados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <section class="projects-section">
            <h1>Arquivos Enviados</h1>
            
            <div class="project-cards">
                <?php if ($uploads->num_rows > 0): ?>
                    <?php while ($upload = $uploads->fetch_assoc()): ?>
                        <div class="project-card">
                            <h2><?php echo htmlspecialchars($upload['nome_arquivo']); ?></h2>
                            <p><strong>Projeto:</strong> <?php echo htmlspecialchars($upload['projeto_titulo']); ?></p>
                            <p><strong>Data de Envio:</strong> <?php echo date('d/m/Y H:i', strtotime($upload['enviado_em'])); ?></p>
                            
                            <div class="actions">
                                <a href="<?php echo htmlspecialchars($upload['caminho_arquivo']); ?>" class="btn btn-primary" target="_blank">Visualizar</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="project-card">
                        <h2>Nenhum arquivo enviado</h2>
                        <p>Você ainda não enviou nenhum arquivo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <section class="projects-section">
            <h1>Enviar Novo Arquivo</h1>
            
            <div class="project-card">
                <?php if (isset($mensagem_sucesso)): ?>
                    <div class="alert alert-success"><?php echo $mensagem_sucesso; ?></div>
                <?php endif; ?>
                
                <?php if (isset($mensagem_erro)): ?>
                    <div class="alert alert-danger"><?php echo $mensagem_erro; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
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
        </section>
        
        <section class="projects-section">
            <h1>Enviar Mensagem para o Gestor</h1>
            
            <div class="project-card">
                <?php if (isset($mensagem_chat_sucesso)): ?>
                    <div class="alert alert-success"><?php echo $mensagem_chat_sucesso; ?></div>
                <?php endif; ?>
                
                <?php if (isset($mensagem_chat_erro)): ?>
                    <div class="alert alert-danger"><?php echo $mensagem_chat_erro; ?></div>
                <?php endif; ?>

                <form method="POST" class="chat-form">
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
        </section>
        
        <section class="contact">
            <div class="text">
                <h2>Precisa conversar?</h2>
                <p>Acesse o chat</p>
            </div>
            <button>Acesse aqui</button>
        </section>

        <section id="contato-section" class="contatos">
            <div class="contato-section">
                <div class="texto">
                    <h3>Entre em Contato</h3>
                </div>
                <div class="redes">
                    <div class="rede">
                        <img src="../assets/img/emailroxo.png" alt="Email">
                        <p>Email</p>
                    </div>
                    <div class="rede">
                        <img src="../assets/img/linkedinroxo.png" alt="Linkedin">
                        <p>Linkedin</p>
                    </div>
                    <div class="rede">
                        <img src="../assets/img/zaproxo.png" alt="Whatsapp">
                        <p>Whatsapp</p>
                    </div>
                    <div class="rede">
                        <img src="../assets/img/instaroxo.png" alt="Instagram">
                        <p>Instagram</p>
                    </div>
                </div>
            </div>
        </section>

        <footer class="rodape">
            <div class="pages">
                <a href="index.html">Home</a>
                <a href="projetos.html">Projetos</a>
                <a href="#contato-section">Entre em contato</a>
                <a href="../logout.php">Sair</a>
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
    </div>
</body>
</html>