<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require '../../conexao.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("ID inválido.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $projeto_id = $_POST['projeto_id'];
    $funcionario_id = $_POST['funcionario_id'];

    $stmt = $conn->prepare("UPDATE tarefas SET titulo=?, descricao=?, projeto_id=?, funcionario_id=? WHERE id=?");
    $stmt->bind_param("ssiii", $titulo, $descricao, $projeto_id, $funcionario_id, $id);
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Tarefa atualizada com sucesso!</p>";
    } else {
        echo "<p style='color:red;'>Erro ao atualizar: " . $stmt->error . "</p>";
    }
}

$tarefa = $conn->query("SELECT * FROM tarefas WHERE id = $id")->fetch_assoc();
$projetos = $conn->query("SELECT id, titulo FROM projetos");
$funcionarios = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'funcionario'");
?>
<?php include 'sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
    <h2>Editar Tarefa</h2>
<form method="post">
    Título: <input type="text" name="titulo" value="<?= htmlspecialchars($tarefa['titulo']) ?>" required><br><br>
    Descrição: <textarea name="descricao"><?= htmlspecialchars($tarefa['descricao']) ?></textarea><br><br>

    Projeto:
    <select name="projeto_id" required>
        <?php while ($p = $projetos->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id'] == $tarefa['projeto_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['titulo']) ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    Funcionário:
    <select name="funcionario_id" required>
        <?php while ($f = $funcionarios->fetch_assoc()): ?>
            <option value="<?= $f['id'] ?>" <?= $f['id'] == $tarefa['funcionario_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['nome']) ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <button type="submit">Salvar Alterações</button>
</form>

</body>
</html>
