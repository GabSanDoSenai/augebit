listar projetos gestor <?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require '../../conexao.php';

// Processar atualização de status via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $projeto_id = (int)$_POST['projeto_id'];
    $novo_status = $_POST['status'];
    
    // Validar status
    $status_validos = ['pendente', 'aprovado', 'em_andamento', 'finalizado', 'ajustes'];
    if (in_array($novo_status, $status_validos)) {
        $stmt = $conn->prepare("UPDATE projetos SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $novo_status, $projeto_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Status inválido.']);
    }
    exit;
}

// Processar exclusão de projeto
if (isset($_POST['action']) && $_POST['action'] === 'delete_project') {
    $projeto_id = (int)$_POST['projeto_id'];
    
    // Excluir relacionamentos primeiro
    $conn->query("DELETE FROM projetos_usuarios WHERE projeto_id = $projeto_id");
    $conn->query("DELETE FROM entregas WHERE projeto_id = $projeto_id");
    
    // Excluir projeto
    $stmt = $conn->prepare("DELETE FROM projetos WHERE id = ?");
    $stmt->bind_param("i", $projeto_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Projeto excluído com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir projeto.']);
    }
    exit;
}

// Busca de projetos
$busca = $_GET['busca'] ?? '';
$status_filtro = $_GET['status'] ?? '';

$sql = "SELECT DISTINCT p.id, p.titulo, p.descricao, p.status, p.data_inicio, p.data_fim, p.criado_em, 
               CASE 
                   WHEN u.tipo = 'admin' OR u.id IS NULL THEN 'Sistema'
                   ELSE u.nome 
               END as cliente_nome
        FROM projetos p 
        LEFT JOIN usuarios u ON p.cliente_id = u.id 
        WHERE 1=1";

$params = [];
$types = '';

if ($busca !== '') {
    $sql .= " AND p.titulo LIKE ?";
    $params[] = '%' . $busca . '%';
    $types .= 's';
}

if ($status_filtro !== '') {
    $sql .= " AND p.status = ?";
    $params[] = $status_filtro;
    $types .= 's';
}

$sql .= " ORDER BY p.criado_em DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Contadores por status
$stats_result = $conn->query("SELECT status, COUNT(*) as total FROM projetos GROUP BY status");
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['status']] = $row['total'];
}
?>

