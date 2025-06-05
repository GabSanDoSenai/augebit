<?php
session_start();

// Verificação de autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../conexao.php';

class DashboardData {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getProjectsData() {
        $query = "SELECT status, COUNT(*) AS total FROM projetos GROUP BY status";
        $result = $this->conn->query($query);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[$row['status']] = $row['total'];
        }
        
        return $data;
    }
    
    public function getTasksData() {
        $query = "SELECT status, COUNT(*) AS total FROM tarefas GROUP BY status";
        $result = $this->conn->query($query);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[$row['status']] = $row['total'];
        }
        
        return $data;
    }
    
    public function getUserCounts() {
        $funcionarios = $this->conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'funcionario'")->fetch_assoc()['total'];
        $clientes = $this->conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'cliente'")->fetch_assoc()['total'];
        
        return ['funcionarios' => $funcionarios, 'clientes' => $clientes];
    }
    
    public function getNotifications() {
        $projPendentes = $this->conn->query("SELECT COUNT(*) AS total FROM projetos WHERE status = 'pendente'")->fetch_assoc()['total'] ?? 0;
        $tarefasUrgentes = $this->conn->query("SELECT COUNT(*) AS total FROM tarefas WHERE status = 'a_fazer'")->fetch_assoc()['total'] ?? 0;
        $novosDocs = $this->conn->query("SELECT COUNT(*) AS total FROM uploads WHERE enviado_em >= NOW() - INTERVAL 3 DAY")->fetch_assoc()['total'] ?? 0;
        
        return [
            'projetos_pendentes' => $projPendentes,
            'tarefas_urgentes' => $tarefasUrgentes,
            'novos_documentos' => $novosDocs
        ];
    }
    
    public function getRecentProjects($limit = 5) {
        $query = "SELECT id, titulo, status, criado_em FROM projetos ORDER BY criado_em DESC LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        return $stmt->get_result();
    }
    
    public function getRecentTasks($limit = 5) {
        $query = "
            SELECT t.id, t.titulo, t.status, u.nome AS funcionario, p.titulo AS projeto
            FROM tarefas t
            LEFT JOIN usuarios u ON t.funcionario_id = u.id
            LEFT JOIN projetos p ON t.projeto_id = p.id
            ORDER BY t.criado_em DESC LIMIT ?
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        return $stmt->get_result();
    }
    
    public function getMonthlyProgress() {
        $query = "
            SELECT 
                DATE_FORMAT(criado_em, '%Y-%m') as mes,
                COUNT(*) as total_projetos,
                SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as projetos_concluidos
            FROM projetos 
            WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(criado_em, '%Y-%m')
            ORDER BY mes ASC
        ";
        
        return $this->conn->query($query);
    }
    
    public function getTaskCompletionRate() {
        $query = "
            SELECT 
                status,
                COUNT(*) as total
            FROM tarefas 
            GROUP BY status
        ";
        
        return $this->conn->query($query);
    }
}

// Inicialização dos dados
$dashboard = new DashboardData($conn);
$dadosProjetos = $dashboard->getProjectsData();
$dadosTarefas = $dashboard->getTasksData();
$dadosUsuarios = $dashboard->getUserCounts();
$notificacoes = $dashboard->getNotifications();
$projetosRecentes = $dashboard->getRecentProjects();
$tarefasRecentes = $dashboard->getRecentTasks();
$progressoMensal = $dashboard->getMonthlyProgress();
$taxaConclusao = $dashboard->getTaskCompletionRate();

// Preparar dados para gráficos
$progressoMensalData = [];
while ($row = $progressoMensal->fetch_assoc()) {
    $progressoMensalData[] = $row;
}

