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
                <a href="/views/tarefas/listar.php?funcionario_id=<?= $f['id'] ?>">🔍 Ver Tarefas</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
