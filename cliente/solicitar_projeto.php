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
$MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB por arquivo
$ALLOWED_TYPES = [
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
    'application/pdf'
];
$ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'];

// Função para criar diretório se não existir
function criarDiretorio($caminho) {
    if (!is_dir($caminho)) {
        mkdir($caminho, 0755, true);
    }
}

// Função para sanitizar nome de diretório
function sanitizarNomeDiretorio($nome) {
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
function validarArquivo($arquivo, $allowedTypes, $allowedExtensions, $maxSize) {
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
    <link rel="stylesheet" href="../style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 40px;
            text-align: center;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }
        
        .upload-area.dragover {
            border-color: #007bff;
            background: #e6f3ff;
        }
        
        .file-input {
            display: none;
        }
        
        .upload-info {
            margin-top: 10px;
            font-size: 0.9em;
            color: #666;
        }
        
        .preview {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .preview-item {
            border: 1px solid #ccc;
            padding: 10px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        .preview-item img,
        .preview-item iframe {
            max-width: 100%;
            max-height: 150px;
            object-fit: contain;
            border-radius: 4px;
        }
        
        .preview-item .file-name {
            margin-top: 8px;
            font-size: 0.85em;
            text-align: center;
            word-break: break-word;
            max-width: 100%;
        }
        
        .preview-item .file-size {
            margin-top: 4px;
            font-size: 0.75em;
            color: #666;
        }
        
        .remove-file {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-file:hover {
            background: #cc0000;
        }
        
        .file-counter {
            margin-top: 10px;
            font-weight: bold;
            color: #007bff;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            white-space: pre-line;
        }
        
        .submit-btn {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            background: #0056b3;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
            display: none;
        }
        
        .progress-fill {
            height: 100%;
            background: #007bff;
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
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