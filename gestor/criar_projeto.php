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

<h2>Criar Projeto</h2>
<form method="post">
    Título: <input type="text" name="titulo" required><br>
    Descrição: <textarea name="descricao"></textarea><br>
    Cliente ID: <input type="number" name="cliente_id" required><br>
    <button type="submit">Criar</button>
</form>
