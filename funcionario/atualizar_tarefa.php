<?php
session_start();

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'funcionario') {
    header("Location: ../login.php");
    exit();
}

include '../conexao.php';

$funcionario_id = $_SESSION['usuario_id'];

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard_funcionario.php");
    exit();
}

// Validar dados recebidos
if (!isset($_POST['tarefa_id']) || !isset($_POST['novo_status'])) {
    $_SESSION['error_message'] = "Dados inválidos enviados.";
    header("Location: dashboard_funcionario.php");
    exit();
}

$tarefa_id = (int)$_POST['tarefa_id'];
$novo_status = $conn->real_escape_string($_POST['novo_status']);

// Validar status
$status_validos = ['a_fazer', 'em_progresso', 'concluido'];
if (!in_array($novo_status, $status_validos)) {
    $_SESSION['error_message'] = "Status inválido selecionado.";
    header("Location: dashboard_funcionario.php");
    exit();
}

// Verificar se a tarefa pertence ao funcionário logado
$sql_verificar = "SELECT id FROM tarefas WHERE id = ? AND funcionario_id = ?";
$stmt_verificar = $conn->prepare($sql_verificar);
$stmt_verificar->bind_param("ii", $tarefa_id, $funcionario_id);
$stmt_verificar->execute();
$result_verificar = $stmt_verificar->get_result();

if ($result_verificar->num_rows === 0) {
    $_SESSION['error_message'] = "Você não tem permissão para atualizar esta tarefa.";
    $stmt_verificar->close();
    $conn->close();
    header("Location: dashboard_funcionario.php");
    exit();
}

$stmt_verificar->close();

// Atualizar o status da tarefa
$sql_atualizar = "UPDATE tarefas SET status = ? WHERE id = ? AND funcionario_id = ?";
$stmt_atualizar = $conn->prepare($sql_atualizar);
$stmt_atualizar->bind_param("sii", $novo_status, $tarefa_id, $funcionario_id);

if ($stmt_atualizar->execute()) {
    $_SESSION['success_message'] = "Status da tarefa atualizado com sucesso!";
} else {
    $_SESSION['error_message'] = "Erro ao atualizar o status da tarefa.";
}

$stmt_atualizar->close();
$conn->close();

// Redirecionar de volta para o dashboard
header("Location: dashboard_funcionario.php");
exit();
?>