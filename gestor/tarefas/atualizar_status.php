<?php
require '../conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $novo_status = $_POST['status'];

    $sql = "UPDATE tarefas SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $novo_status, $id);

    if ($stmt->execute()) {
        echo "Status atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar status: " . $stmt->error;
    }

    $stmt->close();
} else {
    $id = $_GET['id'];
}
?>
<?php include 'sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <form method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    Novo status:
    <select name="status" required>
        <option value="a_fazer">A Fazer</option>
        <option value="em_progresso">Em Progresso</option>
        <option value="concluido">Concluído</option>
    </select>
    <button type="submit">Atualizar</button>
</form>
</body>
</html>
