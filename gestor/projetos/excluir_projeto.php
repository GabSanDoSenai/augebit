<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

require '../../conexao.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Verifica se o projeto existe
    $stmt = $conn->prepare("SELECT id FROM projetos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Exclui o projeto
        $delete = $conn->prepare("DELETE FROM projetos WHERE id = ?");
        $delete->bind_param("i", $id);
        $delete->execute();
    }
}

header("Location: listar_projetos.php");
exit;
