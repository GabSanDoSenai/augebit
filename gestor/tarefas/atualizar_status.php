<?php
// ====== ARQUIVO: tarefas/atualizar_status.php ======
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

require '../conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tarefa_id'], $_POST['status'])) {
    $tarefa_id = (int)$_POST['tarefa_id'];
    $novo_status = $_POST['status'];
    
    // Validar status
    $status_validos = ['a_fazer', 'em_progresso', 'concluido'];
    if (!in_array($novo_status, $status_validos)) {
        header("Location: index.php?erro=status_invalido");
        exit;
    }
    
    // Verificar se o usuário pode alterar esta tarefa
    $verificar_sql = "SELECT t.*, p.titulo as projeto_titulo FROM tarefas t 
                      LEFT JOIN projetos p ON t.projeto_id = p.id 
                      WHERE t.id = ?";
    
    if ($_SESSION['usuario_tipo'] !== 'admin') {
        $verificar_sql .= " AND t.funcionario_id = ?";
    }
    
    $stmt = $conn->prepare($verificar_sql);
    if ($_SESSION['usuario_tipo'] !== 'admin') {
        $stmt->bind_param("ii", $tarefa_id, $_SESSION['usuario_id']);
    } else {
        $stmt->bind_param("i", $tarefa_id);
    }
    
    $stmt->execute();
    $tarefa = $stmt->get_result()->fetch_assoc();
    
    if (!$tarefa) {
        header("Location: index.php?erro=tarefa_nao_encontrada");
        exit;
    }
    
    // Atualizar status
    $update_sql = "UPDATE tarefas SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $novo_status, $tarefa_id);
    
    if ($stmt->execute()) {
        // Log da alteração (opcional)
        $log_sql = "INSERT INTO mensagens (remetente_id, projeto_id, mensagem, origem) VALUES (?, ?, ?, 'usuario')";
        $log_msg = "Status da tarefa '{$tarefa['titulo']}' alterado para: " . ucfirst(str_replace('_', ' ', $novo_status));
        $stmt_log = $conn->prepare($log_sql);
        $stmt_log->bind_param("iis", $_SESSION['usuario_id'], $tarefa['projeto_id'], $log_msg);
        $stmt_log->execute();
        
        header("Location: index.php?sucesso=status_atualizado");
    } else {
        header("Location: index.php?erro=falha_atualizacao");
    }
} else {
    header("Location: index.php");
}
?>
