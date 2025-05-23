
<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../conexao.php';

$result = $conn->query("SELECT * FROM projetos");

echo "<h2>Projetos</h2>";
while ($projeto = $result->fetch_assoc()) {
    echo "<div>";
    echo "<strong>{$projeto['titulo']}</strong> - Status: {$projeto['status']}<br>";
    echo "<a href='editar_projeto.php?id={$projeto['id']}'>Editar</a>";
    echo "</div><hr>";
}
