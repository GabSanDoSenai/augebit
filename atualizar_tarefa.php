<?php
session_start();
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_tarefa = $_POST['id_tarefa'];
    $status = $_POST['status'];

    $sql = "UPDATE tarefas SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id_tarefa);
    $stmt->execute();
}

header("Location: dashboard_funcionario.php");
exit;
