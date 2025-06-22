<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../login.php");
    exit;
}
require '../conexao.php';


$mensagem = '';
$erro = '';

// Configurações de upload
$MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB por arquivo

$ALLOWED_TYPES = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/bmp',
    'application/pdf',
    'application/msword',                      
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/acad',                        
    'application/x-acad',
    'application/autocad_dwg',
    'image/vnd.dwg',                            
];

$ALLOWED_EXTENSIONS = [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp',
    'bmp',
    'pdf',
    'doc',
    'docx',
    'dwg'
];

// Função para criar diretório se não existir
function criarDiretorio($caminho)
{
    if (!is_dir($caminho)) {
        mkdir($caminho, 0755, true);
    }
}

// Função para sanitizar nome de diretório
function sanitizarNomeDiretorio($nome)
{
    // Remove caracteres especiais e mantém apenas letras, números, espaços, hífens e underscores
    $nome_limpo = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $nome);
    // Substitui espaços múltiplos por um único espaço
    $nome_limpo = preg_replace('/\s+/', ' ', $nome_limpo);
    // Substitui espaços por underscores
    $nome_limpo = str_replace(' ', '_', $nome_limpo);
    // Remove underscores múltiplos
    $nome_limpo = preg_replace('/_+/', '_', $nome_limpo);
    // Remove underscores no início e fim
    $nome_limpo = trim($nome_limpo, '_');
    // Limita o tamanho do nome (opcional)
    $nome_limpo = substr($nome_limpo, 0, 50);

    return $nome_limpo;
}

