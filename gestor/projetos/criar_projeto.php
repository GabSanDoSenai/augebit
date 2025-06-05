<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Criar Projeto</h2>
<form method="post">
    Título: <input type="text" name="titulo" required><br>
    Descrição: <textarea name="descricao"></textarea><br>
    Cliente ID: <input type="number" name="cliente_id" required><br>
    <button type="submit">Criar</button>
</form>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../conexao.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $cliente_id = $_POST['cliente_id'];

    $sql = "INSERT INTO projetos (titulo, descricao, cliente_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $titulo, $descricao, $cliente_id);

    if ($stmt->execute()) {
        echo "Projeto criado com sucesso!";
    } else {
        echo "Erro: " . $stmt->error;
    }
}
?>

<?php
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../conexao.php';

// Processar atribuição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projeto_id = $_POST['projeto_id'];
    $funcionario_id = $_POST['funcionario_id'];

    // Verifica se já está atribuído
    $check = $conn->prepare("SELECT * FROM projetos_usuarios WHERE projeto_id = ? AND funcionario_id = ?");
    $check->bind_param("ii", $projeto_id, $funcionario_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO projetos_usuarios (projeto_id, funcionario_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $projeto_id, $funcionario_id);
        $stmt->execute();
        echo "<p>Funcionário atribuído com sucesso!</p>";
    } else {
        echo "<p>Funcionário já está atribuído a este projeto.</p>";
    }
}

// Obter projetos e funcionários
$projetos = $conn->query("SELECT id, titulo FROM projetos");
$funcionarios = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'funcionario'");
?>
<?php include '../sidebar.php'; ?>
<link rel="stylesheet" href="../../style.css">
<h2>Atribuir Funcionário a Projeto</h2>
<form method="post">
    Projeto:
    <select name="projeto_id" required>
        <?php while ($p = $projetos->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= $p['titulo'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    Funcionário:
    <select name="funcionario_id" required>
        <?php while ($f = $funcionarios->fetch_assoc()): ?>
            <option value="<?= $f['id'] ?>"><?= $f['nome'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <button type="submit">Atribuir</button>
</form>
