<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../../conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'] ?? null;
    $projeto_id = $_POST['projeto_id'];
    $funcionario_id = $_POST['funcionario_id'];

    $sql = "INSERT INTO tarefas (titulo, descricao, projeto_id, funcionario_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $titulo, $descricao, $projeto_id, $funcionario_id);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Tarefa criada com sucesso!</p>";
    } else {
        echo "<p style='color:red;'>Erro ao criar tarefa: " . $stmt->error . "</p>";
    }

    $stmt->close();
}

// Carregar projetos e funcionários
$projetos = $conn->query("SELECT id, titulo FROM projetos");
$funcionarios = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'funcionario'");
?>
<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h2>Criar Nova Tarefa</h2>

<form method="post">
    Título: <input type="text" name="titulo" required><br><br>
    Descrição: <textarea name="descricao"></textarea><br><br>

    Projeto:
    <select name="projeto_id" required>
        <option value="">-- Selecione um projeto --</option>
        <?php while ($p = $projetos->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['titulo']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    Funcionário:
    <select name="funcionario_id" required>
        <option value="">-- Selecione um funcionário --</option>
        <?php while ($f = $funcionarios->fetch_assoc()): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <button type="submit">Criar Tarefa</button>
    
</form>

</body>
</html>
