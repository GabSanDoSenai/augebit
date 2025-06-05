<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require '../../conexao.php';

$result = $conn->query("SELECT id, titulo, status FROM projetos ORDER BY criado_em DESC");
?>

<?php 
$busca = $_GET['busca'] ?? '';

if ($busca !== '') {
    $stmt = $conn->prepare("SELECT id, titulo, status FROM projetos WHERE titulo LIKE ? ORDER BY criado_em DESC");
    $termo = '%' . $busca . '%';
    $stmt->bind_param("s", $termo);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, titulo, status FROM projetos ORDER BY criado_em DESC");
}
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
    <h2>Projetos</h2>
    <form method="get">
    <input type="text" name="busca" placeholder="Buscar por título..." value="<?= $_GET['busca'] ?? '' ?>">
    <button type="submit">🔍 Buscar</button>
</form>
    <table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Título</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>

    <?php while ($p = $result->fetch_assoc()): ?>
        
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['titulo']) ?></td>
            <td><?= ucfirst($p['status']) ?></td>
            <td>
                <a href="ver_tarefas.php?projeto_id=<?= $p['id'] ?>">Ver Tarefas</a> |
                <a href="ver_documentos.php?projeto_id=<?= $p['id'] ?>">Ver Documentos</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<br>

</body>
</html>



