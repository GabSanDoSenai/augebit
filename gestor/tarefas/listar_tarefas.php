<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require '../../conexao.php';

// Atualizar status se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tarefa_id'], $_POST['status'])) {
    $id = (int)$_POST['tarefa_id'];
    $novo_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE tarefas SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $id);
    $stmt->execute();
    echo "<p style='color:green;'>Status da tarefa #$id atualizado para <strong>$novo_status</strong>.</p>";
}

// Listar tarefas
$sql = "
    SELECT 
        t.id, t.titulo, t.status, t.criado_em,
        p.titulo AS nome_projeto,
        u.nome AS nome_funcionario
    FROM tarefas t
    LEFT JOIN projetos p ON t.projeto_id = p.id
    LEFT JOIN usuarios u ON t.funcionario_id = u.id
    ORDER BY t.criado_em DESC
";

$result = $conn->query($sql);
?>
<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/geral.css">
</head>
<body>
    
    
<div class="main-content">
<h2>Lista de Tarefas</h2>
<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Título</th>
        <th>Projeto</th>
        <th>Funcionário</th>
        <th>Status</th>
        <th>Alterar Status</th>
        <th>Ações</th>
    </tr>

    <?php while ($t = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $t['id'] ?></td>
            <td><?= htmlspecialchars($t['titulo']) ?></td>
            <td><?= htmlspecialchars($t['nome_projeto'] ?? 'Sem projeto') ?></td>
            <td><?= htmlspecialchars($t['nome_funcionario'] ?? 'Não atribuído') ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></td>
            <td>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="tarefa_id" value="<?= $t['id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option value="a_fazer" <?= $t['status'] === 'a_fazer' ? 'selected' : '' ?>>A Fazer</option>
                        <option value="em_progresso" <?= $t['status'] === 'em_progresso' ? 'selected' : '' ?>>Em Progresso</option>
                        <option value="concluido" <?= $t['status'] === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </form>
            </td>
            <td><a href="editar_tarefa.php?id=<?= $t['id'] ?>">✏️ Editar</a></td>
        </tr>
    <?php endwhile; ?>
</table>
</div>
</body>
</html>

