<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require '../../conexao.php';

// Função para formatar tamanho de arquivo
function formatarTamanhoArquivo($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Função para determinar o tipo de arquivo
function getTipoArquivo($mimeType, $extensao) {
    if (strpos($mimeType, 'image/') === 0) {
        return 'imagem';
    } elseif ($mimeType === 'application/pdf') {
        return 'pdf';
    } else {
        return 'outro';
    }
}

// Query atualizada para incluir as novas colunas
$sql = "
    SELECT 
        u.id as upload_id,
        u.nome_arquivo, 
        u.caminho_arquivo, 
        u.tamanho_arquivo,
        u.tipo_mime,
        COALESCE(u.data_upload, u.enviado_em) as data_envio,
        u.status_arquivo,
        p.titulo AS projeto_nome,
        p.id as projeto_id,
        p.status as projeto_status,
        c.nome as cliente_nome
    FROM uploads u
    LEFT JOIN projetos p ON u.projeto_id = p.id
    LEFT JOIN usuarios c ON p.cliente_id = c.id
    WHERE u.status_arquivo = 'ativo' OR u.status_arquivo IS NULL
    ORDER BY p.titulo ASC, u.data_upload DESC, u.enviado_em DESC
";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<p>Nenhum documento encontrado.</p>";
    exit;
}

$documentosPorProjeto = [];
$totalArquivos = 0;
$totalTamanho = 0;

