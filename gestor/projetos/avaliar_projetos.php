<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliação de Projetos</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        .project-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .project-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pendente {
            background: #ffeaa7;
            color: #fdcb6e;
        }

        .status-aprovado {
            background: #d1f2eb;
            color: #00b894;
        }

        .status-em_andamento {
            background: #dbeafe;
            color: #3b82f6;
        }

        .status-ajustes {
            background: #fed7aa;
            color: #f97316;
        }

        .status-finalizado {
            background: #d1fae5;
            color: #059669;
        }

        .status-recusado {
            background: #fecaca;
            color: #dc2626;
        }

        .project-info {
            margin: 15px 0;
        }

        .project-info p {
            margin: 8px 0;
            color: #666;
        }

        .project-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-info {
            background: #3b82f6;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .filter-section h3 {
            margin-top: 0;
            color: #333;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .no-projects {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1em;
        }

        .project-description {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            line-height: 1.5;
        }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .meta-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }

        .meta-label {
            font-weight: bold;
            color: #333;
            font-size: 0.9em;
        }

        .meta-value {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <?php
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
            header("Location: ../login.php");
            exit;
        }

        include '../../conexao.php';

        $message = '';
        $message_type = '';

        // Processar ações
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $action = $_GET['action'];

            switch ($action) {
                case 'aprovar':
                    $stmt = $conn->prepare("UPDATE projetos SET status = 'aprovado' WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $message = "Projeto aprovado com sucesso!";
                        $message_type = 'success';
                    } else {
                        $message = "Erro ao aprovar projeto.";
                        $message_type = 'error';
                    }
                    break;

                case 'recusar':
                    $stmt = $conn->prepare("UPDATE projetos SET status = 'recusado' WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $message = "Projeto recusado.";
                        $message_type = 'success';
                    } else {
                        $message = "Erro ao recusar projeto.";
                        $message_type = 'error';
                    }
                    break;

                case 'iniciar':
                    $stmt = $conn->prepare("UPDATE projetos SET status = 'em_andamento' WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $message = "Projeto iniciado!";
                        $message_type = 'success';
                    } else {
                        $message = "Erro ao iniciar projeto.";
                        $message_type = 'error';
                    }
                    break;

                case 'solicitar_ajustes':
                    $stmt = $conn->prepare("UPDATE projetos SET status = 'ajustes' WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $message = "Solicitação de ajustes enviada.";
                        $message_type = 'success';
                    } else {
                        $message = "Erro ao solicitar ajustes.";
                        $message_type = 'error';
                    }
                    break;

                case 'finalizar':
                    $stmt = $conn->prepare("UPDATE projetos SET status = 'finalizado' WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $message = "Projeto finalizado!";
                        $message_type = 'success';
                    } else {
                        $message = "Erro ao finalizar projeto.";
                        $message_type = 'error';
                    }
                    break;

                case 'reabrir':
                    $stmt = $conn->prepare("UPDATE projetos SET status = 'em_andamento' WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $message = "Projeto reaberto!";
                        $message_type = 'success';
                    } else {
                        $message = "Erro ao reabrir projeto.";
                        $message_type = 'error';
                    }
                    break;
            }
        }

        // Filtro de status
        $status_filter = isset($_GET['status']) ? $_GET['status'] : 'todos';
        $status_conditions = [
            'todos' => '',
            'pendente' => "WHERE p.status = 'pendente'",
            'aprovado' => "WHERE p.status = 'aprovado'",
            'em_andamento' => "WHERE p.status = 'em_andamento'",
            'ajustes' => "WHERE p.status = 'ajustes'",
            'finalizado' => "WHERE p.status = 'finalizado'",
            'recusado' => "WHERE p.status = 'recusado'"
        ];

        $where_clause = isset($status_conditions[$status_filter]) ? $status_conditions[$status_filter] : '';

        // Buscar projetos
        $query = "SELECT p.id, p.titulo, p.descricao, p.status, u.nome AS cliente, u.email AS cliente_email
                  FROM projetos p
                  JOIN usuarios u ON p.cliente_id = u.id
                  $where_clause
                  ORDER BY 
    CASE p.status
        WHEN 'pendente' THEN 1
        WHEN 'ajustes' THEN 2
        WHEN 'em_andamento' THEN 3
        WHEN 'aprovado' THEN 4
        WHEN 'finalizado' THEN 5
        WHEN 'recusado' THEN 6
    END
