<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require '../../conexao.php';

$projeto_id = $_GET['projeto_id'] ?? null;
if (!$projeto_id || !is_numeric($projeto_id)) {
    die("Projeto inválido.");
}

// Buscar nome do projeto
$projeto = $conn->query("SELECT titulo FROM projetos WHERE id = $projeto_id")->fetch_assoc();
if (!$projeto) {
    die("Projeto não encontrado.");
}

// Buscar tarefas
$sql = "
    SELECT t.id, t.titulo, t.status, t.criado_em, u.nome AS funcionario
    FROM tarefas t
    LEFT JOIN usuarios u ON t.funcionario_id = u.id
    WHERE t.projeto_id = $projeto_id
    ORDER BY t.criado_em DESC
";
$tarefas = $conn->query($sql);
?>
<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
    <h2>Tarefas do Projeto: <?= htmlspecialchars($projeto['titulo']) ?></h2>
</body>
</html>

<?php if ($tarefas->num_rows === 0): ?>
    <p>Nenhuma tarefa cadastrada neste projeto.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Funcionário</th>
            <th>Status</th>
            <th>Criado em</th>
        </tr>
        <?php while ($t = $tarefas->fetch_assoc()): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><?= htmlspecialchars($t['titulo']) ?></td>
                <td><?= htmlspecialchars($t['funcionario'] ?? 'Não atribuído') ?></td>
                <td><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></td>
                <td><?= $t['criado_em'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>