<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Projetos</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        /* Definições da fonte Poppins */
        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-SemiBold.ttf') format('truetype');
            font-weight: 600;
        }
           
        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-Regular.ttf') format('truetype');
            font-weight: 450;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-Medium.ttf') format('truetype');
            font-weight: 500;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-Italic.ttf') format('truetype');
            font-weight: 400;
            font-style: italic;
        }
        
        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-ExtraLight.ttf') format('truetype');
            font-weight: 200;
        }

        /* Estilos gerais com Poppins */
        body {
            font-family: 'Poppins', sans-serif;
            font-weight: 450;
            color: #333;
        }

        .main-content {
            padding: 20px;
            max-width: 1400px;
            font-family: 'Poppins', sans-serif;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #3E236A;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-filters {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-filters input, .search-filters select {
            padding: 8px 12px;
            border: 1px solid #9999FF;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .search-filters button {
            padding: 8px 15px;
            background: #3E236A;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .search-filters button:hover {
            background: #2A1848;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-card.pendente { border-left-color: #9999FF; }
        .stat-card.aprovado { border-left-color: #3E236A; }
        .stat-card.em_andamento { border-left-color: #7A5CFF; }
        .stat-card.finalizado { border-left-color: #B399FF; }
        .stat-card.ajustes { border-left-color: #3E236A; }
        
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: #3E236A;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .projects-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid rgba(62, 35, 106, 0.1);
        }
        
        .projects-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .projects-table th {
            background: #F5F5FF;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #3E236A;
            border-bottom: 2px solid #9999FF;
        }
        
        .projects-table td {
            padding: 12px;
            border-bottom: 1px solid #E6E6FF;
            vertical-align: middle;
        }
        
        .projects-table tr:hover {
            background-color: #F9F9FF;
        }
        
        .status-select {
            padding: 6px 10px;
            border: 1px solid #9999FF;
            border-radius: 4px;
            font-size: 13px;
            min-width: 120px;
            font-family: 'Poppins', sans-serif;
            background: #F5F5FF;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pendente { background: #E6E6FF; color: #3E236A; }
        .status-aprovado { background: #D6D1FF; color: #3E236A; }
        .status-em_andamento { background: #C2B8FF; color: #3E236A; }
        .status-finalizado { background: #B399FF; color: white; }
        .status-ajustes { background:rgba(62, 35, 106, 0.67); color: #3E236A;}
        
        .actions-cell {
            white-space: nowrap;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }
        
        .btn-primary { background: #3E236A; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #3E236A; color: white; }
        .btn-info { background: #9999FF; color: white; }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .project-title {
            font-weight: 600;
            color: #3E236A;
        }
        
        .project-description {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        .client-name {
            font-size: 12px;
            color: #666;
        }
        
        .date-info {
            font-size: 12px;
            color: #666;
        }
        
        .loading {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .message.success {
            background: #E6FFE6;
            color: #155724;
            border: 1px solid #9999FF;
        }
        
        .message.error {
            background:rgba(62, 35, 106, 0.66);
            color: #3E236A;
            border: 1px solidrgb(97, 69, 142);
        }
        
        /* Botão secundário */
        .btn-secondary {
            background: #9999FF;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7A5CFF;
        }
        
        /* Efeitos de hover para cards */
        .stat-card:hover {
            box-shadow: 0 5px 15px rgba(153, 153, 255, 0.2);
        }
        
        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-filters {
                justify-content: center;
            }
            
            .projects-table {
                overflow-x: auto;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .btn {
                padding: 8px;
                font-size: 11px;
                margin: 1px;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header-section">
            <h2>Gestão de Projetos</h2>
            
            <form method="get" class="search-filters">
                <input type="text" name="busca" placeholder="Buscar por título..." 
                       value="<?= htmlspecialchars($busca) ?>">
                       
                <select name="status">
                    <option value="">Todos os Status</option>
                    <option value="pendente" <?= $status_filtro === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    <option value="aprovado" <?= $status_filtro === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                    <option value="em_andamento" <?= $status_filtro === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="finalizado" <?= $status_filtro === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                    <option value="ajustes" <?= $status_filtro === 'ajustes' ? 'selected' : '' ?>>Ajustes</option>
                </select>
                
                <button type="submit"> Filtrar</button>
                
                <?php if ($busca || $status_filtro): ?>
                    <a href="?" class="btn btn-secondary">Limpar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Cards de Estatísticas -->
        <div class="stats-cards">
            <div class="stat-card pendente">
                <div class="stat-number"><?= $stats['pendente'] ?? 0 ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-card aprovado">
                <div class="stat-number"><?= $stats['aprovado'] ?? 0 ?></div>
                <div class="stat-label">Aprovados</div>
            </div>
            <div class="stat-card em_andamento">
                <div class="stat-number"><?= $stats['em_andamento'] ?? 0 ?></div>
                <div class="stat-label">Em Andamento</div>
            </div>
            <div class="stat-card finalizado">
                <div class="stat-number"><?= $stats['finalizado'] ?? 0 ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
            <div class="stat-card ajustes">
                <div class="stat-number"><?= $stats['ajustes'] ?? 0 ?></div>
                <div class="stat-label">Ajustes</div>
            </div>
        </div>
        
        <!-- Mensagem de feedback -->
        <div id="message-container"></div>
        
        <!-- Tabela de Projetos -->
        <div class="projects-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Projeto</th>
                        <th>Cliente</th>
                        <th>Status</th>
                        <th>Prazo</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($projeto = $result->fetch_assoc()): ?>
                            <tr data-project-id="<?= $projeto['id'] ?>">
                                <td><strong>#<?= $projeto['id'] ?></strong></td>
                                <td>
                                    <div class="project-title"><?= htmlspecialchars($projeto['titulo']) ?></div>
                                    <?php if ($projeto['descricao']): ?>
                                        <div class="project-description">
                                            <?= htmlspecialchars(substr($projeto['descricao'], 0, 100)) ?>
                                            <?= strlen($projeto['descricao']) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="client-name">
                                        <?= $projeto['cliente_nome'] ? htmlspecialchars($projeto['cliente_nome']) : 'N/A' ?>
                                    </div>
                                </td>
                                <td>
                                    <select class="status-select" data-project-id="<?= $projeto['id'] ?>" 
                                            data-current-status="<?= $projeto['status'] ?>">
                                        <option value="pendente" <?= $projeto['status'] === 'pendente' ? 'selected' : '' ?>>
                                            Pendente
                                        </option>
                                        <option value="aprovado" <?= $projeto['status'] === 'aprovado' ? 'selected' : '' ?>>
                                            Aprovado
                                        </option>
                                        <option value="em_andamento" <?= $projeto['status'] === 'em_andamento' ? 'selected' : '' ?>>
                                            Em Andamento
                                        </option>
                                        <option value="finalizado" <?= $projeto['status'] === 'finalizado' ? 'selected' : '' ?>>
                                            Finalizado
                                        </option>
                                        <option value="ajustes" <?= $projeto['status'] === 'ajustes' ? 'selected' : '' ?>>
                                            Ajustes
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <?php if ($projeto['data_inicio'] || $projeto['data_fim']): ?>
                                        <div class="date-info">
                                            <?php if ($projeto['data_inicio']): ?>
                                                <div>Início: <?= date('d/m/Y', strtotime($projeto['data_inicio'])) ?></div>
                                            <?php endif; ?>
                                            <?php if ($projeto['data_fim']): ?>
                                                <div>Fim: <?= date('d/m/Y', strtotime($projeto['data_fim'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <?= date('d/m/Y H:i', strtotime($projeto['criado_em'])) ?>
                                    </div>
                                </td>
                                <td class="actions-cell">
                                    <a href="ver_tarefas.php?projeto_id=<?= $projeto['id'] ?>" 
                                       class="btn btn-info" title="Ver Tarefas">
                                        Tarefas
                                    </a>
                                    
                                    <a href="ver_documentos.php?projeto_id=<?= $projeto['id'] ?>" 
                                       class="btn btn-primary" title="Ver Documentos">
                                        Docs
                                    </a>
                                    
                                    <button class="btn btn-danger delete-btn" 
                                            data-project-id="<?= $projeto['id'] ?>"
                                            data-project-title="<?= htmlspecialchars($projeto['titulo']) ?>"
                                            title="Excluir Projeto">
                                         Excluir
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                <?php if ($busca || $status_filtro): ?>
                                    Nenhum projeto encontrado com os filtros aplicados.
                                <?php else: ?>
                                    Nenhum projeto cadastrado ainda.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Função para mostrar mensagens
        function showMessage(message, type = 'success') {
            const container = document.getElementById('message-container');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            
            container.innerHTML = '';
            container.appendChild(messageDiv);
            
            // Auto-hide após 5 segundos
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Atualização de status
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const projectId = this.dataset.projectId;
                const newStatus = this.value;
                const currentStatus = this.dataset.currentStatus;
                
                if (newStatus === currentStatus) return;
                
                const row = this.closest('tr');
                row.classList.add('loading');
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_status&projeto_id=${projectId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    row.classList.remove('loading');
                    
                    if (data.success) {
                        showMessage(data.message, 'success');
                        this.dataset.currentStatus = newStatus;
                        
                        // Atualizar contadores (recarregar página após 1 segundo)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showMessage(data.message, 'error');
                        this.value = currentStatus; // Reverter mudança
                    }
                })
                .catch(error => {
                    row.classList.remove('loading');
                    showMessage('Erro ao atualizar status. Tente novamente.', 'error');
                    this.value = currentStatus; // Reverter mudança
                });
            });
        });

        // Exclusão de projetos
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const projectId = this.dataset.projectId;
                const projectTitle = this.dataset.projectTitle;
                
                if (!confirm(`Tem certeza que deseja excluir o projeto "${projectTitle}"?\n\nEsta ação não pode ser desfeita e irá remover:\n- O projeto\n- Todas as tarefas relacionadas\n- Todas as entregas relacionadas`)) {
                    return;
                }
                
                const row = this.closest('tr');
                row.classList.add('loading');
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_project&projeto_id=${projectId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        
                        setTimeout(() => {
                            row.remove();
                            // Recarregar para atualizar contadores
                            window.location.reload();
                        }, 300);
                    } else {
                        row.classList.remove('loading');
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    row.classList.remove('loading');
                    showMessage('Erro ao excluir projeto. Tente novamente.', 'error');
                });
            });
        });

        // Auto-submit do formulário de busca com delay
        let searchTimeout;
        document.querySelector('input[name="busca"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 800);
        });
    </script>
</body>
</html>