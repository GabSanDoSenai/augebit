<?php
session_start();

// Verificar se o usuário está logado e é do tipo cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../login.php");
    exit();
}

include '../conexao.php';

$cliente_id = $_SESSION['usuario_id'];
$nome_cliente = $_SESSION['usuario_nome'];

// Inicializar variáveis para evitar warnings
$upload_error = "";
$upload_success = "";
$mensagem_chat_sucesso = "";
$mensagem_chat_erro = "";

// FUNÇÃO: Obter URL correta para download (copiada do funcionário)
function obterUrlDownload($caminho_arquivo, $projeto_id = null) {
    // Base URL do diretório uploads
    $base_url = '/augebit/uploads/';
    
    // Se o caminho já é uma URL completa, retornar como está
    if (strpos($caminho_arquivo, 'http') === 0) {
        return $caminho_arquivo;
    }
    
    // Se contém 'uploads/' no caminho, extrair a parte após 'uploads/'
    if (strpos($caminho_arquivo, 'uploads/') !== false) {
        $parte_arquivo = substr($caminho_arquivo, strpos($caminho_arquivo, 'uploads/') + 8);
        return $base_url . $parte_arquivo;
    }
    
    // Se é apenas o nome do arquivo e temos projeto_id
    if (strpos($caminho_arquivo, '/') === false && $projeto_id) {
        // Buscar o nome da pasta do projeto
        global $conn;
        $sql_projeto = "SELECT titulo FROM projetos WHERE id = ?";
        $stmt = $conn->prepare($sql_projeto);
        $stmt->bind_param("i", $projeto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $projeto = $result->fetch_assoc();
            $nome_projeto_formatado = str_replace(' ', '_', $projeto['titulo']);
            $pasta_projeto = $nome_projeto_formatado . '_' . $projeto_id;
            return $base_url . $pasta_projeto . '/' . $caminho_arquivo;
        }
    }
    
    // Para outros casos, tentar o caminho direto
    return $base_url . $caminho_arquivo;
}

// FUNÇÃO: Verificar se arquivo existe fisicamente (copiada do funcionário)
function verificarArquivoExiste($caminho_arquivo, $projeto_id = null) {
    $documento_root = $_SERVER['DOCUMENT_ROOT'];
    
    // Lista de caminhos possíveis para verificar
    $caminhos_possiveis = [];
    
    // Se é apenas nome do arquivo e temos projeto_id
    if (strpos($caminho_arquivo, '/') === false && $projeto_id) {
        global $conn;
        $sql_projeto = "SELECT titulo FROM projetos WHERE id = ?";
        $stmt = $conn->prepare($sql_projeto);
        $stmt->bind_param("i", $projeto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $projeto = $result->fetch_assoc();
            $nome_projeto_formatado = str_replace(' ', '_', $projeto['titulo']);
            $pasta_projeto = $nome_projeto_formatado . '_' . $projeto_id;
            $caminhos_possiveis[] = $documento_root . '/augebit/uploads/' . $pasta_projeto . '/' . $caminho_arquivo;
        }
    }
    
    // Outros caminhos possíveis
    $caminhos_possiveis[] = $documento_root . '/augebit/uploads/' . $caminho_arquivo;
    $caminhos_possiveis[] = $documento_root . $caminho_arquivo;
    
    if (strpos($caminho_arquivo, 'uploads/') !== false) {
        $parte_arquivo = substr($caminho_arquivo, strpos($caminho_arquivo, 'uploads/') + 8);
        $caminhos_possiveis[] = $documento_root . '/augebit/uploads/' . $parte_arquivo;
    }
    
    foreach ($caminhos_possiveis as $caminho) {
        if (file_exists($caminho)) {
            return $caminho;
        }
    }
    
    return false;
}

