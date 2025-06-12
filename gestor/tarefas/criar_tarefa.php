<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'funcionario')) {
    header("Location: ../login.php");
    exit;
}
include '../../conexao.php';

// Buscar projetos disponíveis
if ($_SESSION['usuario_tipo'] === 'admin') {
    // Admin vê todos os projetos
    $projetos_query = "SELECT p.id, p.titulo, u.nome as cliente_nome 
                       FROM projetos p 
                       LEFT JOIN usuarios u ON p.cliente_id = u.id 
                       WHERE p.status IN ('aprovado','em_andamento', 'ajustes')
                       ORDER BY p.titulo";
} else {
    // Funcionário vê apenas projetos atribuídos a ele
    $projetos_query = "SELECT p.id, p.titulo, u.nome as cliente_nome 
                       FROM projetos p 
                       LEFT JOIN usuarios u ON p.cliente_id = u.id
                       INNER JOIN projetos_usuarios pu ON p.id = pu.projeto_id
                       WHERE pu.funcionario_id = ? AND p.status IN ('aprovado','em_andamento', 'ajustes')
                       ORDER BY p.titulo";
}

$projetos_stmt = $conn->prepare($projetos_query);
if ($_SESSION['usuario_tipo'] === 'funcionario') {
    $projetos_stmt->bind_param("i", $_SESSION['usuario_id']);
}
$projetos_stmt->execute();
$projetos = $projetos_stmt->get_result();

