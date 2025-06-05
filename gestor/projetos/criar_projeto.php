<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../../conexao.php';

// Buscar clientes para o dropdown
$clientes_query = "SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente' ORDER BY nome";
$clientes = $conn->query($clientes_query);

// Buscar funcionários para o dropdown
$funcionarios_query = "SELECT id, nome, email FROM usuarios WHERE tipo = 'funcionario' ORDER BY nome";
$funcionarios = $conn->query($funcionarios_query);

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $cliente_id = $_POST['cliente_id'];
    $funcionarios_selecionados = isset($_POST['funcionarios']) ? $_POST['funcionarios'] : [];

    if (!empty($titulo) && !empty($cliente_id)) {
        // Inserir projeto
        $sql = "INSERT INTO projetos (titulo, descricao, cliente_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $titulo, $descricao, $cliente_id);

        if ($stmt->execute()) {
            $projeto_id = $conn->insert_id;
            
            // Atribuir funcionários ao projeto
            if (!empty($funcionarios_selecionados)) {
                foreach ($funcionarios_selecionados as $funcionario_id) {
                    $sql_atribuir = "INSERT INTO projetos_usuarios (projeto_id, funcionario_id) VALUES (?, ?)";
                    $stmt_atribuir = $conn->prepare($sql_atribuir);
                    $stmt_atribuir->bind_param("ii", $projeto_id, $funcionario_id);
                    $stmt_atribuir->execute();
                }
            }
            
            $mensagem = "Projeto criado com sucesso!";
            $tipo_mensagem = 'success';
        } else {
            $mensagem = "Erro ao criar projeto: " . $stmt->error;
            $tipo_mensagem = 'error';
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        $tipo_mensagem = 'error';
    }
}
?>

<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Projeto - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            margin: 0;
            font-size: 2.2em;
            font-weight: 300;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            border: 1px solid #e1e5e9;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .required {
            color: #e74c3c;
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-control.is-valid {
            border-color: #27ae60;
            background-color: #f8fff8;
        }

        .form-control.is-invalid {
            border-color: #e74c3c;
            background-color: #fff8f8;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            pointer-events: none;
        }

        select.form-control {
            appearance: none;
            background: #f8f9fa url('data:image/svg+xml;charset=US-ASCII,<svg viewBox="0 0 4 5" xmlns="http://www.w3.org/2000/svg"><path fill="%23667eea" d="m0 0 2 2 2-2z"/></svg>') no-repeat right 15px center;
            background-size: 12px;
            cursor: pointer;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .checkbox-item:hover {
            background: #e3f2fd;
            border-color: #667eea;
        }

        .checkbox-item input[type="checkbox"] {
            margin-right: 12px;
            transform: scale(1.2);
            accent-color: #667eea;
        }

        .checkbox-item.selected {
            background: #e8f2ff;
            border-color: #667eea;
            color: #2c3e50;
        }

        .user-info {
            font-size: 13px;
            color: #6c757d;
            margin-top: 4px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: flex-end;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
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

        .progress-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            display: flex;
            align-items: center;
            color: #6c757d;
        }

        .step.active {
            color: #667eea;
            font-weight: 600;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e1e5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            font-weight: 600;
        }

        .step.active .step-number {
            background: #667eea;
            color: white;
        }

        .char-counter {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .form-container {
                padding: 25px 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .checkbox-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Criar Novo Projeto</h1>
            <p>Defina os detalhes do projeto e atribua funcionários</p>
        </div>

        <div class="progress-indicator">
            <div class="step active">
                <div class="step-number">1</div>
                <span>Informações do Projeto</span>
            </div>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>">
                <i class="fas fa-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" id="projetoForm">
                <div class="form-group">
                    <label for="titulo">
                        <i class="fas fa-tag"></i> Título do Projeto
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="titulo" 
                           name="titulo" 
                           class="form-control" 
                           placeholder="Digite um título descritivo para o projeto"
                           maxlength="150"
                           required>
                    <div class="char-counter">
                        <span id="titulo-count">0</span>/150 caracteres
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">
                        <i class="fas fa-align-left"></i> Descrição do Projeto
                    </label>
                    <textarea id="descricao" 
                              name="descricao" 
                              class="form-control" 
                              placeholder="Descreva os objetivos, escopo e requisitos do projeto..."
                              maxlength="1000"></textarea>
                    <div class="char-counter">
                        <span id="descricao-count">0</span>/1000 caracteres
                    </div>
                </div>

                <div class="form-group">
                    <label for="cliente_id">
                        <i class="fas fa-user-tie"></i> Cliente
                        <span class="required">*</span>
                    </label>
                    <div class="select-wrapper">
                        <select id="cliente_id" name="cliente_id" class="form-control" required>
                            <option value="">Selecione um cliente</option>
                            <?php while ($cliente = $clientes->fetch_assoc()): ?>
                                <option value="<?= $cliente['id'] ?>">
                                    <?= htmlspecialchars($cliente['nome']) ?> (<?= htmlspecialchars($cliente['email']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-users"></i> Funcionários Responsáveis
                        <small style="font-weight: normal; color: #6c757d;">(Opcional - você pode atribuir depois)</small>
                    </label>
                    <div class="checkbox-group">
                        <?php 
                        $funcionarios->data_seek(0); // Reset pointer
                        while ($funcionario = $funcionarios->fetch_assoc()): 
                        ?>
                            <label class="checkbox-item" for="func_<?= $funcionario['id'] ?>">
                                <input type="checkbox" 
                                       id="func_<?= $funcionario['id'] ?>" 
                                       name="funcionarios[]" 
                                       value="<?= $funcionario['id'] ?>">
                                <div>
                                    <div><?= htmlspecialchars($funcionario['nome']) ?></div>
                                    <div class="user-info"><?= htmlspecialchars($funcionario['email']) ?></div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="../dashboard_gestor.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Criar Projeto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validação em tempo real
        const form = document.getElementById('projetoForm');
        const titulo = document.getElementById('titulo');
        const descricao = document.getElementById('descricao');
        const cliente = document.getElementById('cliente_id');

        // Contadores de caracteres
        const tituloCounter = document.getElementById('titulo-count');
        const descricaoCounter = document.getElementById('descricao-count');

        titulo.addEventListener('input', function() {
            tituloCounter.textContent = this.value.length;
            validateField(this);
        });

        descricao.addEventListener('input', function() {
            descricaoCounter.textContent = this.value.length;
        });

        cliente.addEventListener('change', function() {
            validateField(this);
        });

        function validateField(field) {
            if (field.value.trim() !== '') {
                field.classList.add('is-valid');
                field.classList.remove('is-invalid');
            } else if (field.hasAttribute('required')) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
            }
        }

        // Checkbox styling
        document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const item = this.closest('.checkbox-item');
                if (this.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
        });

        // Validação do formulário
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            if (titulo.value.trim() === '') {
                titulo.classList.add('is-invalid');
                isValid = false;
            }
            
            if (cliente.value === '') {
                cliente.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
            }
        });

        // Auto-resize textarea
        descricao.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>