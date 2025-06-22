<?php
session_start();
require_once '../../conexao.php';
require_once '../../includes/upload_processor.php';


$mensagem = '';
$erro = '';

// Buscar projetos para o select
$projetos_query = "SELECT p.id, p.titulo, u.nome as cliente_nome 
                   FROM projetos p 
                   LEFT JOIN usuarios u ON p.cliente_id = u.id 
                   ORDER BY p.id DESC";
$projetos_result = $conn->query($projetos_query);

// Processar envio de arquivos
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
                'upload_dir' => '../../uploads/',
                'field_name' => 'documentos'
            ];
            
            $processor = new UploadProcessor($upload_config);
            $resultado = $processor->processarMultiplosArquivos($projeto_id, $titulo_projeto);
            
            if ($resultado['sucesso'] && $resultado['arquivos_processados'] > 0) {
                // Atualizar tabela uploads com informações do usuário
                $update_uploads = $conn->prepare("UPDATE uploads SET enviado_por = ? WHERE projeto_id = ? AND enviado_por IS NULL");
                $update_uploads->bind_param("ii", $usuario_id, $projeto_id);
                $update_uploads->execute();
                
                $mensagem = $resultado['mensagem'];
            } else {
                $erro = !empty($resultado['erros']) ? implode("<br>", $resultado['erros']) : "Erro ao processar arquivos.";
            }
        } else {
            $erro = "Projeto não encontrado.";
        }
        $projeto_stmt->close();
    }
}

// Buscar arquivos enviados recentemente (corrigido)
$arquivos_recentes_query = "SELECT u.*, p.titulo as projeto_titulo, us.nome as enviado_por_nome
                           FROM uploads u
                           LEFT JOIN projetos p ON u.projeto_id = p.id
                           LEFT JOIN usuarios us ON u.enviado_por = us.id
                           WHERE u.enviado_por IS NOT NULL
                           ORDER BY u.enviado_em DESC
                           LIMIT 10";
$arquivos_recentes = $conn->query($arquivos_recentes_query);

// Consultas para estatísticas (corrigidas)
$total_projetos = $conn->query("SELECT COUNT(*) as total FROM projetos")->fetch_assoc()['total'];
$total_uploads = $conn->query("SELECT COUNT(*) as total FROM uploads WHERE enviado_por IS NOT NULL")->fetch_assoc()['total'];
$uploads_hoje = $conn->query("SELECT COUNT(*) as total FROM uploads WHERE enviado_por IS NOT NULL AND DATE(enviado_em) = CURDATE()")->fetch_assoc()['total'];
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