// Processar upload de arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_arquivo'])) {
    $projeto_id = (int)$_POST['projeto_id'];
    
    if ($projeto_id <= 0) {
        $upload_error = "Selecione um projeto válido.";
    } else {
        // Verificar se o cliente é dono do projeto
        $verificar_acesso = $conn->prepare("SELECT COUNT(*) as tem_acesso FROM projetos WHERE id = ? AND cliente_id = ?");
        $verificar_acesso->bind_param("ii", $projeto_id, $cliente_id);
        $verificar_acesso->execute();
        $acesso_result = $verificar_acesso->get_result();
        $tem_acesso = $acesso_result->fetch_assoc()['tem_acesso'] > 0;
        $verificar_acesso->close();
        
        if (!$tem_acesso) {
            $upload_error = "Você não tem permissão para enviar arquivos para este projeto.";
        } else {
            // Buscar título do projeto para criar diretório
            $projeto_stmt = $conn->prepare("SELECT titulo FROM projetos WHERE id = ?");
            $projeto_stmt->bind_param("i", $projeto_id);
            $projeto_stmt->execute();
            $projeto_result = $projeto_stmt->get_result();
            
            if ($projeto_data = $projeto_result->fetch_assoc()) {
                $titulo_projeto = $projeto_data['titulo'];
                
                // Configurar processador de upload
                $upload_config = [
                    'max_file_size' => 50 * 1024 * 1024, // 50MB
                    'allowed_types' => [
                        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
                        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain', 'application/zip', 'application/x-rar-compressed'
                    ],
                    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
                    'upload_dir' => '../uploads/',
                    'field_name' => 'arquivos'
                ];
                
                // Verificar se o arquivo upload_processor.php existe
                if (file_exists('../includes/upload_processor.php')) {
                    require_once '../includes/upload_processor.php';
                    
                    $processor = new UploadProcessor($upload_config);
                    $resultado = $processor->processarMultiplosArquivos($projeto_id, $titulo_projeto);
                    
                    if ($resultado['sucesso'] && $resultado['arquivos_processados'] > 0) {
                        // Atualizar tabela uploads com informações do usuário cliente
                        $update_uploads = $conn->prepare("UPDATE uploads SET enviado_por = ? WHERE projeto_id = ? AND enviado_por IS NULL");
                        $update_uploads->bind_param("ii", $cliente_id, $projeto_id);
                        $update_uploads->execute();
                        
                        $upload_success = $resultado['mensagem'];
                    } else {
                        $upload_error = !empty($resultado['erros']) ? implode("<br>", $resultado['erros']) : "Erro ao processar arquivos.";
                    }
                } else {
                    // Fallback para upload simples se o processor não existir
                    $upload_error = "Sistema de upload não está disponível. Entre em contato com o administrador.";
                }
            } else {
                $upload_error = "Projeto não encontrado.";
            }
            $projeto_stmt->close();
        }
    }
}

// Processar envio de mensagem de chat
if (isset($_POST['enviar_mensagem'])) {
    $projeto_id = (int)$_POST['projeto_mensagem_id'];
    $mensagem = trim($_POST['mensagem']);
    
    if (!empty($mensagem)) {
        // Buscar o ID do gestor/admin (assumindo que o primeiro admin/funcionario é o destinatário)
        $sql_gestor = "SELECT id FROM usuarios WHERE tipo IN ('admin', 'funcionario') LIMIT 1";
        $resultado_gestor = $conn->query($sql_gestor);
        
        if ($resultado_gestor->num_rows > 0) {
            $gestor = $resultado_gestor->fetch_assoc();
            $destinatario_id = $gestor['id'];
            
            $sql = "INSERT INTO mensagens (remetente_id, destinatario_id, projeto_id, mensagem, origem) VALUES (?, ?, ?, ?, 'usuario')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $cliente_id, $destinatario_id, $projeto_id, $mensagem);
            
            if ($stmt->execute()) {
                $mensagem_chat_sucesso = "Mensagem enviada com sucesso!";
            } else {
                $mensagem_chat_erro = "Erro ao enviar mensagem.";
            }
            $stmt->close();
        }
    }
}

// Buscar projetos do cliente
$sql = "SELECT id, titulo, status, criado_em FROM projetos WHERE cliente_id = ? ORDER BY criado_em DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$projetos = $stmt->get_result();
$stmt->close();

