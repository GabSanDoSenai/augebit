<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../../login.php");
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

// Buscar documentos
$stmt = $conn->prepare("SELECT nome_arquivo, caminho_arquivo, enviado_em FROM uploads WHERE projeto_id = ?");
$stmt->bind_param("i", $projeto_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos do Projeto</title>
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
        .document-container {
            margin: 50px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(153, 153, 255, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid #e6e6ff;
        }

        .document-header {
            background: linear-gradient(135deg, #9999FF, #7a7aff);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .document-title {
            font-weight: 600;
            font-size: 16px;
            margin: 0;
        }

        .document-date {
            font-size: 14px;
            opacity: 0.9;
        }

        .document-content {
            padding: 20px;
        }

        .document-preview {
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #e6e6ff;
            border-radius: 6px;
            overflow: hidden;
        }

        .document-preview img {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            display: block;
        }

        .document-preview iframe {
            width: 100%;
            height: 500px;
            border: none;
        }

        .no-preview {
            padding: 40px;
            background: #f8f8ff;
            color: #666;
            text-align: center;
            font-style: italic;
        }

        .download-btn {
            background: linear-gradient(135deg, #9999FF, #7a7aff);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(153, 153, 255, 0.3);
        }

        .download-btn:hover {
            background: linear-gradient(135deg, #8080ff, #6666ff);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(153, 153, 255, 0.4);
        }

        .download-icon {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .no-documents {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(153, 153, 255, 0.1);
            border: 1px solid #e6e6ff;
        }

        .no-documents-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            opacity: 0.5;
        }

        .page-title {
            margin: 50px;
            color: #4d4dff;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #9999FF;
        }

        .file-extension {
            background: #9999FF;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            margin-left: 10px;
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
        <h2 class="page-title">Documentos do Projeto: <?= htmlspecialchars($projeto['titulo']) ?></h2>

        <?php if ($result->num_rows === 0): ?>
            <div class="no-documents">
                <svg class="no-documents-icon" viewBox="0 0 24 24" fill="none" stroke="#9999FF" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10,9 9,9 8,9"/>
                </svg>
            <h3 style="color: #666; margin-bottom: 10px;">Nenhum documento encontrado</h3>
                <p style="color: #999;">Este projeto ainda não possui documentos enviados.</p>
            </div>
        <?php else: ?>
            <?php while ($doc = $result->fetch_assoc()): 
                $nome = htmlspecialchars($doc['nome_arquivo']);
                $caminho = "../" . htmlspecialchars($doc['caminho_arquivo']);
                $ext = strtolower(pathinfo($doc['caminho_arquivo'], PATHINFO_EXTENSION));
                $data_formatada = date('d/m/Y H:i', strtotime($doc['enviado_em']));
            ?>
                <div class="document-container">
                    <div class="document-header">
                        <div>
                            <h3 class="document-title"><?= $nome ?></h3>
                        </div>
                        <div class="document-info">
                            <span class="file-extension"><?= $ext ?></span>
                            <span class="document-date"><?= $data_formatada ?></span>
                        </div>
                    </div>
                    
                    <div class="document-content">
                        <div class="document-preview">
                            <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                <img src="<?= $caminho ?>" alt="<?= $nome ?>" loading="lazy">
                            <?php elseif ($ext === 'pdf'): ?>
                                <iframe src="<?= $caminho ?>" title="<?= $nome ?>"></iframe>
                            <?php else: ?>
                                <div class="no-preview">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9999FF" stroke-width="1.5">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <polyline points="10,9 9,9 8,9"/>
                                    </svg>
                                    <p>Visualização não disponível para arquivos .<?= $ext ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="text-align: center;">
                            <a href="<?= $caminho ?>" download="<?= $nome ?>" class="download-btn">
                                <svg class="download-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7,10 12,15 17,10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Baixar Arquivo
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
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