// Buscar funcionários para atribuição (apenas admin)
$funcionarios = null;
if ($_SESSION['usuario_tipo'] === 'admin') {
    $funcionarios_query = "SELECT id, nome, email FROM usuarios WHERE tipo = 'funcionario' ORDER BY nome";
    $funcionarios = $conn->query($funcionarios_query);
}

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $projeto_id = $_POST['projeto_id'];
    $prioridade = $_POST['prioridade'];
    $prazo = !empty($_POST['prazo']) ? $_POST['prazo'] : null;
    
    // Definir funcionário responsável
    if ($_SESSION['usuario_tipo'] === 'admin' && isset($_POST['funcionario_id'])) {
        $funcionario_id = $_POST['funcionario_id'];
    } else {
        $funcionario_id = $_SESSION['usuario_id']; // Funcionário cria tarefa para si mesmo
    }

    if (!empty($titulo) && !empty($projeto_id)) {
        $sql = "INSERT INTO tarefas (titulo, descricao, projeto_id, funcionario_id, prioridade, prazo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiss", $titulo, $descricao, $projeto_id, $funcionario_id, $prioridade, $prazo);

        if ($stmt->execute()) {
            $mensagem = "Tarefa criada com sucesso!";
            $tipo_mensagem = 'success';
        } else {
            $mensagem = "Erro ao criar tarefa: " . $stmt->error;
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
    <title>Criar Nova Tarefa - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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
            border-color: #28a745;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
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

        select.form-control {
            appearance: none;
            background: #f8f9fa url('data:image/svg+xml;charset=US-ASCII,<svg viewBox="0 0 4 5" xmlns="http://www.w3.org/2000/svg"><path fill="%2328a745" d="m0 0 2 2 2-2z"/></svg>') no-repeat right 15px center;
            background-size: 12px;
            cursor: pointer;
        }

        .priority-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .priority-option {
            position: relative;
        }

        .priority-option input[type="radio"] {
            display: none;
        }

        .priority-option label {
            display: block;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: normal;
        }

        .priority-option.baixa label {
            background: #f8f9fa;
            color: #6c757d;
        }

        .priority-option.media label {
            background: #fff3cd;
            color: #856404;
        }

        .priority-option.alta label {
            background: #f8d7da;
            color: #721c24;
        }

        .priority-option input[type="radio"]:checked + label {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .priority-option.baixa input[type="radio"]:checked + label {
            border-color: #6c757d;
            background: #6c757d;
            color: white;
        }

        .priority-option.media input[type="radio"]:checked + label {
            border-color: #ffc107;
            background: #ffc107;
            color: #212529;
        }

        .priority-option.alta input[type="radio"]:checked + label {
            border-color: #dc3545;
            background: #dc3545;
            color: white;
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
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

        .char-counter {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }

        .project-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
        }

        .project-info strong {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .form-container {
                padding: 25px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .priority-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-tasks"></i> Criar Nova Tarefa</h1>
            <p>Defina uma nova tarefa para o projeto</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>">
                <i class="fas fa-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" id="tarefaForm">
                <div class="form-group">
                    <label for="titulo">
                        <i class="fas fa-tag"></i> Título da Tarefa
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="titulo" 
                           name="titulo" 
                           class="form-control" 
                           placeholder="Digite um título claro e objetivo para a tarefa"
                           maxlength="100"
                           required>
                    <div class="char-counter">
                        <span id="titulo-count">0</span>/100 caracteres
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">
                        <i class="fas fa-align-left"></i> Descrição da Tarefa
                    </label>
                    <textarea id="descricao" 
                              name="descricao" 
                              class="form-control" 
                              placeholder="Detalhe o que deve ser feito, critérios de aceitação e observações importantes..."
                              maxlength="500"></textarea>
                    <div class="char-counter">
                        <span id="descricao-count">0</span>/500 caracteres
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="projeto_id">
                            <i class="fas fa-project-diagram"></i> Projeto
                            <span class="required">*</span>
                        </label>
                        <div class="select-wrapper">
                            <select id="projeto_id" name="projeto_id" class="form-control" required>
                                <option value="">Selecione um projeto</option>
                                <?php while ($projeto = $projetos->fetch_assoc()): ?>
                                    <option value="<?= $projeto['id'] ?>">
                                        <?= htmlspecialchars($projeto['titulo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div id="projeto-info" class="project-info" style="display: none;">
                            <strong>Cliente:</strong> <span id="cliente-nome"></span>
                        </div>
                    </div>

                    <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                    <div class="form-group">
                        <label for="funcionario_id">
                            <i class="fas fa-user"></i> Responsável
                            <span class="required">*</span>
                        </label>
                        <div class="select-wrapper">
                            <select id="funcionario_id" name="funcionario_id" class="form-control" required>
                                <option value="">Selecione um funcionário</option>
                                <?php while ($funcionario = $funcionarios->fetch_assoc()): ?>
                                    <option value="<?= $funcionario['id'] ?>">
                                        <?= htmlspecialchars($funcionario['nome']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-exclamation-circle"></i> Prioridade
                            <span class="required">*</span>
                        </label>
                        <div class="priority-options">
                            <div class="priority-option baixa">
                                <input type="radio" id="prioridade_baixa" name="prioridade" value="baixa" required>
                                <label for="prioridade_baixa">
                                    <i class="fas fa-arrow-down"></i> Baixa
                                </label>
                            </div>
                            <div class="priority-option media">
                                <input type="radio" id="prioridade_media" name="prioridade" value="media" checked>
                                <label for="prioridade_media">
                                    <i class="fas fa-minus"></i> Média
                                </label>
                            </div>
                            <div class="priority-option alta">
                                <input type="radio" id="prioridade_alta" name="prioridade" value="alta">
                                <label for="prioridade_alta">
                                    <i class="fas fa-arrow-up"></i> Alta
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="prazo">
                            <i class="fas fa-calendar-alt"></i> Prazo (Opcional)
                        </label>
                        <input type="datetime-local" 
                               id="prazo" 
                               name="prazo" 
                               class="form-control"
                               min="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                </div>

                <div class="btn-group">
                    <a href="../dashboard_gestor.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Criar Tarefa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dados dos projetos para mostrar informações do cliente
        const projetosData = {
            <?php 
            $projetos->data_seek(0); // Reset pointer
            while ($p = $projetos->fetch_assoc()): 
            ?>
            <?= $p['id'] ?>: {
                cliente: "<?= htmlspecialchars($p['cliente_nome']) ?>"
            },
            <?php endwhile; ?>
        };

        // Elementos do formulário
        const form = document.getElementById('tarefaForm');
        const titulo = document.getElementById('titulo');
        const descricao = document.getElementById('descricao');
        const projeto = document.getElementById('projeto_id');
        const projetoInfo = document.getElementById('projeto-info');
        const clienteNome = document.getElementById('cliente-nome');

        // Contadores de caracteres
        const tituloCounter = document.getElementById('titulo-count');
        const descricaoCounter = document.getElementById('descricao-count');

        // Event listeners
        titulo.addEventListener('input', function() {
            tituloCounter.textContent = this.value.length;
            validateField(this);
        });

        descricao.addEventListener('input', function() {
            descricaoCounter.textContent = this.value.length;
            // Auto-resize
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        projeto.addEventListener('change', function() {
            validateField(this);
            
            if (this.value && projetosData[this.value]) {
                clienteNome.textContent = projetosData[this.value].cliente;
                projetoInfo.style.display = 'block';
            } else {
                projetoInfo.style.display = 'none';
            }
        });

        // Validação de campos
        function validateField(field) {
            if (field.value.trim() !== '') {
                field.classList.add('is-valid');
                field.classList.remove('is-invalid');
            } else if (field.hasAttribute('required')) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
            }
        }

        // Validação do formulário
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            if (titulo.value.trim() === '') {
                titulo.classList.add('is-invalid');
                isValid = false;
            }
            
            if (projeto.value === '') {
                projeto.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
            }
        });

        // Definir data mínima como hoje
        const prazoInput = document.getElementById('prazo');
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        prazoInput.min = now.toISOString().slice(0, 16);
    </script>
</body>
</html>