// Função para validar arquivo
function validarArquivo($arquivo, $allowedTypes, $allowedExtensions, $maxSize)
{
    $erros = [];

    // Verificar se houve erro no upload
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        switch ($arquivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $erros[] = "Arquivo muito grande.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $erros[] = "Upload incompleto.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $erros[] = "Nenhum arquivo enviado.";
                break;
            default:
                $erros[] = "Erro no upload.";
        }
        return $erros;
    }

    // Verificar tamanho
    if ($arquivo['size'] > $maxSize) {
        $erros[] = "Arquivo excede o tamanho máximo de " . ($maxSize / 1024 / 1024) . "MB.";
    }

    // Verificar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $erros[] = "Tipo de arquivo não permitido. Apenas imagens e PDFs são aceitos.";
    }

    // Verificar extensão
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, $allowedExtensions)) {
        $erros[] = "Extensão de arquivo não permitida.";
    }

    return $erros;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']) ?? null;
    $prazo = $_POST['prazo'] ?? null;
    $cliente_id = $_SESSION['usuario_id'];

    // Validações básicas
    if (empty($titulo)) {
        $erro = "Título é obrigatório.";
    } else {
        // Inserir projeto
        $stmt = $conn->prepare("INSERT INTO projetos (titulo, descricao, data_fim, cliente_id, status) VALUES (?, ?, ?, ?, 'pendente')");
        $stmt->bind_param("sssi", $titulo, $descricao, $prazo, $cliente_id);

        if ($stmt->execute()) {
            $projeto_id = $stmt->insert_id;

            // Criar nome do diretório baseado no título do projeto
            $nome_diretorio = sanitizarNomeDiretorio($titulo);

            // Se após sanitização o nome ficar vazio, usar fallback com ID
            if (empty($nome_diretorio)) {
                $nome_diretorio = "projeto_" . $projeto_id;
            } else {
                // Adicionar ID ao final para garantir unicidade
                $nome_diretorio = $nome_diretorio . "_" . $projeto_id;
            }

            // Criar diretório específico para o projeto
            $diretorio_projeto = "../uploads/" . $nome_diretorio;
            criarDiretorio($diretorio_projeto);

            $arquivos_processados = 0;
            $erros_upload = [];

            // Processar múltiplos arquivos
            if (!empty($_FILES['arquivos']['name'][0])) {
                $total_arquivos = count($_FILES['arquivos']['name']);

                for ($i = 0; $i < $total_arquivos; $i++) {
                    // Montar array do arquivo individual
                    $arquivo = [
                        'name' => $_FILES['arquivos']['name'][$i],
                        'type' => $_FILES['arquivos']['type'][$i],
                        'tmp_name' => $_FILES['arquivos']['tmp_name'][$i],
                        'error' => $_FILES['arquivos']['error'][$i],
                        'size' => $_FILES['arquivos']['size'][$i]
                    ];

                    // Pular arquivos vazios
                    if (empty($arquivo['name'])) continue;

                    // Validar arquivo
                    $erros_validacao = validarArquivo($arquivo, $ALLOWED_TYPES, $ALLOWED_EXTENSIONS, $MAX_FILE_SIZE);

                    if (!empty($erros_validacao)) {
                        $erros_upload[] = $arquivo['name'] . ": " . implode(", ", $erros_validacao);
                        continue;
                    }

                    // Gerar nome único para o arquivo
                    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                    $nome_limpo = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($arquivo['name'], PATHINFO_FILENAME));
                    $nome_unico = uniqid() . "_" . time() . "_" . $nome_limpo . "." . $extensao;
                    $caminho_completo = $diretorio_projeto . "/" . $nome_unico;
                    $caminho_relativo = "uploads/" . $nome_diretorio . "/" . $nome_unico;

                    // Mover arquivo
                    if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
                        // Inserir no banco
                        $insert = $conn->prepare("INSERT INTO uploads (nome_arquivo, caminho_arquivo, projeto_id, tamanho_arquivo, tipo_mime) VALUES (?, ?, ?, ?, ?)");
                        $tipo_mime = mime_content_type($caminho_completo);
                        $insert->bind_param("sssis", $arquivo['name'], $caminho_relativo, $projeto_id, $arquivo['size'], $tipo_mime);

                        if ($insert->execute()) {
                            $arquivos_processados++;
                        } else {
                            $erros_upload[] = $arquivo['name'] . ": Erro ao salvar no banco de dados.";
                            // Remove arquivo se não conseguiu salvar no banco
                            unlink($caminho_completo);
                        }
                        $insert->close();
                    } else {
                        $erros_upload[] = $arquivo['name'] . ": Erro ao mover arquivo.";
                    }
                }
            }

            // Mensagem de resultado
            if ($arquivos_processados > 0) {
                $mensagem = "Projeto criado com sucesso! $arquivos_processados arquivo(s) enviado(s).";
            } else {
                $mensagem = "Projeto criado com sucesso!";
            }

            if (!empty($erros_upload)) {
                $erro = "Alguns arquivos não puderam ser enviados:\n" . implode("\n", $erros_upload);
            }
        } else {
            $erro = "Erro ao criar projeto: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Novo Projeto</title>
    <style>
        /* Estilo totalmente roxo como solicitado */

        body {
            background: linear-gradient(135deg,
                    rgb(223, 223, 252) 0%,
                    rgb(240, 240, 240) 30%,
                    #FFFFFF 100%);
            background-attachment: fixed;
            min-height: 100vh;
            backdrop-filter: blur(50%);
            /* Aumente o valor para mais blur (ex: 50px) */
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonte/Poppins-SemiBold.ttf') format('truetype');
            font-weight: 600;
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

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 30px;
            background-color: rgba(252, 252, 252, 0.75);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(62, 35, 106, 0.42);
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
            left: 30px
        }

        .logo-header h1 {
            position: relative;
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

        .container {
            margin-top: 120px;

        }

        h2 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            margin-left: 290px;
            font-size: 24px;
            border-bottom: 2px solid var(--secondary-light);
            padding-bottom: 10px;
        }

        /* Formulário */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            margin-top: 30px;
            font-weight: 600;
            color: #3E236A;
        }

        .form-group input,
        .form-group textarea,
        .form-group select,
        .form-group input[type="date"] {
            width: 96%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-family: inherit;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: rgb(214, 214, 255);
            color: #3E236A;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-main);
            box-shadow: 0 0 0 3px rgba(124, 77, 255, 0.2);
        }

        /* Área de Upload */
        .upload-area {
            border: 2px dashed var(--primary-main);
            border-radius: 12px;
            border: 2px solid #9999FF;
            ;
            padding: 25px;
            text-align: center;
            background-color: rgba(252, 252, 252, 0.96);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }



        .upload-area:hover {
            background-color: rgba(179, 136, 255, 0.1);
            border-color: var(--primary-dark);
        }

        .upload-area.dragover {
            background-color: rgba(179, 136, 255, 0.2);
            border-color: var(--primary-dark);
        }

        .upload-info {
            margin-top: 15px;
            font-size: 14px;
            color: var(--text-light);
        }

        /* Pré-visualização de Arquivos */
        .preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding: 20px;
            background-color: rgba(213, 196, 233, 0.3);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .preview-item {
            position: relative;
            background: var(--light-color);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .preview-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(149, 117, 205, 0.2);
        }

        .preview-item img {
            max-width: 100%;
            max-height: 100px;
            object-fit: contain;
            border-radius: 4px;
        }

        .file-name {
            margin-top: 10px;
            font-size: 13px;
            word-break: break-word;
            color: var(--text-color);
        }

        .file-size {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }

        .remove-file {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 22px;
            height: 22px;
            background: var(--error-color);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .remove-file:hover {
            background: #b71c1c;
            transform: scale(1.1);
        }

        /* Contador e Barra de Progresso */
        .file-counter {
            margin-top: 15px;
            font-size: 14px;
            color: var(--primary-dark);
            font-weight: 600;
        }

        .progress-bar {
            height: 8px;
            background: var(--secondary-light);
            border-radius: 4px;
            margin-top: 15px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-main), var(--primary-dark));
            width: 0%;
            transition: width 0.3s ease;
        }

        /* Mensagens */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
        }

        .message.success {
            background-color: rgba(179, 136, 255, 0.15);
            color: var(--primary-dark);
            border: 1px solid var(--primary-main);
        }

        .message.error {
            background-color: rgba(211, 47, 47, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
            white-space: pre-line;
        }

        /* Botão */
        .submit-btn {
            background: #3E236A;
            color: white;
            border-radius: 30px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            margin-left: 290px;
            margin-right: 0;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(124, 77, 255, 0.3);
            background: rgb(47, 28, 79);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 15px;
            }

            .preview {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .upload-area {
                padding: 20px;
            }

            h2 {
                font-size: 20px;
            }

            .submit-btn {
                width: 100%;
                padding: 15px;
            }
        }

        .rodape {
            background-color: #9999FF;
            width: 100vw;
            /* Usa a largura total da viewport */
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
            /* Faz cada link ocupar sua própria linha */
            margin-bottom: 15px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            font-family: "Poppins";
            font-weight: 300;
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
    </style>
</head>

<body>
    <header>
        <div class="logo-header">
            <img src="../assets/img/augebit.logo.png" alt="Logo da empresa">

        </div>
        <nav>
            <ul>
                <li><a href="#" class="active">Home</a></li>
                <li><a href="dashboard_cliente.php" class="acount">Projetos</a></li>
                <li><a href="#contato-section" class="contato">Sair</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h2>Solicitar Novo Projeto</h2>

        <?php if ($mensagem): ?>
            <div class="message success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="message error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="projectForm">
            <div class="form-group">
                <label for="titulo">Título do Projeto:</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4" placeholder="Descreva os detalhes do seu projeto..."></textarea>
            </div>

            <div class="form-group">
                <label for="prazo">Prazo estimado:</label>
                <input type="date" id="prazo" name="prazo">
            </div>

            <div class="form-group">
                <label>Anexar arquivos (Imagens e PDFs):</label>
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <div>
                        📁 Clique aqui ou arraste arquivos para fazer upload
                    </div>
                    <div class="upload-info">
                        Tipos aceitos: JPG, PNG, GIF, WebP, BMP, PDF<br>
                        Tamanho máximo por arquivo: 50MB
                    </div>
                </div>
                <input type="file" id="fileInput" name="arquivos[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf" class="file-input" onchange="previewArquivos(event)">

                <div class="file-counter" id="fileCounter" style="display: none;">
                    0 arquivo(s) selecionado(s)
                </div>

                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </div>

            <div class="preview" id="previewContainer" style="display: none;"></div>

            <button type="submit" class="submit-btn">Enviar Solicitação</button>
        </form>

    </div>
    <footer class="rodape">
        <div class="pages">
            <a href="index.html">Home</a>
            <a href="projetos.html">Projetos</a>
            <a href="#contato-section">Entre em contato</a>
            <a href="logout.php">Sair</a>
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
    <script>
        let selectedFiles = [];

        // Drag and drop functionality
        const uploadArea = document.querySelector('.upload-area');
        const fileInput = document.getElementById('fileInput');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
        });

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function previewArquivos(event) {
            handleFiles(event.target.files);
        }

        function handleFiles(files) {
            selectedFiles = Array.from(files);
            updateFileInput();
            displayPreviews();
            updateCounter();
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        function displayPreviews() {
            const container = document.getElementById('previewContainer');
            container.innerHTML = '';

            if (selectedFiles.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'grid';

            selectedFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.classList.add('preview-item');

                const removeBtn = document.createElement('button');
                removeBtn.classList.add('remove-file');
                removeBtn.innerHTML = '×';
                removeBtn.onclick = (e) => {
                    e.preventDefault();
                    removeFile(index);
                };

                const fileName = document.createElement('div');
                fileName.classList.add('file-name');
                fileName.textContent = file.name.length > 30 ?
                    file.name.slice(0, 30) + '...' : file.name;

                const fileSize = document.createElement('div');
                fileSize.classList.add('file-size');
                fileSize.textContent = formatFileSize(file.size);

                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.onload = () => URL.revokeObjectURL(img.src);
                    div.appendChild(img);
                } else if (file.type === 'application/pdf') {
                    const pdfIcon = document.createElement('div');
                    pdfIcon.innerHTML = '📄';
                    pdfIcon.style.fontSize = '48px';
                    pdfIcon.style.marginBottom = '10px';
                    div.appendChild(pdfIcon);
                } else {
                    const genericIcon = document.createElement('div');
                    genericIcon.innerHTML = '📎';
                    genericIcon.style.fontSize = '48px';
                    genericIcon.style.marginBottom = '10px';
                    div.appendChild(genericIcon);
                }

                div.appendChild(removeBtn);
                div.appendChild(fileName);
                div.appendChild(fileSize);
                container.appendChild(div);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileInput();
            displayPreviews();
            updateCounter();
        }

        function updateCounter() {
            const counter = document.getElementById('fileCounter');
            if (selectedFiles.length > 0) {
                counter.style.display = 'block';
                counter.textContent = `${selectedFiles.length} arquivo(s) selecionado(s)`;
            } else {
                counter.style.display = 'none';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Progress bar simulation (opcional)
        document.getElementById('projectForm').addEventListener('submit', function() {
            if (selectedFiles.length > 0) {
                const progressBar = document.getElementById('progressBar');
                const progressFill = document.getElementById('progressFill');
                progressBar.style.display = 'block';

                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress >= 90) {
                        clearInterval(interval);
                        progress = 90;
                    }
                    progressFill.style.width = progress + '%';
                }, 200);
            }
        });
    </script>
</body>

</html>