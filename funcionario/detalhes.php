
<?php
// gestor/tarefas/detalhes.php - Página de detalhes da tarefa


require '../../conexao.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: index.php");
    exit;
}

// Buscar detalhes da tarefa
$sql = "
    SELECT 
        t.*,
        p.titulo AS nome_projeto,
        p.descricao AS descricao_projeto,
        u.nome AS nome_funcionario,
        u.email AS email_funcionario,
        c.nome AS nome_cliente
    FROM tarefas t
    LEFT JOIN projetos p ON t.projeto_id = p.id
    LEFT JOIN usuarios u ON t.funcionario_id = u.id
    LEFT JOIN usuarios c ON p.cliente_id = c.id
    WHERE t.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$tarefa = $stmt->get_result()->fetch_assoc();

if (!$tarefa) {
    header("Location: index.php");
    exit;
}

// Buscar uploads relacionados ao projeto da tarefa
$uploads_sql = "
    SELECT * FROM uploads 
    WHERE projeto_id = ? 
    ORDER BY enviado_em DESC
";
$stmt_uploads = $conn->prepare($uploads_sql);
$stmt_uploads->bind_param("i", $tarefa['projeto_id']);
$stmt_uploads->execute();
$uploads = $stmt_uploads->get_result();

// Buscar mensagens relacionadas ao projeto
$mensagens_sql = "
    SELECT m.*, u.nome as remetente_nome
    FROM mensagens m
    LEFT JOIN usuarios u ON m.remetente_id = u.id
    WHERE m.projeto_id = ?
    ORDER BY m.enviado_em DESC
    LIMIT 10
";
$stmt_msg = $conn->prepare($mensagens_sql);
$stmt_msg->bind_param("i", $tarefa['projeto_id']);
$stmt_msg->execute();
$mensagens = $stmt_msg->get_result();

$stmt->close();
$stmt_uploads->close();
$stmt_msg->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Tarefa #<?= $tarefa['id'] ?> - AugeBit</title>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Detalhes da Tarefa #<?= $tarefa['id'] ?></h1>
        
        <div style="margin: 10px 0;">
            <a href="index.php">← Voltar para Lista de Tarefas</a> |
            <a href="index.php?editar=<?= $tarefa['id'] ?>">✏️ Editar Tarefa</a>
        </div>

        <!-- Informações da Tarefa -->
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd;">
            <h2>Informações da Tarefa</h2>
            <table border="0" cellpadding="5">
                <tr>
                    <td><strong>ID:</strong></td>
                    <td><?= $tarefa['id'] ?></td>
                </tr>
                <tr>
                    <td><strong>Título:</strong></td>
                    <td><?= htmlspecialchars($tarefa['titulo']) ?></td>
                </tr>
                <tr>
                    <td><strong>Descrição:</strong></td>
                    <td><?= $tarefa['descricao'] ? nl2br(htmlspecialchars($tarefa['descricao'])) : '<em>Sem descrição</em>' ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <strong>
                            <?php
                            $status_labels = [
                                'a_fazer' => '⏳ A Fazer',
                                'em_progresso' => '🔄 Em Progresso', 
                                'concluido' => '✅ Concluído'
                            ];
                            echo $status_labels[$tarefa['status']] ?? $tarefa['status'];
                            ?>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td><strong>Criado em:</strong></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($tarefa['criado_em'])) ?></td>
                </tr>
            </table>
        </div>

        <!-- Informações do Projeto -->
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd;">
            <h2>Projeto Relacionado</h2>
            <table border="0" cellpadding="5">
                <tr>
                    <td><strong>Nome do Projeto:</strong></td>
                    <td><?= htmlspecialchars($tarefa['nome_projeto'] ?? 'Sem projeto') ?></td>
                </tr>
                <tr>
                    <td><strong>Descrição do Projeto:</strong></td>
                    <td><?= $tarefa['descricao_projeto'] ? nl2br(htmlspecialchars($tarefa['descricao_projeto'])) : '<em>Sem descrição</em>' ?></td>
                </tr>
                <tr>
                    <td><strong>Cliente:</strong></td>
                    <td><?= htmlspecialchars($tarefa['nome_cliente'] ?? 'Não definido') ?></td>
                </tr>
            </table>
        </div>

        <!-- Informações do Funcionário -->
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd;">
            <h2>Funcionário Responsável</h2>
            <table border="0" cellpadding="5">
                <tr>
                    <td><strong>Nome:</strong></td>
                    <td><?= htmlspecialchars($tarefa['nome_funcionario'] ?? 'Não atribuído') ?></td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td><?= htmlspecialchars($tarefa['email_funcionario'] ?? 'N/A') ?></td>
                </tr>
            </table>
        </div>