while ($row = $result->fetch_assoc()) {
    $projetoId = $row['projeto_id'];
    $documentosPorProjeto[$projetoId]['nome'] = $row['projeto_nome'] ?? 'Projeto Desconhecido';
    $documentosPorProjeto[$projetoId]['status'] = $row['projeto_status'] ?? 'indefinido';
    $documentosPorProjeto[$projetoId]['cliente'] = $row['cliente_nome'] ?? 'Cliente Desconhecido';
    $documentosPorProjeto[$projetoId]['docs'][] = $row;
    
    $totalArquivos++;
    $totalTamanho += $row['tamanho_arquivo'] ?? 0;
}
?>
<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Documentos por Projeto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
            font-size: 12px;
            padding: 5px 10px;
        }

        .search-box {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
        }

        .accordion {
            background: white;
            color: #333;
            cursor: pointer;
            padding: 20px;
            width: 100%;
            border: none;
            outline: none;
            text-align: left;
            font-size: 16px;
            margin-top: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .accordion:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .accordion.active {
            background: #3498db;
            color: white;
        }

        .projeto-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .projeto-titulo {
            font-weight: bold;
            font-size: 18px;
        }

        .projeto-meta {
            font-size: 14px;
            opacity: 0.8;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pendente { background: #f39c12; color: white; }
        .status-em-andamento { background: #3498db; color: white; }
        .status-concluido { background: #27ae60; color: white; }
        .status-cancelado { background: #e74c3c; color: white; }

        .panel {
            display: none;
            padding: 25px;
            background: white;
            border-left: 4px solid #3498db;
            margin-bottom: 15px;
            border-radius: 0 10px 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .doc-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }

        .doc-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .doc-info h4 {
            color: #2c3e50;
            margin-bottom: 5px;
            word-break: break-word;
        }

        .doc-meta {
            font-size: 12px;
            color: #7f8c8d;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .doc-preview {
            text-align: center;
            margin-bottom: 15px;
        }

        .doc-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .doc-preview iframe {
            width: 100%;
            height: 250px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .file-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .doc-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .panel-stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .no-docs {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .docs-grid {
                grid-template-columns: 1fr;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Gerenciar Documentos por Projeto</h1>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalArquivos ?></div>
                    <div>Total de Arquivos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= formatarTamanhoArquivo($totalTamanho) ?></div>
                    <div>Espaço Utilizado</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= count($documentosPorProjeto) ?></div>
                    <div>Projetos com Docs</div>
                </div>
            </div>

            <div class="controls">
                <input type="text" class="search-box" id="searchBox" placeholder="Buscar projetos ou documentos...">
                <button class="btn btn-primary" onclick="expandirTodos()">Expandir Todos</button>
                <button class="btn btn-primary" onclick="recolherTodos()">Recolher Todos</button>
                <button class="btn btn-success" onclick="window.location.reload()">🔄 Atualizar</button>
            </div>
        </div>

        <div id="projectsContainer">
            <?php if (empty($documentosPorProjeto)): ?>
                <div class="no-docs">
                    <h3>📂 Nenhum documento encontrado</h3>
                    <p>Ainda não há documentos enviados para nenhum projeto.</p>
                </div>
            <?php else: ?>
                <?php foreach ($documentosPorProjeto as $projetoId => $dados): ?>
                    <div class="project-item" data-projeto="<?= strtolower($dados['nome']) ?>" data-cliente="<?= strtolower($dados['cliente']) ?>">
                        <button class="accordion">
                            <div class="projeto-info">
                                <div class="projeto-titulo">
                                    📂 <?= htmlspecialchars($dados['nome']) ?>
                                </div>
                                <div class="projeto-meta">
                                    Cliente: <?= htmlspecialchars($dados['cliente']) ?> | 
                                    <?= count($dados['docs']) ?> arquivo(s)
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-<?= $dados['status'] ?>">
                                    <?= ucfirst($dados['status']) ?>
                                </span>
                            </div>
                        </button>
                        
                        <div class="panel">
                            <div class="panel-header">
                                <h3>Documentos do Projeto</h3>
                                <div class="panel-stats">
                                    <span>📄 <?= count($dados['docs']) ?> arquivos</span>
                                    <span>💾 <?= formatarTamanhoArquivo(array_sum(array_column($dados['docs'], 'tamanho_arquivo'))) ?></span>
                                </div>
                            </div>

                            <?php if (empty($dados['docs'])): ?>
                                <div class="no-docs">
                                    <p>Nenhum documento encontrado para este projeto.</p>
                                </div>
                            <?php else: ?>
                                <div class="docs-grid">
                                    <?php foreach ($dados['docs'] as $doc): 
                                        $nome = htmlspecialchars($doc['nome_arquivo']);
                                        $link = '../' . htmlspecialchars($doc['caminho_arquivo']);
                                        $data = $doc['data_envio'];
                                        $tamanho = $doc['tamanho_arquivo'] ? formatarTamanhoArquivo($doc['tamanho_arquivo']) : 'N/A';
                                        $mimeType = $doc['tipo_mime'] ?? '';
                                        $ext = strtolower(pathinfo($doc['caminho_arquivo'], PATHINFO_EXTENSION));
                                        $tipoArquivo = getTipoArquivo($mimeType, $ext);
                                    ?>
                                        <div class="doc-card" data-filename="<?= strtolower($nome) ?>">
                                            <div class="doc-header">
                                                <div class="doc-info">
                                                    <h4><?= $nome ?></h4>
                                                    <div class="doc-meta">
                                                        <span>📅 <?= date('d/m/Y H:i', strtotime($data)) ?></span>
                                                        <span>📏 <?= $tamanho ?></span>
                                                        <?php if ($mimeType): ?>
                                                            <span>🏷️ <?= $mimeType ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <button class="btn btn-danger" onclick="confirmarExclusao(<?= $doc['upload_id'] ?>, '<?= addslashes($nome) ?>')">
                                                    🗑️
                                                </button>
                                            </div>

                                            <div class="doc-preview">
                                                <?php if ($tipoArquivo === 'imagem'): ?>
                                                    <img src="<?= $link ?>" alt="<?= $nome ?>" loading="lazy">
                                                <?php elseif ($tipoArquivo === 'pdf'): ?>
                                                    <div class="file-icon">📄</div>
                                                    <iframe src="<?= $link ?>" loading="lazy"></iframe>
                                                <?php else: ?>
                                                    <div class="file-icon">📎</div>
                                                    <p>Arquivo: <?= $ext ?></p>
                                                <?php endif; ?>
                                            </div>

                                            <div class="doc-actions">
                                                <a href="<?= $link ?>" class="btn btn-primary" target="_blank">
                                                    👁️ Visualizar
                                                </a>
                                                <a href="<?= $link ?>" class="btn btn-success" download>
                                                    ⬇️ Download
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Accordion toggle
        document.querySelectorAll('.accordion').forEach(button => {
            button.addEventListener('click', () => {
                button.classList.toggle('active');
                const panel = button.nextElementSibling;
                panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
            });
        });

        // Função de busca
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const projectItems = document.querySelectorAll('.project-item');
            
            projectItems.forEach(item => {
                const projectName = item.dataset.projeto;
                const clientName = item.dataset.cliente;
                const docCards = item.querySelectorAll('.doc-card');
                let hasVisibleDocs = false;
                
                // Buscar em documentos
                docCards.forEach(card => {
                    const filename = card.dataset.filename;
                    if (filename.includes(searchTerm)) {
                        card.style.display = 'block';
                        hasVisibleDocs = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Mostrar projeto se nome/cliente coincidir ou tem documentos visíveis
                if (projectName.includes(searchTerm) || 
                    clientName.includes(searchTerm) || 
                    hasVisibleDocs || 
                    searchTerm === '') {
                    item.style.display = 'block';
                    
                    // Se busca não está vazia, mostrar todos os docs do projeto encontrado
                    if (searchTerm !== '' && (projectName.includes(searchTerm) || clientName.includes(searchTerm))) {
                        docCards.forEach(card => card.style.display = 'block');
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Expandir todos
        function expandirTodos() {
            document.querySelectorAll('.accordion').forEach(button => {
                button.classList.add('active');
                button.nextElementSibling.style.display = 'block';
            });
        }

        // Recolher todos
        function recolherTodos() {
            document.querySelectorAll('.accordion').forEach(button => {
                button.classList.remove('active');
                button.nextElementSibling.style.display = 'none';
            });
        }

        // Confirmação de exclusão
        function confirmarExclusao(uploadId, nomeArquivo) {
            if (confirm(`Tem certeza que deseja excluir o arquivo "${nomeArquivo}"?\n\nEsta ação não pode ser desfeita.`)) {
                // Aqui você pode implementar a exclusão via AJAX
                window.location.href = `excluir_documento.php?id=${uploadId}`;
            }
        }

        // Lazy loading para iframes
        const iframes = document.querySelectorAll('iframe[loading="lazy"]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = entry.target;
                    iframe.src = iframe.src; // Recarrega o iframe
                    observer.unobserve(iframe);
                }
            });
        });

        iframes.forEach(iframe => imageObserver.observe(iframe));
    </script>
</body>
</html>