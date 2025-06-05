<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
</body>
</html>
<?php include '../sidebar.php'; ?>
<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../../conexao.php';

// Aprovação ou recusa
if (isset($_GET['aprovar'])) {
    $id = (int)$_GET['aprovar'];
    $conn->query("UPDATE projetos SET status = 'aprovado' WHERE id = $id");
}

if (isset($_GET['recusar'])) {
    $id = (int)$_GET['recusar'];
    $conn->query("UPDATE projetos SET status = 'recusado' WHERE id = $id");
}

// Listar projetos pendentes
$result = $conn->query("SELECT p.id, p.titulo, p.descricao, u.nome AS cliente
                        FROM projetos p
                        JOIN usuarios u ON p.cliente_id = u.id
                        WHERE p.status = 'em_andamento'");

echo "<h2>Projetos Pendentes</h2>";

if ($result->num_rows === 0) {
    echo "<p>Nenhum projeto pendente no momento.</p>";
} else {
    while ($p = $result->fetch_assoc()) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
        echo "<strong>{$p['titulo']}</strong> por {$p['cliente']}<br>";
        echo "<p>{$p['descricao']}</p>";
        echo "<a href='avaliar_projetos.php?aprovar={$p['id']}'>✅ Aprovar</a> | ";
        echo "<a href='avaliar_projetos.php?recusar={$p['id']}'>❌ Recusar</a>";
        echo "</div>";
    }
}
