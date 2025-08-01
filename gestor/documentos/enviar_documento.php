<?php
session_start();
require_once '../../conexao.php';

$mensagem = '';
$erro = '';

// Buscar projetos para o select
$projetos_query = "SELECT p.id, p.titulo, u.nome as cliente_nome 
                   FROM projetos p 
                   LEFT JOIN usuarios u ON p.cliente_id = u.id 
                   ORDER BY p.id DESC";
$projetos_result = $conn->query($projetos_query);

// PROCESSAR UPLOAD DE ARQUIVO - IMPLEMENTAÇÃO DIRETA (SEM DEPENDÊNCIAS EXTERNAS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_documentos'])) {
    $projeto_id = (int)$_POST['projeto_id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    if ($projeto_id <= 0) {
        $erro = "Selecione um projeto válido.";
    } else {
        // Buscar título do projeto para criar diretório
        $projeto_stmt = $conn->prepare("SELECT titulo FROM projetos WHERE id = ?");
        $projeto_stmt->bind_param("i", $projeto_id);
        $projeto_stmt->execute();
        $projeto_result = $projeto_stmt->get_result();
        
        if ($projeto_data = $projeto_result->fetch_assoc()) {
            $titulo_projeto = $projeto_data['titulo'];
            
            // PROCESSAR UPLOAD DIRETAMENTE (SEM DEPENDÊNCIAS EXTERNAS)
            if (!empty($_FILES['documentos']['name'][0])) {
                // Configurações de upload
                $max_file_size = 50 * 1024 * 1024; // 50MB
                $allowed_types = [
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
                    'application/pdf', 'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain', 'application/zip', 'application/x-rar-compressed'
                ];
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
                
                // Criar nome do diretório
                $nome_projeto_sanitizado = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $titulo_projeto);
                $nome_projeto_sanitizado = str_replace(' ', '_', $nome_projeto_sanitizado);
                $nome_projeto_sanitizado = preg_replace('/_+/', '_', trim($nome_projeto_sanitizado, '_'));
                
                if (empty($nome_projeto_sanitizado)) {
                    $nome_projeto_sanitizado = "projeto";
                }
                
                $nome_diretorio = $nome_projeto_sanitizado . "_" . $projeto_id;
                $diretorio_projeto = "../../uploads/" . $nome_diretorio;
                
                // Criar diretório se não existir
                if (!is_dir($diretorio_projeto)) {
                    mkdir($diretorio_projeto, 0755, true);
                }
                
                $arquivos_processados = 0;
                $erros_upload = [];
                $total_arquivos = count($_FILES['documentos']['name']);
                
                // Processar cada arquivo
                for ($i = 0; $i < $total_arquivos; $i++) {
                    $arquivo = [
                        'name' => $_FILES['documentos']['name'][$i],
                        'type' => $_FILES['documentos']['type'][$i],
                        'tmp_name' => $_FILES['documentos']['tmp_name'][$i],
                        'error' => $_FILES['documentos']['error'][$i],
                        'size' => $_FILES['documentos']['size'][$i]
                    ];
                    
                    // Pular arquivos vazios
                    if (empty($arquivo['name'])) continue;
                    
                    // Validar arquivo
                    $erros_validacao = [];
                    
                    // Verificar erro de upload
                    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                        switch ($arquivo['error']) {
                            case UPLOAD_ERR_INI_SIZE:
                            case UPLOAD_ERR_FORM_SIZE:
                                $erros_validacao[] = "Arquivo muito grande";
                                break;
                            case UPLOAD_ERR_PARTIAL:
                                $erros_validacao[] = "Upload incompleto";
                                break;
                            default:
                                $erros_validacao[] = "Erro no upload";
                        }
                    }
                    
                    // Verificar tamanho
                    if ($arquivo['size'] > $max_file_size) {
                        $erros_validacao[] = "Arquivo excede 50MB";
                    }
                    
                    // Verificar extensão
                    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                    if (!in_array($extensao, $allowed_extensions)) {
                        $erros_validacao[] = "Extensão não permitida";
                    }
                    
                    // Verificar tipo MIME
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $arquivo['tmp_name']);
                        finfo_close($finfo);
                        
                        if (!in_array($mime_type, $allowed_types)) {
                            $erros_validacao[] = "Tipo de arquivo não permitido";
                        }
                    }
                    
                    if (!empty($erros_validacao)) {
                        $erros_upload[] = $arquivo['name'] . ": " . implode(", ", $erros_validacao);
                        continue;
                    }
                    
                    // Gerar nome único para o arquivo
                    $nome_limpo = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($arquivo['name'], PATHINFO_FILENAME));
                    $nome_unico = uniqid() . "_" . time() . "_" . $nome_limpo . "." . $extensao;
                    $caminho_completo = $diretorio_projeto . "/" . $nome_unico;
                    $caminho_relativo = "uploads/" . $nome_diretorio . "/" . $nome_unico;
                    
                    // Mover arquivo
                    if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
                        // Obter tipo MIME real do arquivo salvo
                        $tipo_mime_real = mime_content_type($caminho_completo);
                        
                        // INSERIR NO BANCO DE DADOS - CORRIGIDO
                        $insert_sql = "INSERT INTO uploads (nome_arquivo, caminho_arquivo, projeto_id, tamanho_arquivo, tipo_mime, enviado_por, status_arquivo) VALUES (?, ?, ?, ?, ?, ?, 'ativo')";
                        $insert_stmt = $conn->prepare($insert_sql);
                        
                        if ($insert_stmt) {
                            $insert_stmt->bind_param("ssisii", 
                                $arquivo['name'],           // nome original
                                $caminho_relativo,          // caminho relativo
                                $projeto_id,                // ID do projeto
                                $arquivo['size'],           // tamanho
                                $tipo_mime_real,            // tipo MIME
                                $usuario_id                 // gestor que enviou
                            );
                            
                            if ($insert_stmt->execute()) {
                                $arquivos_processados++;
                            } else {
                                $erros_upload[] = $arquivo['name'] . ": Erro ao salvar no banco - " . $conn->error;
                                // Remove arquivo se não conseguiu salvar no banco
                                unlink($caminho_completo);
                            }
                            $insert_stmt->close();
                        } else {
                            $erros_upload[] = $arquivo['name'] . ": Erro na preparação da query - " . $conn->error;
                            unlink($caminho_completo);
                        }
                    } else {
                        $erros_upload[] = $arquivo['name'] . ": Erro ao mover arquivo";
                    }
                }
                
                // Gerar mensagem de resultado
                if ($arquivos_processados > 0) {
                    $mensagem = "$arquivos_processados arquivo(s) enviado(s) com sucesso!";
                    
                    // Definir mensagem de sessão para após redirect
                    $_SESSION['success_message'] = $mensagem;
                }
                
                if (!empty($erros_upload)) {
                    if ($arquivos_processados > 0) {
                        $erro = "Alguns arquivos não puderam ser enviados: " . implode("; ", $erros_upload);
                    } else {
                        $erro = "Nenhum arquivo pôde ser enviado: " . implode("; ", $erros_upload);
                    }
                    $_SESSION['error_message'] = $erro;
                }
                
                // Redirect para evitar resubmissão
                if ($arquivos_processados > 0 || !empty($erros_upload)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
                
            } else {
                $erro = "Nenhum arquivo selecionado.";
            }
        } else {
            $erro = "Projeto não encontrado.";
        }
        $projeto_stmt->close();
    }
}