";

        $result = $conn->query($query);

        // Contar projetos por status
        $status_counts = [];
        $count_query = "SELECT status, COUNT(*) as count FROM projetos GROUP BY status";
        $count_result = $conn->query($count_query);
        while ($row = $count_result->fetch_assoc()) {
            $status_counts[$row['status']] = $row['count'];
        }

        function getStatusBadge($status)
        {
            $status_labels = [
                'pendente' => 'Pendente',
                'aprovado' => 'Aprovado',
                'em_andamento' => 'Em Andamento',
                'ajustes' => 'Ajustes',
                'finalizado' => 'Finalizado',
                'recusado' => 'Recusado'
            ];

            $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
            return "<span class='status-badge status-$status'>$label</span>";
        }

        function getActionButtons($project)
        {
            $buttons = [];
            $id = $project['id'];
            $status = $project['status'];

            switch ($status) {
                case 'pendente':
                    $buttons[] = "<a href='?action=aprovar&id=$id' class='btn btn-success'>✅ Aprovar</a>";
                    $buttons[] = "<a href='?action=recusar&id=$id' class='btn btn-danger' onclick='return confirm(\"Tem certeza que deseja recusar este projeto?\")'>❌ Recusar</a>";
                    break;

                case 'aprovado':
                    $buttons[] = "<a href='?action=iniciar&id=$id' class='btn btn-info'>🚀 Iniciar Projeto</a>";
                    $buttons[] = "<a href='?action=recusar&id=$id' class='btn btn-danger' onclick='return confirm(\"Tem certeza que deseja recusar este projeto?\")'>❌ Recusar</a>";
                    break;

                case 'em_andamento':
                    $buttons[] = "<a href='?action=solicitar_ajustes&id=$id' class='btn btn-warning'>⚠️ Solicitar Ajustes</a>";
                    $buttons[] = "<a href='?action=finalizar&id=$id' class='btn btn-success'>✅ Finalizar</a>";
                    break;

                case 'ajustes':
                    $buttons[] = "<a href='?action=iniciar&id=$id' class='btn btn-info'>↩️ Retomar</a>";
                    $buttons[] = "<a href='?action=recusar&id=$id' class='btn btn-danger' onclick='return confirm(\"Tem certeza que deseja recusar este projeto?\")'>❌ Recusar</a>";
                    break;

                case 'finalizado':
                    $buttons[] = "<a href='?action=reabrir&id=$id' class='btn btn-secondary' onclick='return confirm(\"Tem certeza que deseja reabrir este projeto?\")'>🔄 Reabrir</a>";
                    break;

                case 'recusado':
                    $buttons[] = "<a href='?action=aprovar&id=$id' class='btn btn-success'>✅ Aprovar</a>";
                    break;
            }

            return implode(' ', $buttons);
        }
        ?>

        <h1>Avaliação de Projetos</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filter-section">
            <h3>Filtrar por Status</h3>
            <div class="filter-buttons">
                <a href="?status=todos" class="filter-btn <?php echo $status_filter === 'todos' ? 'active' : ''; ?>">
                    Todos (<?php echo $result->num_rows; ?>)
                </a>
                <a href="?status=pendente"
                    class="filter-btn <?php echo $status_filter === 'pendente' ? 'active' : ''; ?>">
                    Pendente (<?php echo isset($status_counts['pendente']) ? $status_counts['pendente'] : 0; ?>)
                </a>
                <a href="?status=aprovado"
                    class="filter-btn <?php echo $status_filter === 'aprovado' ? 'active' : ''; ?>">
                    Aprovado (<?php echo isset($status_counts['aprovado']) ? $status_counts['aprovado'] : 0; ?>)
                </a>
                <a href="?status=em_andamento"
                    class="filter-btn <?php echo $status_filter === 'em_andamento' ? 'active' : ''; ?>">
                    Em Andamento
                    (<?php echo isset($status_counts['em_andamento']) ? $status_counts['em_andamento'] : 0; ?>)
                </a>
                <a href="?status=ajustes"
                    class="filter-btn <?php echo $status_filter === 'ajustes' ? 'active' : ''; ?>">
                    Ajustes (<?php echo isset($status_counts['ajustes']) ? $status_counts['ajustes'] : 0; ?>)
                </a>
                <a href="?status=finalizado"
                    class="filter-btn <?php echo $status_filter === 'finalizado' ? 'active' : ''; ?>">
                    Finalizado (<?php echo isset($status_counts['finalizado']) ? $status_counts['finalizado'] : 0; ?>)
                </a>
                <a href="?status=recusado"
                    class="filter-btn <?php echo $status_filter === 'recusado' ? 'active' : ''; ?>">
                    Recusado (<?php echo isset($status_counts['recusado']) ? $status_counts['recusado'] : 0; ?>)
                </a>
            </div>
        </div>

        <!-- Lista de Projetos -->
        <?php if ($result->num_rows === 0): ?>
            <div class="no-projects">
                <h3>Nenhum projeto encontrado</h3>
                <p>Não há projetos <?php echo $status_filter !== 'todos' ? "com status '$status_filter'" : ''; ?> no
                    momento.</p>
            </div>
        <?php else: ?>
            <?php while ($project = $result->fetch_assoc()): ?>
                <div class="project-card">
                    <div class="project-header">
                        <div class="project-title"><?php echo htmlspecialchars($project['titulo']); ?></div>
                        <?php echo getStatusBadge($project['status']); ?>
                    </div>

                    <div class="project-meta">
                        <div class="meta-item">
                            <div class="meta-label">Cliente</div>
                            <div class="meta-value"><?php echo htmlspecialchars($project['cliente']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Email</div>
                            <div class="meta-value"><?php echo htmlspecialchars($project['cliente_email']); ?></div>
                        </div>

                        <div class="meta-item">
                            <div class="meta-label">ID do Projeto</div>
                            <div class="meta-value">#<?php echo $project['id']; ?></div>
                        </div>
                    </div>

                    <div class="project-description">
                        <strong>Descrição:</strong><br>
                        <?php echo nl2br(htmlspecialchars($project['descricao'])); ?>
                    </div>

                    <div class="project-actions">
                        <?php echo getActionButtons($project); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>

</html>