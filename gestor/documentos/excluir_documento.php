<?php
session_start();
require '../../conexao.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['documento_id'])) {
    $documento_id = (int)$_POST['documento_id'];
    
    // Buscar informações do documento
    $stmt = $conn->prepare("SELECT nome_arquivo, caminho_arquivo FROM uploads WHERE id = ?");
    $stmt->bind_param("i", $documento_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($documento = $resultado->fetch_assoc()) {
        // Tentar excluir o arquivo físico - ajustar caminho para uploads na raiz
        $caminho_completo = str_replace('../../uploads/', '../../uploads/', $documento['caminho_arquivo']);
        $arquivo_excluido = true;
        
        if (file_exists($caminho_completo)) {
            if (!unlink($caminho_completo)) {
                $arquivo_excluido = false;
                $erro = "Erro ao excluir o arquivo físico.";
            }
        }
        
        // Se conseguiu excluir o arquivo ou ele não existia, remove do banco
        if ($arquivo_excluido) {
            $stmt_delete = $conn->prepare("DELETE FROM uploads WHERE id = ?");
            $stmt_delete->bind_param("i", $documento_id);
            
            if ($stmt_delete->execute()) {
                $mensagem = "Documento '{$documento['nome_arquivo']}' excluído com sucesso.";
            } else {
                $erro = "Erro ao excluir o documento do banco de dados.";
            }
        }
    } else {
        $erro = "Documento não encontrado.";
    }
}

// Redirecionar de volta com mensagem
$redirect_url = "visualizar_documentos.php";

if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'visualizar_documentos.php') !== false) {
    // Preservar parâmetros de filtro se vieram da página de visualização
    $parsed = parse_url($_SERVER['HTTP_REFERER']);
    if (isset($parsed['query'])) {
        $redirect_url .= "?" . $parsed['query'];
    }
}

if ($mensagem) {
    $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'sucesso=' . urlencode($mensagem);
} elseif ($erro) {
    $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'erro=' . urlencode($erro);
}

header("Location: $redirect_url");
exit;
?>