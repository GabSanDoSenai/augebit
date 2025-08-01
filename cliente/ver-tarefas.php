<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../login.php");
    exit;
}
require '../conexao.php';

$projeto_id = $_GET['projeto_id'] ?? null;
if (!$projeto_id || !is_numeric($projeto_id)) {
    die("Projeto inválido.");
}

// Buscar nome do projeto
$projeto = $conn->query("SELECT titulo FROM projetos WHERE id = $projeto_id")->fetch_assoc();
if (!$projeto) {
    die("Projeto não encontrado.");
}

// Buscar tarefas
$sql = "
    SELECT t.id, t.titulo, t.status, t.criado_em, u.nome AS funcionario
    FROM tarefas t
    LEFT JOIN usuarios u ON t.funcionario_id = u.id
    WHERE t.projeto_id = $projeto_id
    ORDER BY t.criado_em DESC
";
$tarefas = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="fontes.css">
    <style>
       * {
            border: 0;
            margin: 0;
            padding: 0;
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

        body {
            font-family: 'Poppins';
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #9999ff 0%, #7777ff 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(153, 153, 255, 0.3);
            margin-bottom: 50px;
            text-align: center;
        }

        .project-name {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(153, 153, 255, 0.1);
            overflow: hidden;
            border: 2px solid;
        }

        .no-tasks {
            text-align: center;
            padding: 60px 40px;
            color: #6666cc;
        }

        .no-tasks-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-tasks h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #7777ff;
        }

        .no-tasks p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #9999ff 0%, #8888ff 100%);
            color: white;
        }

        th {
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #7777ff;
        }

        tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0ff;
        }

        tbody tr:nth-child(even) {
            background-color: #fafaff;
        }

        tbody tr:hover {
            background: linear-gradient(135deg, #f0f0ff 0%, #e8e8ff 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(153, 153, 255, 0.1);
        }

        td {
            padding: 18px 15px;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .task-id {
            font-weight: 600;
            color: #7777ff;
            background: #f0f0ff;
            padding: 8px 12px;
            border-radius: 20px;
            display: inline-block;
            min-width: 50px;
            text-align: center;
        }

        .task-title {
            font-weight: 500;
            color: #333;
            max-width: 250px;
            word-wrap: break-word;
        }

        .employee-name {
            color: #6666cc;
            font-weight: 500;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-pendente {
            background: linear-gradient(135deg, #ffcc99 0%, #ffb366 100%);
            color: #cc6600;
        }

        .status-andamento {
            background: linear-gradient(135deg, #99ccff 0%, #66b3ff 100%);
            color: #0066cc;
        }

        .status-concluido {
            background: linear-gradient(135deg, #99ff99 0%, #66ff66 100%);
            color: #009900;
        }

        .date-info {
            color: #666;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .actions {
            margin-top: 30px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #9999ff 0%, #7777ff 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(153, 153, 255, 0.3);
            margin: 0 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(153, 153, 255, 0.4);
            background: linear-gradient(135deg, #8888ff 0%, #6666ff 100%);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #ccccff 0%, #b3b3ff 100%);
            color: #4444cc;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #b3b3ff 0%, #9999ff 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 2rem;
            }

            th, td {
                padding: 12px 8px;
                font-size: 0.9rem;
            }

            .task-title {
                max-width: 150px;
            }
        }

        /* Animações sutis */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-card {
            animation: fadeIn 0.6s ease-out;
        }

        .header {
            animation: fadeIn 0.8s ease-out;
        }
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

        .actions {
            margin-top: 15px;
        }

        .actions .btn {
            margin-right: 10px;
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
                <li><a href="dashboard_cliente.php" class="active">Home</a></li>         
                <li><a href="dashboard_cliente.php" class="acount">Projetos</a></li>
                <li><a href="chat.php">Chat</a></li>     
                <li><a href="../index.php" class="contato">Sair</a></li>    
            </ul>     
        </nav>   
    </header>
    <div class="container">
        <div class="header">
            <h1>Tarefas do Projeto</h1>
            <div class="project-name"><?php echo htmlspecialchars($projeto['titulo']); ?></div>
        </div>

        <div class="content-card">
            <?php if (empty($tarefas)): ?>
                <div class="no-tasks">
                    <div class="no-tasks-icon">📋</div>
                    <h3>Nenhuma tarefa cadastrada</h3>
                    <p>Este projeto ainda não possui tarefas cadastradas.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Funcionário</th>
                                <th>Status</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarefas as $tarefa): ?>
                                <tr>
                                    <td>
                                        <span class="task-id"><?php echo $tarefa['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="task-title"><?php echo htmlspecialchars($tarefa['titulo']); ?></div>
                                    </td>
                                    <td>
                                        <span class="employee-name"><?php echo htmlspecialchars($tarefa['funcionario']); ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $tarefa['status']; ?>">
                                            <?php echo ucfirst($tarefa['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-info"><?php echo date('d/m/Y H:i', strtotime($tarefa['criado_em'])); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="dashboard_cliente.php" class="btn btn-secondary">Voltar ao Projeto</a>
        </div>
    </div>
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
</body>
</html>