// Verificar mensagens de sessão
if (isset($_SESSION['success_message'])) {
    $mensagem = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $erro = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Buscar arquivos enviados recentemente (corrigido)
$arquivos_recentes_query = "SELECT u.*, p.titulo as projeto_titulo, us.nome as enviado_por_nome
                           FROM uploads u
                           LEFT JOIN projetos p ON u.projeto_id = p.id
                           LEFT JOIN usuarios us ON u.enviado_por = us.id
                           WHERE u.enviado_por IS NOT NULL
                           ORDER BY u.data_upload DESC
                           LIMIT 10";
$arquivos_recentes = $conn->query($arquivos_recentes_query);

// Consultas para estatísticas (corrigidas)
$total_projetos = $conn->query("SELECT COUNT(*) as total FROM projetos")->fetch_assoc()['total'];
$total_uploads = $conn->query("SELECT COUNT(*) as total FROM uploads WHERE enviado_por IS NOT NULL")->fetch_assoc()['total'];
$uploads_hoje = $conn->query("SELECT COUNT(*) as total FROM uploads WHERE enviado_por IS NOT NULL AND DATE(data_upload) = CURDATE()")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor - Envio de Documentos</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins';
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .header h1 {
            font-family: 'Poppins';
            color: #5e35b1;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-family: 'Poppins';
            font-size: 16px;
            color: #5e35b1;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            background: white;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: #5e35b2;
        }

        .btn {
            background: #5e35b1;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .arquivos-recentes {
            max-height: 400px;
            overflow-y: auto;
        }

        .arquivo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 10px;
            background: #fafafa;
            transition: all 0.3s ease;
        }

        .arquivo-item:hover {
            background: #f0f0f0;
            transform: translateX(5px);
        }

        .arquivo-info {
            flex: 1;
        }

        .arquivo-nome {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .arquivo-detalhes {
            font-size: 14px;
            color: #666;
        }

        .arquivo-data {
            font-size: 12px;
            color: #999;
            text-align: right;
            min-width: 120px;
        }

        .navigation {
            margin-bottom: 20px;
        }

        .navigation a {
            color: #3498db;
            text-decoration: none;
            margin-right: 15px;
            font-weight: 500;
        }

        .navigation a:hover {
            text-decoration: underline;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    <div class="main-content">
    <div class="container">
        <div class="header">
            <h1>Gestão de Documentos</h1>
            <p>Envie e gerencie documentos dos projetos</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-error">
                ❌ <?= $erro ?>
            </div>
        <?php endif; ?>


        <!-- Formulário de envio -->
        <div class="card">
            <h2>Enviar Documentos</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="projeto_id">Selecionar Projeto:</label>
                    <select name="projeto_id" id="projeto_id" required>
                        <option value="">-- Selecione um projeto --</option>
                        <?php while ($projeto = $projetos_result->fetch_assoc()): ?>
                            <option value="<?= $projeto['id'] ?>">
                                #<?= $projeto['id'] ?> - <?= htmlspecialchars($projeto['titulo']) ?>
                                <?php if ($projeto['cliente_nome']): ?>
                                    (Cliente: <?= htmlspecialchars($projeto['cliente_nome']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <?php
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
                    'field_name' => 'documentos',
                    'label' => 'Documentos do Projeto:',
                    'accept_attr' => '.jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar',
                    'description' => 'Tipos aceitos: Imagens, PDFs, Documentos do Office, Arquivos de texto, ZIPs<br>Tamanho máximo por arquivo: 50MB',
                    'multiple' => true,
                    'required' => true
                ];
                $upload_id = 'gestor_upload';
                include '../../includes/upload_files_include.php';
                ?>

                <div style="margin-top: 30px;">
                    <button type="submit" name="enviar_documentos" class="btn">
                        Enviar Documentos
                    </button>
                    <a href="../dashboard_gestor.php" class="btn btn-secondary" style="margin-left: 10px;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <!-- Arquivos enviados recentemente -->
        
    </div>
</div>
    <script>
        // Adicionar confirmação antes do envio
        document.querySelector('form').addEventListener('submit', function(e) {
            const arquivos = getSelectedFiles_gestor_upload();
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

        // Auto-refresh da página a cada 5 minutos para mostrar novos uploads
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>