<?php
require_once '../conexao.php';

// Busca todos os funcionários
$funcionarios = $conn->query("
    SELECT 
        u.id, u.nome, u.email,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id) AS total_tarefas,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id AND t.status = 'em_progresso') AS em_progresso,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id AND t.status = 'concluido') AS concluidas
    FROM usuarios u
    WHERE u.tipo = 'funcionario'
    ORDER BY u.nome
");
?>
<?php include 'sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/geral.css">
</head>
<body>
    <div class="main-content">
    <h2>👥 Funcionários Cadastrados</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Total de Tarefas</th>
        <th>Em Progresso</th>
        <th>Concluídas</th>
        <th>Ações</th>
    </tr>
    <?php while ($f = $funcionarios->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($f['nome']) ?></td>
            <td><?= htmlspecialchars($f['email']) ?></td>
            <td><?= $f['total_tarefas'] ?></td>
            <td><?= $f['em_progresso'] ?></td>
            <td><?= $f['concluidas'] ?></td>
            <td>
                <a href="../tarefas/listar_tarefas.php?funcionario_id=<?= $f['id'] ?>">🔍 Ver Tarefas</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
</div>
</body>
</html>
