<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include 'conexao.php';

// Coleta de dados para o dashboard
$projetos = $conn->query("SELECT status, COUNT(*) AS total FROM projetos GROUP BY status");
$dadosProjetos = [];
while ($p = $projetos->fetch_assoc()) {
    $dadosProjetos[$p['status']] = $p['total'];
}

$tarefas = $conn->query("SELECT status, COUNT(*) AS total FROM tarefas GROUP BY status");
$dadosTarefas = [];
while ($t = $tarefas->fetch_assoc()) {
    $dadosTarefas[$t['status']] = $t['total'];
}

$totalFuncionarios = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'funcionario'")->fetch_assoc()['total'];
$totalClientes = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'cliente'")->fetch_assoc()['total'];

// Notificações
$projPendentes = $conn->query("SELECT COUNT(*) AS total FROM projetos WHERE status = 'pendente'")->fetch_assoc()['total'] ?? 0;
$tarefasUrgentes = $conn->query("SELECT COUNT(*) AS total FROM tarefas WHERE status = 'a_fazer'")->fetch_assoc()['total'] ?? 0;
$novosDocs = $conn->query("SELECT COUNT(*) AS total FROM uploads WHERE enviado_em >= NOW() - INTERVAL 3 DAY")->fetch_assoc()['total'] ?? 0;

// Projetos recentes
$projetosRecentes = $conn->query("SELECT id, titulo, status, criado_em FROM projetos ORDER BY criado_em DESC LIMIT 5");

// Tarefas recentes
$tarefasRecentes = $conn->query("
    SELECT t.id, t.titulo, t.status, u.nome AS funcionario, p.titulo AS projeto
    FROM tarefas t
    LEFT JOIN usuarios u ON t.funcionario_id = u.id
    LEFT JOIN projetos p ON t.projeto_id = p.id
    ORDER BY t.criado_em DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema AugeBit</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
            padding: 2rem 0;
            z-index: 1000;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 2rem;
        }

        .logo h1 {
            color: #667eea;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .user-info {
            text-align: center;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .user-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .user-info span {
            color: #666;
            font-size: 0.9rem;
        }

        .nav-menu {
            list-style: none;
            padding: 0 1rem;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #555;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }

        .nav-link i {
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.projects { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.tasks { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .stat-icon.users { background: linear-gradient(135deg, #fa709a, #fee140); }
        .stat-icon.docs { background: linear-gradient(135deg, #a8edea, #fed6e3); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        .notifications {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .notifications h2 {
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .notifications h2::before {
            content: "🔔";
            margin-right: 0.5rem;
        }

        .notification-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .notification-item.urgent {
            border-left-color: #ff6b6b;
            background: #fff5f5;
        }

        .recent-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .recent-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .recent-section h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .recent-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9ff;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .recent-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pendente { background: #fff3cd; color: #856404; }
        .status-badge.aprovado { background: #d4edda; color: #155724; }
        .status-badge.em_andamento { background: #cce5ff; color: #004085; }
        .status-badge.finalizado { background: #d1ecf1; color: #0c5460; }
        .status-badge.a_fazer { background: #fff3cd; color: #856404; }
        .status-badge.em_progresso { background: #cce5ff; color: #004085; }
        .status-badge.concluido { background: #d4edda; color: #155724; }

        .quick-actions {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .quick-actions h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .recent-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="logo">
            <h1>AugeBit</h1>
        </div>
        
        <div class="user-info">
            <h3><?= htmlspecialchars($_SESSION['usuario_nome']) ?></h3>
            <span>Gestor do Sistema</span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard_gestor.php" class="nav-link active">
                    <span>📊</span> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="projetos.php" class="nav-link">
                    <span>📋</span> Projetos
                </a>
            </li>
            <li class="nav-item">
                <a href="/gestor/tarefas/listar_tarefas.php" class="nav-link">
                    <span>✅</span> Tarefas
                </a>
            </li>
            <li class="nav-item">
                <a href="funcionarios.php" class="nav-link">
                    <span>👥</span> Funcionários
                </a>
            </li>
            <li class="nav-item">
                <a href="gestor/projetos/ver_documentos.php" class="nav-link">
                    <span>📁</span> Documentos
                </a>
            </li>
            <li class="nav-item">
                <a href="/gestor/projetos/avaliar_projetos.php" class="nav-link">
                    <span>📈</span> Relatórios
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <span>🚪</span> Sair
                </a>
            </li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</h1>
            <p>Aqui está um resumo do seu sistema de gestão</p>
        </div>

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
                        <div class="stat-number"><?= $totalFuncionarios ?></div>
                        <div class="stat-label">Funcionários</div>
                    </div>
                    <div class="stat-icon users">👥</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?= $totalClientes ?></div>
                        <div class="stat-label">Clientes</div>
                    </div>
                    <div class="stat-icon docs">👤</div>
                </div>
            </div>
        </div>

        <?php if ($projPendentes > 0 || $tarefasUrgentes > 0 || $novosDocs > 0): ?>
        <div class="notifications">
            <h2>Notificações Importantes</h2>
            
            <?php if ($projPendentes > 0): ?>
            <div class="notification-item urgent">
                <span><strong><?= $projPendentes ?></strong> projeto(s) aguardando aprovação.</span>
                <a href="projetos.php?status=pendente" style="margin-left: auto; color: #667eea; text-decoration: none; font-weight: 600;">Ver Projetos →</a>
            </div>
            <?php endif; ?>
            
            <?php if ($tarefasUrgentes > 0): ?>
            <div class="notification-item">
                <span><strong><?= $tarefasUrgentes ?></strong> tarefa(s) aguardando início.</span>
                <a href="tarefas.php?status=a_fazer" style="margin-left: auto; color: #667eea; text-decoration: none; font-weight: 600;">Ver Tarefas →</a>
            </div>
            <?php endif; ?>
            
            <?php if ($novosDocs > 0): ?>
            <div class="notification-item">
                <span><strong><?= $novosDocs ?></strong> novo(s) documento(s) enviado(s) recentemente.</span>
                <a href="documentos.php" style="margin-left: auto; color: #667eea; text-decoration: none; font-weight: 600;">Ver Documentos →</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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

        <div class="quick-actions">
            <h3>🚀 Ações Rápidas</h3>
            <div class="action-grid">
                <a href="gestor/criar_projeto.php" class="action-btn">
                    ➕ Novo Projeto
                </a>
                <a href="tarefas/cria_tarefa.php" class="action-btn">
                    ✅ Nova Tarefa
                </a>
                <a href="gestor/documentos/enviar_documento.php" class="action-btn">
                    📁 Enviar Documento
                </a>
                <a href="gestor/projetos/listar_projetos.php" class="action-btn">
                    ⚖️ Avaliar Projetos
                </a>
            </div>
        </div>
    </main>
</body>
</html>