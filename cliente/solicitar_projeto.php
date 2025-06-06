<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../login.php");
    exit;
}
require '../conexao.php';
include '../conexao.php';
require '../includes/upload_processor.php';

// Configuração personalizada
$mensagem = '';
$erro = '';

// Configuração personalizada para este formulário (opcional)
$upload_config = [
    'max_file_size' => 50 * 1024 * 1024, // 10MB por arquivo (personalizado)
    'allowed_types' => [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword'
    ],
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'],
    'field_name' => 'documentos', // Nome personalizado do campo
    'label' => 'Anexar documentos:',
    'accept_attr' => '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx',
    'description' => 'Tipos aceitos: Imagens, PDFs e DOCs<br>Tamanho máximo: 50MB por arquivo',
    'multiple' => true,
    'required' => false
];

// ID único para este upload (importante se houver múltiplos uploads na página)
$upload_id = 'documentos_projeto';

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']) ?? null;

    if (empty($titulo)) {
        $erro = "Título é obrigatório.";
    } else {
        // Inserir projeto
        $stmt = $conn->prepare("INSERT INTO projetos (titulo, descricao, cliente_id, status) VALUES (?, ?, ?, 'pendente')");
        $cliente_id = $_SESSION['usuario_id'];
        $stmt->bind_param("ssi", $titulo, $descricao, $cliente_id);

        if ($stmt->execute()) {
            $projeto_id = $stmt->insert_id;

            // Criar instância do processador de upload com configuração personalizada
            $uploadProcessor = new UploadProcessor([
                'max_file_size' => $upload_config['max_file_size'],
                'allowed_types' => $upload_config['allowed_types'],
                'allowed_extensions' => $upload_config['allowed_extensions'],
                'upload_dir' => '../uploads/',
                'field_name' => $upload_config['field_name']
            ]);

            // Processar arquivos
            $resultado = $uploadProcessor->processarMultiplosArquivos($projeto_id, $titulo, $conn);

            if ($resultado['arquivos_processados'] > 0) {
                $mensagem = "Projeto criado com sucesso! " . $resultado['mensagem'];
            } else {
                $mensagem = "Projeto criado com sucesso!";
            }

            if (!empty($resultado['erros'])) {
                $erro = implode("\n", $resultado['erros']);
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

            <?php include '../includes/upload_files_include.php'; ?>

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