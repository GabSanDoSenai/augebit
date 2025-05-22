<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../../conexao.php';

$projeto_id = $_GET['projeto_id'] ?? null;

if (!$projeto_id) {
    echo "Projeto não especificado.";
    exit;
}

$stmt = $conn->prepare("SELECT nome_arquivo, caminho_arquivo, enviado_em FROM uploads WHERE projeto_id = ?");
$stmt->bind_param("i", $projeto_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Documentos do Projeto #$projeto_id</h2>";
while ($row = $result->fetch_assoc()) {
    $nome = htmlspecialchars($row['nome_arquivo']);
    $link = $row['caminho_arquivo'];
    echo "<p><a href='$link' download>$nome</a> (Enviado em: {$row['enviado_em']})</p>";
}
