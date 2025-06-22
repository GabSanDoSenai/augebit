<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require '../../conexao.php';

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
<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos do Projeto</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        .document-container {
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
    </style>
</head>
<body>
    <div class="main-content">
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
                $caminho = "../../" . htmlspecialchars($doc['caminho_arquivo']);
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
    </div>
</body>
</html>