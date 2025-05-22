<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../conexao.php';

if (!isset($_GET['projeto_id']) || !is_numeric($_GET['projeto_id'])) {
    die("Projeto inválido.");
}

$projeto_id = $_GET['projeto_id'];

$stmt = $conn->prepare("SELECT u.nome FROM projetos_usuarios pu JOIN usuarios u ON pu.funcionario_id = u.id WHERE pu.projeto_id = ?");
$stmt->bind_param("i", $projeto_id);
$stmt->execute();
$res = $stmt->get_result();

echo "<h2>Equipe do Projeto #$projeto_id</h2>";
while ($row = $res->fetch_assoc()) {
    echo "<p>- {$row['nome']}</p>";
}
