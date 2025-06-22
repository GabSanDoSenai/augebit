<?php
session_start();
require_once '../../conexao.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

// Processar exclusão de funcionário
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $funcionario_id = (int)$_GET['excluir'];

    // Verificar se o funcionário existe
    $stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ? AND tipo = 'funcionario'");
    $stmt->bind_param("i", $funcionario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $funcionario = $resultado->fetch_assoc();

        // Verificar se o funcionário tem projetos ou tarefas associadas
        $stmt_check = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM projetos_usuarios WHERE funcionario_id = ?) as projetos,
                (SELECT COUNT(*) FROM tarefas WHERE funcionario_id = ?) as tarefas
        ");
        $stmt_check->bind_param("ii", $funcionario_id, $funcionario_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        $check_data = $check_result->fetch_assoc();

        if ($check_data['projetos'] > 0 || $check_data['tarefas'] > 0) {
            $mensagem = "Não é possível excluir o funcionário " . htmlspecialchars($funcionario['nome']) . " pois ele possui projetos ou tarefas associadas.";
            $tipo_mensagem = 'erro';
        } else {
            // Excluir funcionário
            $stmt_delete = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'funcionario'");
            $stmt_delete->bind_param("i", $funcionario_id);

            if ($stmt_delete->execute()) {
                $mensagem = "Funcionário " . htmlspecialchars($funcionario['nome']) . " excluído com sucesso!";
                $tipo_mensagem = 'sucesso';
            } else {
                $mensagem = "Erro ao excluir funcionário: " . $conn->error;
                $tipo_mensagem = 'erro';
            }
            $stmt_delete->close();
        }
        $stmt_check->close();
    } else {
        $mensagem = "Funcionário não encontrado.";
        $tipo_mensagem = 'erro';
    }
    $stmt->close();
}

// Buscar todos os funcionários com informações adicionais
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        u.nome, 
        u.email, 
        u.criado_em,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id) AS total_tarefas,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id AND t.status = 'em_progresso') AS em_progresso,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id AND t.status = 'concluido') AS concluidas,
        (SELECT COUNT(*) FROM projetos_usuarios pu WHERE pu.funcionario_id = u.id) AS total_projetos
    FROM usuarios u
    WHERE u.tipo = 'funcionario'
    ORDER BY u.nome