$taxaConclusaoData = [];
while ($row = $taxaConclusao->fetch_assoc()) {
    $taxaConclusaoData[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/gestor.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</h1>
            <p>Aqui está um resumo do seu sistema de gestão</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?= array_sum($dadosProjetos) ?></div>
                        <div class="stat-label">Total de Projetos</div>
                    </div>
                    <div class="stat-icon projects">📋</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?= array_sum($dadosTarefas) ?></div>
                        <div class="stat-label">Total de Tarefas</div>
                    </div>
                    <div class="stat-icon tasks">✅</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?= $dadosUsuarios['funcionarios'] ?></div>
                        <div class="stat-label">Funcionários</div>
                    </div>
                    <div class="stat-icon users">👥</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?= $dadosUsuarios['clientes'] ?></div>
                        <div class="stat-label">Clientes</div>
                    </div>
                    <div class="stat-icon docs">👤</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>📈 Progresso Mensal de Projetos</h3>
                <canvas id="progressoMensalChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>🎯 Status das Tarefas</h3>
                <canvas id="statusTarefasChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>📊 Distribuição de Projetos</h3>
                <canvas id="statusProjetosChart"></canvas>
            </div>
        </div>

        <!-- Notifications -->
        <?php if ($notificacoes['projetos_pendentes'] > 0 || $notificacoes['tarefas_urgentes'] > 0 || $notificacoes['novos_documentos'] > 0): ?>
        <div class="notifications">
            <h2>Notificações Importantes</h2>
            
            <?php if ($notificacoes['projetos_pendentes'] > 0): ?>
            <div class="notification-item urgent">
                <span><strong><?= $notificacoes['projetos_pendentes'] ?></strong> projeto(s) aguardando aprovação.</span>
                <a href="projetos.php?status=pendente" class="notification-link">Ver Projetos →</a>
            </div>
            <?php endif; ?>
            
            <?php if ($notificacoes['tarefas_urgentes'] > 0): ?>
            <div class="notification-item">
                <span><strong><?= $notificacoes['tarefas_urgentes'] ?></strong> tarefa(s) aguardando início.</span>
                <a href="tarefas/listar_tarefas.php?status=a_fazer" class="notification-link">Ver Tarefas →</a>
            </div>
            <?php endif; ?>
            
            <?php if ($notificacoes['novos_documentos'] > 0): ?>
            <div class="notification-item">
                <span><strong><?= $notificacoes['novos_documentos'] ?></strong> novo(s) documento(s) enviado(s) recentemente.</span>
                <a href="documentos/visualizar_documentos.php" class="notification-link">Ver Documentos →</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Recent Items Grid -->
        <div class="recent-grid">
            <div class="recent-section">
                <h3>📋 Projetos Recentes</h3>
                <?php while ($projeto = $projetosRecentes->fetch_assoc()): ?>
                <div class="recent-item">
                    <div>
                        <strong><?= htmlspecialchars($projeto['titulo']) ?></strong>
                        <br><small><?= date('d/m/Y', strtotime($projeto['criado_em'])) ?></small>
                    </div>
                    <span class="status-badge <?= $projeto['status'] ?>"><?= ucfirst(str_replace('_', ' ', $projeto['status'])) ?></span>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="recent-section">
                <h3>✅ Tarefas Recentes</h3>
                <?php while ($tarefa = $tarefasRecentes->fetch_assoc()): ?>
                <div class="recent-item">
                    <div>
                        <strong><?= htmlspecialchars($tarefa['titulo']) ?></strong>
                        <br><small><?= htmlspecialchars($tarefa['funcionario'] ?? 'Não atribuído') ?> - <?= htmlspecialchars($tarefa['projeto'] ?? 'Sem projeto') ?></small>
                    </div>
                    <span class="status-badge <?= $tarefa['status'] ?>"><?= ucfirst(str_replace('_', ' ', $tarefa['status'])) ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>🚀 Ações Rápidas</h3>
            <div class="action-grid">
                <a href="projetos/criar_projeto.php" class="action-btn">
                    ➕ Novo Projeto
                </a>
                <a href="cria_tarefa.php" class="action-btn">
                    ✅ Nova Tarefa
                </a>
                <a href="documentos/enviar_documento.php" class="action-btn">
                    📁 Enviar Documento
                </a>
                <a href="projetos/listar_projetos.php" class="action-btn">
                    ⚖️ Avaliar Projetos
                </a>
            </div>
        </div>
    </main>

    <script>
        // Dados para os gráficos
        const progressoMensalData = <?= json_encode($progressoMensalData) ?>;
        const taxaConclusaoData = <?= json_encode($taxaConclusaoData) ?>;
        const dadosProjetos = <?= json_encode($dadosProjetos) ?>;
        
        // Configuração padrão dos gráficos
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#666';
        
        // Gráfico de Progresso Mensal
        const ctxProgresso = document.getElementById('progressoMensalChart').getContext('2d');
        new Chart(ctxProgresso, {
            type: 'line',
            data: {
                labels: progressoMensalData.map(item => {
                    const [ano, mes] = item.mes.split('-');
                    return new Date(ano, mes - 1).toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Projetos Criados',
                    data: progressoMensalData.map(item => item.total_projetos),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Projetos Concluídos',
                    data: progressoMensalData.map(item => item.projetos_concluidos),
                    borderColor: '#43e97b',
                    backgroundColor: 'rgba(67, 233, 123, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });
        
        // Gráfico de Status das Tarefas
        const ctxTarefas = document.getElementById('statusTarefasChart').getContext('2d');
        new Chart(ctxTarefas, {
            type: 'doughnut',
            data: {
                labels: taxaConclusaoData.map(item => item.status.replace('_', ' ').toUpperCase()),
                datasets: [{
                    data: taxaConclusaoData.map(item => item.total),
                    backgroundColor: [
                        '#667eea',
                        '#43e97b',
                        '#fa709a',
                        '#fee140',
                        '#4facfe'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
        
        // Gráfico de Distribuição de Projetos
        const ctxProjetos = document.getElementById('statusProjetosChart').getContext('2d');
        new Chart(ctxProjetos, {
            type: 'bar',
            data: {
                labels: Object.keys(dadosProjetos).map(status => status.replace('_', ' ').toUpperCase()),
                datasets: [{
                    label: 'Quantidade',
                    data: Object.values(dadosProjetos),
                    backgroundColor: [
                        '#667eea',
                        '#43e97b',
                        '#fa709a',
                        '#fee140'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
    <script src="../js/dashboardCharts.js"></script>
</body>
</html>