// CONSULTA MELHORADA: Buscar documentos dos projetos do cliente
$sql_documentos = "SELECT DISTINCT u.id, u.nome_arquivo, u.caminho_arquivo, u.data_upload, u.projeto_id, p.titulo as projeto_nome 
FROM uploads u 
INNER JOIN projetos p ON u.projeto_id = p.id 
WHERE p.cliente_id = ?
ORDER BY u.data_upload DESC";

$stmt_documentos = $conn->prepare($sql_documentos);
$stmt_documentos->bind_param("i", $cliente_id);
$stmt_documentos->execute();
$result_documentos = $stmt_documentos->get_result();
$stmt_documentos->close();

// Buscar projetos para o formulário de upload
$sql = "SELECT id, titulo FROM projetos WHERE cliente_id = ? ORDER BY titulo";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$projetos_upload = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - AugeBit</title>
    <style>
        /* RESET & BASE STYLES */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f8f9fa;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-SemiBold.ttf') format('truetype');
            font-weight: 600;
        }
           
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-Regular.ttf') format('truetype');
            font-weight: 450;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-Medium.ttf') format('truetype');
            font-weight: 500;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-Italic.ttf') format('truetype');
            font-weight: 400;
            font-style: italic;
        }
        
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-ExtraLight.ttf') format('truetype');
            font-weight: 200;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            position: relative;
            z-index: 10;
            padding: 10px 0;
        }

        .logo-header {
            display: flex;
            align-items: center;
        }

        .logo-header img {
            position: relative;
            width: 70px;
            height: 80px;
            top: -16px; 
            left:30px
        }

        .logo-header h1 {
            position:relative; 
            font-size: 30px;
            font-family: 'Poppins';
            font-weight: 300;
            left: 30px;
        }

        nav ul {
            padding-right: 100px;
            display: flex;
            gap: 80px;
            list-style: none;
        }

        nav a {
            color: #3E236A;
            text-decoration: none;
            font-size: 19px;
            font-family: 'Poppins';
            transition: all 0.3s ease;
            font-weight: 450;
        }

        nav a:hover {
            color: #9999FF;
        }

        .active {
            color: #3E236A;
        }

        .acount {
            color: #3E236A;
        }

        .contato {
            color: #3E236A;
        }
       
        section {
            padding: 30px;
        }
        
        section:last-child {
            border-bottom: none;
        }
        
        .greeting {
            position: relative;
            height: 300px;
            display: flex;
            left: 500px;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding-top: 60px;
        }

        p {
            font-family:'Poppins';
            font-weight: 200; 
        }

        .new-project-btn {
            height: 30px;
            width: 250px;
            padding: 20px;
            font-family: 'Poppins';
            font-weight: 450;
            font-size: 15px;
            color: white;
            border-radius: 30px;
            background-color: #9999FF;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 58px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .new-project-btn:hover {
            background-color: #3E236A;
        }
        
        .greeting h1 {
            font-size: 40px;
            font-weight: 200;
            font-family: 'Poppins';
            padding-top: 100px; 
            padding-left:76px;
        }
        
        .username {
            color: #6741d9;
            font-weight: 500;
            font-family: 'Poppins';
            font-size: 40px;
        }
        
        /* PROJECTS SECTION */
        .projects-section {
            margin-top: 30px;
        }
        
        .projects-section h1 {
            font-size: 2rem;
            font-weight: 500;
            color: #3E236A;
            margin-bottom: 30px;
            font-family: 'Poppins';
            margin-left:30px
        }
        
        .project-cards {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .project-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .project-card h2 {
            font-size: 1.4rem;
            font-weight: 500;
            color: #3E236A;
            margin-bottom: 15px;
            font-family: 'Poppins';
        }
        
        .project-card p {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .project-card .access-btn {
            display: inline-block;
            background: none;
            color: #6741d9;
            font-weight: 500;
            text-decoration: none;
            padding: 8px 0;
            font-family: 'Poppins';
            font-size: 1rem;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .project-card .access-btn:hover {
            border-bottom-color: #6741d9;
        }
        
        /* CONTACT SECTION */
        .contact h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .contact p {
            color: #868e96;
            font-size: 1rem;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            nav ul {
                padding-right: 20px;
                gap: 30px;
            }
            
            .greeting {
                height: 200px;
            }
            
            .greeting h1 {
                font-size: 32px;
                padding-top: 50px;
                padding-left:30px
            }
            
            .username {
                font-size: 32px;
            }
        }
        
        @media (max-width: 600px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            nav ul {
                padding: 20px 0;
                gap: 20px;
                flex-direction: column;
            }
            
            section {
                padding: 20px;
            }
            
            .projects-section h1 {
                font-size: 1.6rem;
            }
            
            .project-card h2 {
                font-size: 1.2rem;
            }
        }

        .contact {
            display: flex;
            align-items: center;
            margin-bottom: 30px; 
            margin-top:30px;
        }

        .contact .text {
            flex: 1;
            position: absolute;
            left:180px;
        }
        .contact p{
            position: relative; 
            font-family:'Poppins';
            font-weight:400;
            color: #3E236A;
            padding-left: 99px; 
        }

        .contact h2{
            position:relative; 
            font-family: 'Poppins';
            font-weight:600px;
            color:  #3E236A;
            font-size:40px;
            padding-left:90px; 
        }
        .contact button {
            position:relative; 
            left: 900px;
            height: 30px;
            width:190px;
            padding: 20px;
            font-family: 'Poppins';
            font-weight: 400;
            font-size:15px;
            color: white;
            border-radius: 30px;
            background-color: #9999FF;
            border: none; 
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .contact button:hover {
            background-color: #3E236A;
        }
        .contato {
            margin-top: 20px;
        }

        /* Seção Contatos */
        .contatos {
            text-align: center;
            justify-content: center;
            padding: 90px 70px;
            height: 300px;
        }

        .contato-section {
            position: relative;
            z-index: 10; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
        }
        .contato-section h3{
            font-family:"Poppins";
            font-weight:500; 
        }

        .trabalhos p{
            font-family: ' Poppins';
            font-weight:300;
        }
        .texto h3 {
            position: relative;
            color: #3e206e;
            font-size: 1.8rem;
            text-align: left;
            text-transform: none;
            letter-spacing: normal;
        }

        .redes {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .rede {
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.3s ease;
            padding: 20px;
            border-radius: 10px;
        }

        .rede:hover {
            background: rgba(126, 117, 227, 0.1);
            transform: translateY(-5px);
        }

        .rede img {
            width: 40px;
            height: 40px;
            margin-bottom: 10px;
        }

        .rede p {
            font-weight: 400;
            color:  #9999FF;
            font-size: 0.9rem;
            font-family: 'Poppins';
        }

        /* Status styles */
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            font-family: 'Poppins';
        }

        .status.em_andamento {
            background-color: #fff3cd;
            color: #856404;
        }

        .status.aprovado {
            background-color: #d4edda;
            color: #9999FF;
        }

        .status.finalizado {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status.ajustes {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            font-family: 'Poppins';
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #3E236A;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: rgba(153, 153, 255, 0.05);
        }

        /* Button styles */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 2px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9em;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-family: 'Poppins';
            font-weight: 500;
        }

        .btn-primary {
            background-color: #9999FF;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3E236A;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: #9999FF;
            color: white;
        }

        .btn-success:hover {
            background-color: #3E236A;
            transform: translateY(-2px);
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #117a8b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }

        .btn-download {
            background-color: #0984e3;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-download:hover {
            background-color: #9999FF;
            transform: translateY(-2px);
        }

        /* Form styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #3E236A;
            font-family: 'Poppins';
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
            font-family: 'Poppins';
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #9999FF;
            box-shadow: 0 0 0 3px rgba(153, 153, 255, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Alert styles */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            font-family: 'Poppins';
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .upload-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }

        .upload-form:hover {
            border-color: #9999FF;
            background-color: rgba(153, 153, 255, 0.05);
        }

        .chat-form {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 40px;
            font-style: italic;
            font-family: 'Poppins';
        }

        /* Footer styles */
        .rodape {
            background-color: #9999FF;
            width: 100vw;
            min-height: 300px;
            padding: 60px 15px;
            color: #ffffff;
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 40px;
            position: relative;
            margin-top: 80px;
            left: 0;
            box-sizing: border-box;
        }

        .logo-rodape {
            text-align: center;
            display: flex;
            padding-left: 50px;
            flex-direction: column;
            align-items: center;
        }

        .logo-rodape img {
            width: 100px;
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .logo-rodape h1 {
            font-size: 1.8rem;
            font-family: 'Poppins';
            margin-bottom: 5px;
            color: #ffffff;
            font-weight: 500;
            margin-left: 25px;
        }

        .logo-rodape p {
            font-size: 0.9rem;
            margin-left: 25px;
            font-family: 'Poppins';
            font-weight: 300;
        }

        .pages {
            margin-left: -110px;
        }
        .pages a {
            display: block;
            margin-bottom: 15px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            font-family:"Poppins";
            font-weight:300; 
            transition: all 0.4s cubic-bezier(0.65, 0, 0.35, 1);
            position: relative;
        }

        .pages a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: white;
            transition: all 0.4s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .pages a:hover::after {
            width: 100%;
        }

        .redescociais {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .redescociais img {
            width: 25px;
            height: 25px;
            filter: brightness(0) invert(1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .redescociais img:hover {
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .pages a {
                margin-bottom: 10px;
                font-size: 0.8rem;
            }
        }

        .actions {
            margin-top: 15px;
        }

        .actions .btn {
            margin-right: 10px;
        }

        /* Estilos específicos para seção de documentos */
        .document-status-available {
            color: #28a745;
            font-weight: 600;
        }

        .document-status-missing {
            color: #dc3545;
            font-weight: 600;
        }

        .document-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .section {
            margin: 50px;
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .section:hover {
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #9999FF;
            margin-bottom: 1.8rem;
            font-weight: 600;
            font-size: 1.5rem;
            position: relative;
            padding-bottom: 0.8rem;
            font-family: 'Poppins';
        }

        .section h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #9999FF, #3E236A);
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-header">    
            <img src="../assets/img/augebit.logo.png" alt="Logo da empresa"> 
            <h1>AUGEBIT</h1>
        </div>    
        <nav>       
            <ul>         
                <li><a href="dashboard_cliente.php" class="active">Home</a></li>         
                <li><a href="#projects-section" class="acount">Projetos</a></li>
                <li><a href="chat.php">Chat</a></li>     
                <li><a href="../index.php" class="contato">Sair</a></li>    
            </ul>     
        </nav>   
    </header>    
    
    <div class="dashboard">
        <!-- Mensagens de Sistema -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <section class="greeting">
            <h1>Olá <span class="username"><?php echo htmlspecialchars($nome_cliente); ?></span></h1>
            <a href="solicitar_projeto.php" style="text-decoration:none" class="new-project-btn">Solicitar novo projeto</a>
        </section>
        
        <section id="projects-section" class="projects-section">
            <h1>Seus Projetos</h1>
            
            <div class="project-cards">
                <?php if ($projetos->num_rows > 0): ?>
                    <?php while ($projeto = $projetos->fetch_assoc()): ?>
                        <div class="project-card">
                            <h2><?php echo htmlspecialchars($projeto['titulo']); ?></h2>
                            <p><strong>Status:</strong> <span class="status <?php echo $projeto['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $projeto['status'])); ?>
                            </span></p>
                            <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i', strtotime($projeto['criado_em'])); ?></p>
                            
                            <div class="actions">
    <a href="ver-tarefas.php?projeto_id=<?php echo $projeto['id']; ?>" class="btn btn-primary">Ver Tarefas</a>
    <a href="ver-documentos.php?projeto_id=<?php echo $projeto['id']; ?>" class="btn btn-info">Ver Documentos</a>
    <?php if ($projeto['status'] === 'finalizado'): ?>
        <a href="#" class="btn btn-success" onclick="alert('Funcionalidade em desenvolvimento')">Ver Entrega Final</a>
    <?php endif; ?>
</div>

                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="project-card">
                        <h2>Nenhum projeto encontrado</h2>
                        <p>Você ainda não possui projetos cadastrados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Seção de Documentos Melhorada -->
        <section class="section">
            <h2>Documentos dos Projetos</h2>
            
            <?php if ($result_documentos && $result_documentos->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Arquivo</th>
                            <th>Projeto</th>
                            <th>Data de Upload</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($documento = $result_documentos->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($documento['nome_arquivo']); ?></td>
                                <td><?php echo htmlspecialchars($documento['projeto_nome']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($documento['data_upload'])); ?></td>
                                <td>
                                    <?php 
                                    $caminho_fisico = verificarArquivoExiste($documento['caminho_arquivo'], $documento['projeto_id']);
                                    if ($caminho_fisico): 
                                    ?>
                                        <span class="document-status-available">Disponível</span>
                                    <?php else: ?>
                                        <span class="document-status-missing">Não encontrado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($caminho_fisico): ?>
                                        <div class="document-actions">
                                            <?php 
                                            $url_download = obterUrlDownload($documento['caminho_arquivo'], $documento['projeto_id']);
                                            $extensao = strtolower(pathinfo($documento['nome_arquivo'], PATHINFO_EXTENSION));
                                            ?>
                                            
                                            <!-- Botão de Visualizar (para PDFs e imagens) -->
                                            <?php if (in_array($extensao, ['pdf', 'jpg', 'jpeg', 'png', 'gif'])): ?>
                                                <a href="<?php echo htmlspecialchars($url_download); ?>" 
                                                   class="btn btn-secondary" 
                                                   target="_blank"
                                                   title="Visualizar arquivo">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                                        <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                                    </svg>
                                                    Ver
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Botão de Download -->
                                            <a href="<?php echo htmlspecialchars($url_download); ?>" 
                                               class="btn btn-download" 
                                               download="<?php echo htmlspecialchars($documento['nome_arquivo']); ?>"
                                               title="Baixar arquivo">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                </svg>
                                                Download
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-size: 12px;">Arquivo não encontrado no servidor</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
                        <path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8zm0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <p>Nenhum documento encontrado.</p>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Seção de Upload Melhorada -->
        <section class="section">
            <h2>Enviar Documentos</h2>
            
            <?php if (!empty($upload_error)): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    <?php echo $upload_error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($upload_success)): ?>
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    <?php echo htmlspecialchars($upload_success); ?>
                </div>
            <?php endif; ?>

            <div class="upload-form">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="projeto_id">Projeto</label>
                        <select name="projeto_id" id="projeto_id" class="form-control" required>
                            <option value="">-- Selecione um projeto --</option>
                            <?php 
                            if ($projetos_upload) {
                                $projetos_upload->data_seek(0);
                                while ($projeto = $projetos_upload->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $projeto['id']; ?>">
                                    #<?php echo $projeto['id']; ?> - <?php echo htmlspecialchars($projeto['titulo']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>

                    <?php
                    // Incluir o sistema de upload de múltiplos arquivos
                    if (file_exists('../includes/upload_files_include.php')) {
                        // Configurar o include de upload
                        $upload_config = [
                            'max_file_size' => 50 * 1024 * 1024, // 50MB
                            'allowed_types' => [
                                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
                                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/plain', 'application/zip', 'application/x-rar-compressed'
                            ],
                            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
                            'field_name' => 'arquivos',
                            'label' => 'Documentos do Projeto:',
                            'accept_attr' => '.jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar',
                            'description' => 'Tipos aceitos: Imagens, PDFs, Documentos do Office, Arquivos de texto, ZIPs<br>Tamanho máximo por arquivo: 50MB',
                            'multiple' => true,
                            'required' => true
                        ];
                        $upload_id = 'cliente_upload';
                        include '../includes/upload_files_include.php';
                    } else {
                        // Fallback para input simples se o include não existir
                        ?>
                        <div class="form-group">
                            <label for="arquivos">Arquivos</label>
                            <input type="file" name="arquivos[]" id="arquivos" class="form-control" multiple required 
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                            <small style="color: #6c757d; font-size: 0.875rem;">
                                Formatos permitidos: JPG, PNG, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR (Máx: 50MB por arquivo)
                            </small>
                        </div>
                        <?php
                    }
                    ?>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" name="enviar_arquivo" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                            </svg>
                            Enviar Documentos
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <section class="contact">
            <div class="text">
                <h2>Precisa conversar?</h2>
                <p>Acesse o chat</p>
            </div>
            <button><a href="chat.php" style="text-decoration: none; color: white;">Acesse aqui</a></button>
        </section>

        <section id="contato-section" class="contatos">
            <div class="contato-section">
                <div class="texto">
                    <h3>Entre em Contato</h3>
                </div>
                <div class="redes">
                    <div class="rede">
                        <img src="../assets/img/emailroxo.png" alt="Email">
                        <p>Email</p>
                    </div>
                    <div class="rede">
                        <img src="../assets/img/linkedinroxo.png" alt="Linkedin">
                        <p>Linkedin</p>
                    </div>
                    <div class="rede">
                        <img src="../assets/img/zaproxo.png" alt="Whatsapp">
                        <p>Whatsapp</p>
                    </div>
                    <div class="rede">
                        <img src="../assets/img/instaroxo.png" alt="Instagram">
                        <p>Instagram</p>
                    </div>
                </div>
            </div>
        </section>

        <footer class="rodape">
            <div class="pages">
                <a href="index.html">Home</a>
                <a href="projetos.html">Projetos</a>
                <a href="#contato-section">Entre em contato</a>
                <a href="../logout.php">Sair</a>
            </div>
            <div class="logo-rodape">
                <img src="../assets/img/logobranca.png" alt="Logo Augebit">
                <h1>AUGEBIT</h1>
                <p>Industrial design</p>
            </div>
            <div class="redescociais">
                <img src="../assets/img/emailbranco.png" alt="Email">
                <img src="../assets/img/instabranco.png" alt="Instagram">
                <img src="../assets/img/linkedinbranco.png" alt="Linkedin">
                <img src="../assets/img/zapbranco.png" alt="Whatsapp">
            </div>
        </footer>
    </div>

    <?php
    // Fechar conexões se existirem
    if (isset($conn)) $conn->close();
    ?>

    <script>
    // Adicionar validação JavaScript se o sistema de upload avançado estiver disponível
    if (typeof getSelectedFiles_cliente_upload === 'function') {
        // Adicionar confirmação antes do envio
        document.querySelector('form').addEventListener('submit', function(e) {
            const arquivos = getSelectedFiles_cliente_upload();
            const projeto = document.getElementById('projeto_id').value;
            
            if (!projeto) {
                alert('Por favor, selecione um projeto antes de enviar os documentos.');
                e.preventDefault();
                return;
            }
            
            if (arquivos.length === 0) {
                alert('Por favor, selecione pelo menos um arquivo para enviar.');
                e.preventDefault();
                return;
            }
            
            const confirmacao = confirm(`Deseja enviar ${arquivos.length} arquivo(s) para o projeto selecionado?`);
            if (!confirmacao) {
                e.preventDefault();
            }
        });
    } else {
        // Validação simples para fallback
        document.querySelector('form').addEventListener('submit', function(e) {
            const projeto = document.getElementById('projeto_id').value;
            const arquivos = document.getElementById('arquivos') ? document.getElementById('arquivos').files : [];
            
            if (!projeto) {
                alert('Por favor, selecione um projeto antes de enviar os documentos.');
                e.preventDefault();
                return;
            }
            
            if (arquivos.length === 0) {
                alert('Por favor, selecione pelo menos um arquivo para enviar.');
                e.preventDefault();
                return;
            }
            
            const confirmacao = confirm(`Deseja enviar ${arquivos.length} arquivo(s) para o projeto selecionado?`);
            if (!confirmacao) {
                e.preventDefault();
            }
        });
    }
    </script>
</body>
</html>