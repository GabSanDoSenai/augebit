<?php
/**
 * Include para funcionalidade de upload de arquivos
 * Configurações padrão (podem ser sobrescritas antes do include)
 */

// Configurações padrão (podem ser redefinidas antes do include)
if (!isset($upload_config)) {
    $upload_config = [
        'max_file_size' => 50 * 1024 * 1024, // 50MB por arquivo
        'allowed_types' => [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
            'application/pdf'
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'],
        'field_name' => 'arquivos', // Nome do campo no formulário
        'label' => 'Anexar arquivos (Imagens e PDFs):',
        'accept_attr' => '.jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf',
        'description' => 'Tipos aceitos: JPG, PNG, GIF, WebP, BMP, PDF<br>Tamanho máximo por arquivo: 50MB',
        'multiple' => true,
        'required' => false
    ];
}

// Funções utilitárias para upload
if (!function_exists('criarDiretorio')) {
    function criarDiretorio($caminho) {
        if (!is_dir($caminho)) {
            mkdir($caminho, 0755, true);
        }
    }
}

if (!function_exists('sanitizarNomeDiretorio')) {
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
}

if (!function_exists('validarArquivo')) {
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
}

// Gerar ID único para esta instância do upload (para múltiplos uploads na mesma página)
$upload_id = isset($upload_id) ? $upload_id : 'upload_' . uniqid();
?>

<style>
.upload-area-<?= $upload_id ?> {
    border: 2px dashed #ddd;
    border-radius: 4px;
    padding: 40px;
    text-align: center;
    background: #f9f9f9;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area-<?= $upload_id ?>:hover {
    border-color: #007bff;
    background: #f0f8ff;
}

.upload-area-<?= $upload_id ?>.dragover {
    border-color: #007bff;
    background: #e6f3ff;
}

.file-input-<?= $upload_id ?> {
    display: none;
}

.upload-info {
    margin-top: 10px;
    font-size: 0.9em;
    color: #666;
}

.preview-<?= $upload_id ?> {
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

<div class="form-group">
    <label><?= htmlspecialchars($upload_config['label']) ?></label>
    <div class="upload-area-<?= $upload_id ?>" onclick="document.getElementById('fileInput_<?= $upload_id ?>').click()">
        <div>
            📁 Clique aqui ou arraste arquivos para fazer upload
        </div>
        <div class="upload-info">
            <?= $upload_config['description'] ?>
        </div>
    </div>
    <input type="file" 
           id="fileInput_<?= $upload_id ?>" 
           name="<?= $upload_config['field_name'] ?><?= $upload_config['multiple'] ? '[]' : '' ?>" 
           <?= $upload_config['multiple'] ? 'multiple' : '' ?>
           <?= $upload_config['required'] ? 'required' : '' ?>
           accept="<?= $upload_config['accept_attr'] ?>" 
           class="file-input-<?= $upload_id ?>" 
           onchange="previewArquivos_<?= $upload_id ?>(event)">
    
    <div class="file-counter" id="fileCounter_<?= $upload_id ?>" style="display: none;">
        0 arquivo(s) selecionado(s)
    </div>
    
    <div class="progress-bar" id="progressBar_<?= $upload_id ?>">
        <div class="progress-fill" id="progressFill_<?= $upload_id ?>"></div>
    </div>
</div>

<div class="preview-<?= $upload_id ?>" id="previewContainer_<?= $upload_id ?>" style="display: none;"></div>

<script>
(function() {
    let selectedFiles_<?= $upload_id ?> = [];
    
    // Drag and drop functionality
    const uploadArea = document.querySelector('.upload-area-<?= $upload_id ?>');
    const fileInput = document.getElementById('fileInput_<?= $upload_id ?>');
    
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
    
    window.previewArquivos_<?= $upload_id ?> = function(event) {
        handleFiles(event.target.files);
    }
    
    function handleFiles(files) {
        selectedFiles_<?= $upload_id ?> = Array.from(files);
        updateFileInput();
        displayPreviews();
        updateCounter();
    }
    
    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles_<?= $upload_id ?>.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    function displayPreviews() {
        const container = document.getElementById('previewContainer_<?= $upload_id ?>');
        container.innerHTML = '';
        
        if (selectedFiles_<?= $upload_id ?>.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'grid';
        
        selectedFiles_<?= $upload_id ?>.forEach((file, index) => {
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
        selectedFiles_<?= $upload_id ?>.splice(index, 1);
        updateFileInput();
        displayPreviews();
        updateCounter();
    }
    
    function updateCounter() {
        const counter = document.getElementById('fileCounter_<?= $upload_id ?>');
        if (selectedFiles_<?= $upload_id ?>.length > 0) {
            counter.style.display = 'block';
            counter.textContent = `${selectedFiles_<?= $upload_id ?>.length} arquivo(s) selecionado(s)`;
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
    
    // Expor função para usar em outros lugares
    window.getSelectedFiles_<?= $upload_id ?> = function() {
        return selectedFiles_<?= $upload_id ?>;
    }
    
    // Progress bar (se o formulário pai tiver o ID especificado)
    const parentForm = uploadArea.closest('form');
    if (parentForm) {
        parentForm.addEventListener('submit', function() {
            if (selectedFiles_<?= $upload_id ?>.length > 0) {
                const progressBar = document.getElementById('progressBar_<?= $upload_id ?>');
                const progressFill = document.getElementById('progressFill_<?= $upload_id ?>');
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
    }
})();
</script>