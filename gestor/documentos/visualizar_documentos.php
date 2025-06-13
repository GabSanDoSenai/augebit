<?php 
include '../sidebar.php'; 
require '../../conexao.php';

// Verificar se o usuário está logado e tem permissão
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'funcionario')) {
    header("Location: ../../login.php");
    exit;
}

// Parâmetros para paginação e filtros
$limite = 20;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

$filtro_projeto = isset($_GET['projeto']) ? (int)$_GET['projeto'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Construir a query com filtros
$where_conditions = [];
$params = [];
$types = '';

if ($filtro_projeto) {
    $where_conditions[] = "u.projeto_id = ?";
    $params[] = $filtro_projeto;
    $types .= 'i';
}

if ($filtro_tipo) {
    $where_conditions[] = "u.tipo = ?";
    $params[] = $filtro_tipo;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query principal com JOIN para pegar informações do projeto e usuário
$sql = "SELECT u.*, p.titulo as projeto_titulo, us.nome as enviado_por_nome, us.tipo as enviado_por_tipo
        FROM uploads u 
        INNER JOIN projetos p ON u.projeto_id = p.id 
        INNER JOIN usuarios us ON u.enviado_por = us.id 
        $where_clause
        ORDER BY u.enviado_em DESC 
        LIMIT ? OFFSET ?";

$params[] = $limite;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$documentos = $stmt->get_result();

// Contar total de registros para paginação
$sql_count = "SELECT COUNT(*) as total 
              FROM uploads u 
              INNER JOIN projetos p ON u.projeto_id = p.id 
              INNER JOIN usuarios us ON u.enviado_por = us.id 
              $where_clause";

if (!empty($where_conditions)) {
    $stmt_count = $conn->prepare($sql_count);
    $types_count = str_replace('ii', '', $types); // Remove os parâmetros de limite e offset
    $params_count = array_slice($params, 0, -2); // Remove limite e offset
    if (!empty($params_count)) {
        $stmt_count->bind_param($types_count, ...$params_count);
    }
    $stmt_count->execute();
    $total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
} else {
    $total_registros = $conn->query($sql_count)->fetch_assoc()['total'];
}

$total_paginas = ceil($total_registros / $limite);

// Buscar projetos para o filtro
$projetos = $conn->query("SELECT id, titulo FROM projetos ORDER BY titulo");

// Verificar mensagens de sucesso/erro
$mensagem_sucesso = isset($_GET['sucesso']) ? $_GET['sucesso'] : '';
$mensagem_erro = isset($_GET['erro']) ? $_GET['erro'] : '';

// Função para formatar tamanho do arquivo
function formatarTamanho($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Função para obter ícone do arquivo
function obterIconeArquivo($nomeArquivo) {
    $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
    
    switch ($extensao) {
        case 'pdf':
            return '📄';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'webp':
            return '🖼️';
        case 'doc':
        case 'docx':
            return '📝';
        case 'xls':
        case 'xlsx':
            return '📊';
        case 'zip':
        case 'rar':
            return '📦';
        default:
            return '📎';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Documentos - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        .main-content {
            padding: 20px;
            margin-left: 300px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .header-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .header-section h2 {
            color: #2c3e50;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filtros {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filtro-grupo {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filtro-grupo label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .filtro-grupo select {
            padding: 8px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 14px;
            min-width: 150px;
        }

        .btn-filtrar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-filtrar:hover {
            transform: translateY(-2px);
        }

        .btn-limpar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
        }

        .documentos-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .documentos-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .documentos-lista {
            max-height: 600px;
            overflow-y: auto;
        }

        .documento-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: background-color 0.2s;
        }

        .documento-item:hover {
            background-color: #f8f9fa;
        }

        .documento-item:last-child {
            border-bottom: none;
        }

        .documento-icone {
            font-size: 2em;
            width: 50px;
            text-align: center;
        }

        .documento-info {
            flex: 1;
        }

        .documento-nome {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            word-break: break-word;
        }

        .documento-detalhes {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #666;
            flex-wrap: wrap;
        }

        .documento-detalhes span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .documento-acoes {
            display: flex;
            gap: 10px;
        }

        .btn-acao {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: transform 0.2s;
        }

        .btn-acao:hover {
            transform: translateY(-1px);
        }

        .btn-visualizar {
            background: #28a745;
            color: white;
        }

        .btn-download {
            background: #007bff;
            color: white;
        }

        .btn-excluir {
            background: #dc3545;
            color: white;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-cliente {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-funcionario {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-admin {
            background: #fff3e0;
            color: #f57c00;
        }

        .paginacao {
            padding: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            background: #f8f9fa;
        }

        .paginacao a, .paginacao span {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
        }

        .paginacao a:hover {
            background: #e9ecef;
        }

        .paginacao .atual {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .sem-documentos {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .sem-documentos .icone {
            font-size: 4em;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .filtros {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .documento-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .documento-detalhes {
                flex-direction: column;
                gap: 5px;
            }

            .documento-acoes {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Mensagens de sucesso/erro -->
        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($mensagem_sucesso) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensagem_erro): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($mensagem_erro) ?>
            </div>
        <?php endif; ?>

        <!-- Header com filtros -->
        <div class="header-section">
            <h2>📁 Documentos Recentes</h2>
            
            <form method="GET" class="filtros">
                <div class="filtro-grupo">
                    <label>Projeto:</label>
                    <select name="projeto">
                        <option value="">Todos os projetos</option>
                        <?php while ($proj = $projetos->fetch_assoc()): ?>
                            <option value="<?= $proj['id'] ?>" <?= $filtro_projeto == $proj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['titulo']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filtro-grupo">
                    <label>Enviado por:</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="cliente" <?= $filtro_tipo === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                        <option value="funcionario" <?= $filtro_tipo === 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
                        <option value="admin" <?= $filtro_tipo === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn-filtrar">🔍 Filtrar</button>
                <a href="visualizar_documentos.php" class="btn-limpar">🗑️ Limpar</a>
            </form>
        </div>

        <!-- Cards de estatísticas -->
        <div class="stats-cards">
            <?php
            // Buscar estatísticas
            $stats_hoje = $conn->query("SELECT COUNT(*) as total FROM uploads WHERE DATE(enviado_em) = CURDATE()")->fetch_assoc()['total'];
            $stats_semana = $conn->query("SELECT COUNT(*) as total FROM uploads WHERE enviado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];
            $stats_mes = $conn->query("SELECT COUNT(*) as total FROM uploads WHERE enviado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['total'];
            ?>
            
            <div class="stat-card">
                <div class="number"><?= $stats_hoje ?></div>
                <div class="label">Hoje</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats_semana ?></div>
                <div class="label">Esta Semana</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats_mes ?></div>
                <div class="label">Este Mês</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $total_registros ?></div>
                <div class="label">Total</div>
            </div>
        </div>

        <!-- Lista de documentos -->
        <div class="documentos-container">
            <div class="documentos-header">
                <h3 style="margin: 0;">📋 Lista de Documentos</h3>
                <span><?= $total_registros ?> documento(s) encontrado(s)</span>
            </div>

            <?php if ($documentos->num_rows > 0): ?>
                <div class="documentos-lista">
                    <?php while ($doc = $documentos->fetch_assoc()): ?>
                        <div class="documento-item">
                            <div class="documento-icone">
                                <?= obterIconeArquivo($doc['nome_arquivo']) ?>
                            </div>
                            
                            <div class="documento-info">
                                <div class="documento-nome">
                                    <?= htmlspecialchars($doc['nome_arquivo']) ?>
                                </div>
                                <div class="documento-detalhes">
                                    <span>
                                        🏗️ <?= htmlspecialchars($doc['projeto_titulo']) ?>
                                    </span>
                                    <span>
                                        👤 <?= htmlspecialchars($doc['enviado_por_nome']) ?>
                                        <span class="badge badge-<?= $doc['enviado_por_tipo'] ?>">
                                            <?= ucfirst($doc['enviado_por_tipo']) ?>
                                        </span>
                                    </span>
                                    <span>
                                        📅 <?= date('d/m/Y H:i', strtotime($doc['enviado_em'])) ?>
                                    </span>
                                    <?php 
                                    // Ajustar caminho do arquivo para a pasta uploads na raiz
                                    $caminho_arquivo = str_replace('../../uploads/', '../../uploads/', $doc['caminho_arquivo']);
                                    if (file_exists($caminho_arquivo)): 
                                    ?>
                                        <span>
                                            💾 <?= formatarTamanho(filesize($caminho_arquivo)) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="documento-acoes">
                                <?php 
                                $extensao = strtolower(pathinfo($doc['nome_arquivo'], PATHINFO_EXTENSION));
                                // Ajustar caminho do arquivo para a pasta uploads na raiz
                                $caminho_arquivo = str_replace('../../uploads/', '../../uploads/', $doc['caminho_arquivo']);
                                $arquivo_existe = file_exists($caminho_arquivo);
                                // Caminho para exibição no navegador (relativo à raiz do projeto)
                                $caminho_web = str_replace('../../', '../', $doc['caminho_arquivo']);
                                ?>
                                
                                <?php if ($arquivo_existe): ?>
                                    <?php if (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])): ?>
                                        <a href="<?= $caminho_web ?>" 
                                           target="_blank" 
                                           class="btn-acao btn-visualizar"
                                           title="Visualizar arquivo">
                                            👁️ Ver
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="<?= $caminho_web ?>" 
                                       download="<?= $doc['nome_arquivo'] ?>"
                                       class="btn-acao btn-download"
                                       title="Baixar arquivo">
                                        💾 Baixar
                                    </a>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-size: 12px;">❌ Arquivo não encontrado</span>
                                <?php endif; ?>
                                
                                <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                                    <button onclick="confirmarExclusao(<?= $doc['id'] ?>, '<?= addslashes($doc['nome_arquivo']) ?>')"
                                            class="btn-acao btn-excluir"
                                            title="Excluir documento">
                                        🗑️ Excluir
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="paginacao">
                        <?php
                        $query_string = $_SERVER['QUERY_STRING'];
                        $query_string = preg_replace('/&?pagina=[^&]*/', '', $query_string);
                        $query_string = $query_string ? '&' . $query_string : '';
                        ?>
                        
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?= $pagina - 1 ?><?= $query_string ?>">« Anterior</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                            <?php if ($i == $pagina): ?>
                                <span class="atual"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?pagina=<?= $i ?><?= $query_string ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?= $pagina + 1 ?><?= $query_string ?>">Próxima »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="sem-documentos">
                    <div class="icone">📄</div>
                    <h3>Nenhum documento encontrado</h3>
                    <p>Não há documentos que correspondam aos critérios de filtro selecionados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmarExclusao(id, nomeArquivo) {
            if (confirm(`Tem certeza que deseja excluir o documento "${nomeArquivo}"?\n\nEsta ação não pode ser desfeita.`)) {
                // Criar formulário para envio da exclusão
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'excluir_documento.php';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'documento_id';
                inputId.value = id;
                
                form.appendChild(inputId);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-refresh da página a cada 5 minutos para mostrar novos documentos
        setTimeout(() => {
            location.reload();
        }, 300000);

        // Mostrar loading ao filtrar
        document.querySelector('.filtros').addEventListener('submit', function() {
            const btn = this.querySelector('.btn-filtrar');
            btn.innerHTML = '⏳ Filtrando...';
            btn.disabled = true;
        });
    </script>
</body>
</html>