");
$stmt->execute();
$funcionarios = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Funcionários - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        /* Estilos para mensagens de feedback */
        .message {
            padding: 18px 24px;
            margin-bottom: 28px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(153, 153, 255, 0.15);
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .message::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #9999FF, #7777FF);
        }

        .message.success {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            color: #4a4aff;
            border-left: 4px solid #6666ff;
        }

        .message.success::before {
            background: linear-gradient(180deg, #66ff66, #44dd44);
        }

        .message.error {
            background: linear-gradient(135deg, #fff8f8 0%, #fff0f0 100%);
            color: #dd4444;
            border-left: 4px solid #ff6666;
        }

        .message.error::before {
            background: linear-gradient(180deg, #ff6666, #dd4444);
        }

        /* Estilos para as pílulas de estatísticas dos funcionários */
        .funcionario-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            background: linear-gradient(135deg, #f0f2ff 0%, #e6e9ff 100%);
            color: #5555dd;
            border: 1px solid #ccccff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-pill::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .stat-pill:hover {
            background: linear-gradient(135deg, #e6e9ff 0%, #d9ddff 100%);
            border-color: #9999ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(153, 153, 255, 0.25);
        }

        .stat-pill:hover::before {
            left: 100%;
        }

        .stat-pill svg {
            width: 14px;
            height: 14px;
            opacity: 0.8;
        }

        /* Melhorias na tabela */
        .table-container {
            background: linear-gradient(135deg, #ffffff 0%, #fafbff 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(153, 153, 255, 0.1);
            overflow: hidden;
            border: 1px solid #e6e9ff;
        }

        .table-responsive table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-responsive thead {
            background: linear-gradient(135deg, #9999ff 0%, #7777ff 100%);
        }

        .table-responsive thead th {
            padding: 16px 20px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .table-responsive tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f2ff;
        }

        .table-responsive tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            transform: translateX(2px);
        }

        .table-responsive tbody tr:last-child {
            border-bottom: none;
        }

        .table-responsive tbody td {
            padding: 16px 20px;
            color: #4a4a7a;
            font-size: 14px;
            border: none;
        }

        .table-responsive tbody td strong {
            color: #333366;
            font-weight: 600;
        }

        /* Melhorias nos botões de ação */
        .table-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .action-link img {
            width: 18px;
            height: 18px;
            transition: all 0.3s ease;
            filter: brightness(1.1);
        }

        .action-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .action-link:hover::before {
            opacity: 1;
        }

        .action-link:hover img {
            transform: scale(1.1);
            filter: brightness(1.3);
        }

        .action-view {
            background: linear-gradient(135deg, #e6e9ff 0%, #d9ddff 100%);
            color: #5555dd;
            border: 1px solid #ccccff;
        }

        .action-view:hover {
            background: linear-gradient(135deg, #d9ddff 0%, #ccccff 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(153, 153, 255, 0.3);
        }

        .action-delete {
            background: linear-gradient(135deg, #ffe6e6 0%, #ffd9d9 100%);
            color: #dd4444;
            border: 1px solid #ffcccc;
        }

        .action-delete:hover {
            background: linear-gradient(135deg, #ffd9d9 0%, #ffcccc 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 102, 102, 0.3);
        }

        /* Melhorias no cabeçalho */
        .header-actions h1 {
            color: #333366;
            font-weight: 700;
            font-size: 28px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #9999ff 0%, #7777ff 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            box-shadow: 0 4px 16px rgba(153, 153, 255, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #7777ff 0%, #5555ff 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(153, 153, 255, 0.4);
        }

        /* Animações */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

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

        /* Tooltip melhorado */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #333366 0%, #444477 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            animation: tooltipFadeIn 0.2s ease;
        }

        [data-tooltip]:hover::before {
            content: '';
            position: absolute;
            bottom: calc(100% + 2px);
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: #333366;
            z-index: 1000;
        }

        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        /* Estado vazio da tabela */
        .table-responsive tbody td[colspan] {
            text-align: center;
            padding: 60px 40px;
            background: linear-gradient(135deg, #fafbff 0%, #f5f7ff 100%);
        }

        .table-responsive tbody td[colspan] h3 {
            color: #5555dd;
            margin-bottom: 12px;
            font-size: 20px;
        }

        .table-responsive tbody td[colspan] p {
            color: #7777aa;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <div class="conteudo-principal">

            <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h1 style="margin: 0;">Gerenciar Funcionários</h1>
                <a href="adicionar_funcionario.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px;">
                        <path d="M8 8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 1 0v2A.5.5 0 0 1 8 8m.5 0a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4z" />
                    </svg>
                    Novo Funcionário
                </a>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="message <?= $classe_mensagem ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <div class="table-container fade-in">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Funcionário</th>
                                <th>Contato</th>
                                <th>Estatísticas</th>
                                <th>Data de Cadastro</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($funcionarios->num_rows > 0): ?>
                                <?php while ($f = $funcionarios->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($f['nome']) ?></strong></td>
                                        <td><?= htmlspecialchars($f['email']) ?></td>
                                        <td>
                                            <div class="funcionario-stats">
                                                <span class="stat-pill" title="<?= $f['total_projetos'] ?> projetos atribuídos">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M4.5 9a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5" />
                                                        <path d="M2 1a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zm12 1v2H2V2zm0 3v7H2V5z" />
                                                    </svg>
                                                    <?= $f['total_projetos'] ?>
                                                </span>
                                                <span class="stat-pill" title="<?= $f['total_tarefas'] ?> tarefas no total">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M5.5 2.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0v-1a.5.5 0 0 1 .5-.5m5 0a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0v-1a.5.5 0 0 1 .5-.5" />
                                                        <path d="M1.886.511a1.745 1.745 0 0 1 2.614.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.614l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z" />
                                                    </svg>
                                                    <?= $f['total_tarefas'] ?>
                                                </span>
                                                <span class="stat-pill" title="<?= $f['concluidas'] ?> tarefas concluídas">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093z" />
                                                    </svg>
                                                    <?= $f['concluidas'] ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($f['criado_em'])) ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="../tarefas/listar_tarefas.php?funcionario=<?= $f['id'] ?>" class="action-link action-view" data-tooltip="Ver Tarefas">
                                                    <img src="../../assets/img/icones/NovaTarefa.png" alt="Ver Tarefas">
                                                </a>
                                                <a href="?excluir=<?= $f['id'] ?>" class="action-link action-delete" data-tooltip="Excluir" onclick="return confirm('Tem certeza que deseja excluir o funcionário <?= htmlspecialchars($f['nome'], ENT_QUOTES) ?>?\n\nEsta ação não pode ser desfeita.')">
                                                    <img src="../../assets/img/icones/logout.png" alt="Excluir">
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div style="padding: 40px; text-align: center; color: var(--text-light);">
                                            <h3 style="color: var(--text-dark);">Nenhum funcionário cadastrado</h3>
                                            <p>Parece que você ainda não tem funcionários no sistema.<br>Clique no botão "Novo Funcionário" para começar a montar sua equipe.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Script para remover a mensagem de feedback após alguns segundos
        document.addEventListener('DOMContentLoaded', function() {
            const mensagem = document.querySelector('.message');
            if (mensagem) {
                setTimeout(() => {
                    mensagem.style.transition = 'opacity 0.5s ease';
                    mensagem.style.opacity = '0';
                    setTimeout(() => mensagem.remove(), 500);
                }, 5000); // A mensagem some após 5 segundos
            }
        });
    </script>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>