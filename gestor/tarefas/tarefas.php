<?php
// gestor/tarefas/index.php - Página principal de gerenciamento de tarefas
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

require '../../conexao.php';

// Processar ações
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'criar':
            $titulo = trim($_POST['titulo']);
            $descricao = trim($_POST['descricao']);
            $projeto_id = (int)$_POST['projeto_id'];
            $funcionario_id = (int)$_POST['funcionario_id'];
            
            if (!empty($titulo) && $projeto_id > 0 && $funcionario_id > 0) {
                $stmt = $conn->prepare("INSERT INTO tarefas (titulo, descricao, projeto_id, funcionario_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $titulo, $descricao, $projeto_id, $funcionario_id);
                
                if ($stmt->execute()) {
                    $mensagem = "Tarefa criada com sucesso!";
                } else {
                    $mensagem = "Erro ao criar tarefa: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
            }
            break;
            
        case 'atualizar_status':
            $id = (int)$_POST['tarefa_id'];
            $novo_status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE tarefas SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $novo_status, $id);
            
            if ($stmt->execute()) {
                $mensagem = "Status da tarefa atualizado com sucesso!";
            } else {
                $mensagem = "Erro ao atualizar status: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'editar':
            $id = (int)$_POST['tarefa_id'];
            $titulo = trim($_POST['titulo']);
            $descricao = trim($_POST['descricao']);
            $projeto_id = (int)$_POST['projeto_id'];
            $funcionario_id = (int)$_POST['funcionario_id'];
            $status = $_POST['status'];
            
            if (!empty($titulo) && $projeto_id > 0 && $funcionario_id > 0) {
                $stmt = $conn->prepare("UPDATE tarefas SET titulo = ?, descricao = ?, projeto_id = ?, funcionario_id = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssiisi", $titulo, $descricao, $projeto_id, $funcionario_id, $status, $id);
                
                if ($stmt->execute()) {
                    $mensagem = "Tarefa atualizada com sucesso!";
                } else {
                    $mensagem = "Erro ao atualizar tarefa: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
            }
            break;
            
        case 'excluir':
            $id = (int)$_POST['tarefa_id'];
            
            $stmt = $conn->prepare("DELETE FROM tarefas WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $mensagem = "Tarefa excluída com sucesso!";
            } else {
                $mensagem = "Erro ao excluir tarefa: " . $stmt->error;
            }
            $stmt->close();
            break;
    }
}

// Carregar dados para os formulários
$projetos = $conn->query("SELECT id, titulo FROM projetos ORDER BY titulo");
$funcionarios = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'funcionario' ORDER BY nome");

// Filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_projeto = $_GET['projeto'] ?? '';
$filtro_funcionario = $_GET['funcionario'] ?? '';
$ordenar_por = $_GET['ordenar'] ?? 'criado_em';
$ordem = $_GET['ordem'] ?? 'DESC';

// Construir query com filtros
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($filtro_status)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $filtro_status;
    $param_types .= 's';
}

if (!empty($filtro_projeto)) {
    $where_conditions[] = "t.projeto_id = ?";
    $params[] = (int)$filtro_projeto;
    $param_types .= 'i';
}

if (!empty($filtro_funcionario)) {
    $where_conditions[] = "t.funcionario_id = ?";
    $params[] = (int)$filtro_funcionario;
    $param_types .= 'i';
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$ordem_valida = in_array($ordem, ['ASC', 'DESC']) ? $ordem : 'DESC';
$colunas_validas = ['titulo', 'status', 'criado_em', 'nome_projeto', 'nome_funcionario'];
$ordenar_valido = in_array($ordenar_por, $colunas_validas) ? $ordenar_por : 'criado_em';

$sql = "
    SELECT 
        t.id, t.titulo, t.descricao, t.status, t.criado_em,
        p.titulo AS nome_projeto,
        u.nome AS nome_funcionario
    FROM tarefas t
    LEFT JOIN projetos p ON t.projeto_id = p.id
    LEFT JOIN usuarios u ON t.funcionario_id = u.id
    $where_sql
    ORDER BY $ordenar_valido $ordem_valida
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Obter estatísticas
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'a_fazer' THEN 1 ELSE 0 END) as a_fazer,
        SUM(CASE WHEN status = 'em_progresso' THEN 1 ELSE 0 END) as em_progresso,
        SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluido
    FROM tarefas
";
$stats = $conn->query($stats_sql)->fetch_assoc();

// Buscar tarefa para edição (se solicitado)
$tarefa_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM tarefas WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $tarefa_editar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Tarefas - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
    <?php include '../sidebar.php'; ?>
    <style>
        /* CSS para Gerenciamento de Tarefas - AugeBit */

:root {
    --primary-color: #9999FF;
    --primary-light: #B3B3FF;
    --primary-dark: #7A7AFF;
    --primary-darker: #5C5CFF;
    --secondary-light: #E6E6FF;
    --background-light: #F8F8FF;
    --white: #FFFFFF;
    --text-dark: #333333;
    --text-light: #666666;
    --border-color: #DDDDDD;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --shadow: 0 2px 8px rgba(153, 153, 255, 0.15);
    --shadow-hover: 0 4px 16px rgba(153, 153, 255, 0.25);
    --border-radius: 8px;
    --transition: all 0.3s ease;
}

* {
    box-sizing: border-box;
}

.conteudo-principal {
    background-color: var(--background-light);
    color: var(--text-dark);
    margin: 0;
    padding: 0;
    line-height: 1.6;
}


h1 {
    color: var(--primary-darker);
    font-size: 2.5em;
    margin-bottom: 30px;
    text-align: center;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(153, 153, 255, 0.1);
}

.conteudo-principal h3 {
    color: white;
    margin-bottom: 20px;
    font-size: 1.4em;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

h3::before {
    content: '';
    width: 4px;
    height: 24px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 2px;
}

/* Mensagens de feedback */
.message {
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: var(--border-radius);
    border-left: 4px solid var(--primary-color);
    background: linear-gradient(135deg, var(--white), var(--secondary-light));
    box-shadow: var(--shadow);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Cards/Seções */
.card {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin: 25px 0;
    overflow: hidden;
    transition: var(--transition);
    border: 1px solid var(--border-color);
}

.card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    padding: 20px;
    font-weight: 500;
    font-size: 1.1em;
}

.card-body {
    padding: 25px;
}

/* Estatísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-item {
    background: linear-gradient(135deg, var(--white), var(--secondary-light));
    padding: 20px;
    border-radius: var(--border-radius);
    text-align: center;
    box-shadow: var(--shadow);
    transition: var(--transition);
    border: 1px solid var(--border-color);
}

.stat-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: var(--primary-darker);
    display: block;
    margin-bottom: 5px;
}

.stat-label {
    color: var(--text-light);
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Formulários */
.form-container {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin: 25px 0;
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
    color: var(--white);
    padding: 20px;
    font-weight: 500;
}

.form-body {
    padding: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    align-items: end;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-dark);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 14px;
    transition: var(--transition);
    background: var(--white);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(153, 153, 255, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

/* Botões */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-block;
    text-align: center;
    margin: 5px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary-darker));
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.btn-secondary {
    background: var(--white);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-secondary:hover {
    background: var(--primary-color);
    color: var(--white);
}

.btn-danger {
    background: var(--danger-color);
    color: var(--white);
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.btn-small {
    padding: 8px 16px;
    font-size: 12px;
}

/* Filtros */
.filters-container {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin: 25px 0;
    overflow: hidden;
}

.filters-header {
    background: linear-gradient(135deg, var(--secondary-light), var(--primary-light));
    color: var(--primary-darker);
    padding: 15px 20px;
    font-weight: 500;
}

.filters-body {
    padding: 20px;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Tabela */
.table-container {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin: 25px 0;
}

.table-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    padding: 20px;
    font-weight: 500;
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead {
    background: linear-gradient(135deg, var(--secondary-light), var(--primary-light));
}

th {
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    color: var(--primary-darker);
    border-bottom: 2px solid var(--primary-color);
    white-space: nowrap;
}

td {
    padding: 15px 12px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: top;
}

tbody tr {
    transition: var(--transition);
}

tbody tr:hover {
    background: var(--background-light);
}

tbody tr:nth-child(even) {
    background: rgba(153, 153, 255, 0.02);
}

tbody tr:nth-child(even):hover {
    background: var(--background-light);
}

/* Status badges */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-a-fazer {
    background: #ffeaa7;
    color: #d63031;
}

.status-em-progresso {
    background: #81ecec;
    color: #00b894;
}

.status-concluido {
    background: #55efc4;
    color: #00b894;
}

/* Select personalizado para status */
.status-select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    background: var(--white);
    font-size: 12px;
    cursor: pointer;
    transition: var(--transition);
}

.status-select:hover {
    border-color: var(--primary-color);
}

/* Ações da tabela */
.table-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.action-link {
    padding: 6px 12px;
    text-decoration: none;
    border-radius: var(--border-radius);
    font-size: 12px;
    font-weight: 500;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.action-edit {
    background: var(--primary-light);
    color: var(--primary-darker);
}

.action-edit:hover {
    background: var(--primary-color);
    color: var(--white);
}

.action-view {
    background: var(--secondary-light);
    color: var(--primary-darker);
}

.action-view:hover {
    background: var(--primary-light);
    color: var(--primary-darker);
}

.action-delete {
    background: #ffebee;
    color: var(--danger-color);
    border: none;
    cursor: pointer;
}

.action-delete:hover {
    background: var(--danger-color);
    color: var(--white);
}

/* Responsividade */
@media (max-width: 768px) {
    .main-content {
        padding: 20px 15px;
    }
    
    h1 {
        font-size: 2em;
    }
    
    .form-grid,
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .table-responsive {
        font-size: 12px;
    }
    
    th, td {
        padding: 10px 8px;
    }
    
    .table-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-link {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        margin: 5px 0;
    }
}

/* Animações adicionais */
.fade-in {
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Melhorias para acessibilidade */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Estados de foco melhorados */
button:focus,
select:focus,
input:focus,
textarea:focus,
a:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Estilo para elementos desabilitados */
button:disabled,
input:disabled,
select:disabled,
textarea:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Tooltip básico */
[data-tooltip] {
    position: relative;
}

[data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--text-dark);
    color: var(--white);
    padding: 8px 12px;
    border-radius: var(--border-radius);
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    opacity: 0;
    animation: tooltipFadeIn 0.3s ease forwards;
}

@keyframes tooltipFadeIn {
    to {
        opacity: 1;
    }
}
    </style>
</head>
<body>
<body>
    <div class="main-content">
        <div class="conteudo-principal">
            <h1 class="fade-in">Gerenciamento de Tarefas</h1>
            
            <?php if ($mensagem): ?>
                <div class="message">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <div class="card fade-in">
                <div class="card-body">
                    <h3 style="color: #7A7AFF;">Estatísticas das Tarefas</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?= $stats['total'] ?></span>
                            <span class="stat-label">Total de Tarefas</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $stats['a_fazer'] ?></span>
                            <span class="stat-label">A Fazer</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $stats['em_progresso'] ?></span>
                            <span class="stat-label">Em Progresso</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $stats['concluido'] ?></span>
                            <span class="stat-label">Concluídas</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card fade-in" id="form-tarefa">
                <div class="card-header">
                    <h3><?= $tarefa_editar ? 'Editar Tarefa' : 'Criar Nova Tarefa' ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="tarefas.php#form-tarefa">
                        <input type="hidden" name="acao" value="<?= $tarefa_editar ? 'editar' : 'criar' ?>">
                        <?php if ($tarefa_editar): ?>
                            <input type="hidden" name="tarefa_id" value="<?= $tarefa_editar['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="titulo">Título da Tarefa</label>
                                <input type="text" name="titulo" id="titulo" required 
                                       value="<?= htmlspecialchars($tarefa_editar['titulo'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="projeto_id">Projeto</label>
                                <select name="projeto_id" id="projeto_id" required>
                                    <option value="">-- Selecione um projeto --</option>
                                    <?php 
                                    $projetos->data_seek(0);
                                    while ($p = $projetos->fetch_assoc()): 
                                        $selected = ($tarefa_editar && $tarefa_editar['projeto_id'] == $p['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $p['id'] ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($p['titulo']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="funcionario_id">Atribuir a</label>
                                <select name="funcionario_id" id="funcionario_id" required>
                                    <option value="">-- Selecione um funcionário --</option>
                                    <?php 
                                    $funcionarios->data_seek(0);
                                    while ($f = $funcionarios->fetch_assoc()): 
                                        $selected = ($tarefa_editar && $tarefa_editar['funcionario_id'] == $f['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $f['id'] ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($f['nome']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="descricao">Descrição (Opcional)</label>
                                <textarea name="descricao" id="descricao" rows="4"><?= htmlspecialchars($tarefa_editar['descricao'] ?? '') ?></textarea>
                            </div>

                            <?php if ($tarefa_editar): ?>
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select name="status" id="status" required>
                                        <option value="a_fazer" <?= $tarefa_editar['status'] == 'a_fazer' ? 'selected' : '' ?>>A Fazer</option>
                                        <option value="em_progresso" <?= $tarefa_editar['status'] == 'em_progresso' ? 'selected' : '' ?>>Em Progresso</option>
                                        <option value="concluido" <?= $tarefa_editar['status'] == 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                            <?php if ($tarefa_editar): ?>
                                <a href="tarefas.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <?= $tarefa_editar ? 'Salvar Alterações' : 'Criar Tarefa' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card fade-in">
                <div class="card-header">
                    <h3>Filtrar e Ordenar Tarefas</h3>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="filtro_status">Status</label>
                                <select name="status" id="filtro_status">
                                    <option value="">Todos</option>
                                    <option value="a_fazer" <?= $filtro_status == 'a_fazer' ? 'selected' : '' ?>>A Fazer</option>
                                    <option value="em_progresso" <?= $filtro_status == 'em_progresso' ? 'selected' : '' ?>>Em Progresso</option>
                                    <option value="concluido" <?= $filtro_status == 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filtro_projeto">Projeto</label>
                                <select name="projeto" id="filtro_projeto">
                                    <option value="">Todos</option>
                                    <?php 
                                    $projetos->data_seek(0);
                                    while ($p = $projetos->fetch_assoc()): ?>
                                        <option value="<?= $p['id'] ?>" <?= $filtro_projeto == $p['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['titulo']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filtro_funcionario">Funcionário</label>
                                <select name="funcionario" id="filtro_funcionario">
                                    <option value="">Todos</option>
                                    <?php 
                                    $funcionarios->data_seek(0);
                                    while ($f = $funcionarios->fetch_assoc()): ?>
                                        <option value="<?= $f['id'] ?>" <?= $filtro_funcionario == $f['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($f['nome']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ordenar">Ordenar por</label>
                                <div style="display: flex; gap: 5px;">
                                    <select name="ordenar" id="ordenar" style="flex: 3;">
                                        <option value="criado_em" <?= $ordenar_por == 'criado_em' ? 'selected' : '' ?>>Data</option>
                                        <option value="titulo" <?= $ordenar_por == 'titulo' ? 'selected' : '' ?>>Título</option>
                                        <option value="status" <?= $ordenar_por == 'status' ? 'selected' : '' ?>>Status</option>
                                    </select>
                                    <select name="ordem" style="flex: 2;">
                                        <option value="DESC" <?= $ordem == 'DESC' ? 'selected' : '' ?>>Decrescente</option>
                                        <option value="ASC" <?= $ordem == 'ASC' ? 'selected' : '' ?>>Crescente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                            <a href="tarefas.php" class="btn btn-secondary">Limpar Filtros</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-container fade-in">
                <div class="table-header">
                    <h3>Lista de Tarefas</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Projeto</th>
                                <th>Funcionário</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($t = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($t['titulo']) ?></strong>
                                            <?php if ($t['descricao']): ?>
                                                <p style="font-size: 0.9em; color: #666; margin: 5px 0 0 0;"><?= htmlspecialchars(substr($t['descricao'], 0, 70)) ?><?= strlen($t['descricao']) > 70 ? '...' : '' ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($t['nome_projeto'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($t['nome_funcionario'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                            $status_class = 'status-' . str_replace('_', '-', $t['status']);
                                            $status_text = ucwords(str_replace('_', ' ', $t['status']));
                                            ?>
                                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($t['criado_em'])) ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="tarefas.php?editar=<?= $t['id'] ?>#form-tarefa" class="action-link action-edit" data-tooltip="Editar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/></svg>
                                                    <span class="sr-only">Editar</span>
                                                </a>
                                                
                                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa?')" style="display: inline;">
                                                    <input type="hidden" name="acao" value="excluir">
                                                    <input type="hidden" name="tarefa_id" value="<?= $t['id'] ?>">
                                                    <button type="submit" class="action-link action-delete" data-tooltip="Excluir">
                                                         <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                        <span class="sr-only">Excluir</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">Nenhuma tarefa encontrada com os filtros aplicados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Confirmação de exclusão
        function confirmarExclusao() {
            return confirm('Tem certeza que deseja excluir esta tarefa? Esta ação não pode ser desfeita.');
        }

        // Auto-submit para filtros de status
        document.addEventListener('DOMContentLoaded', function() {
            const selectsStatus = document.querySelectorAll('select[name="status"][onchange]');
            selectsStatus.forEach(select => {
                select.addEventListener('change', function() {
                    if (confirm('Deseja alterar o status desta tarefa?')) {
                        this.form.submit();
                    } else {
                        // Reverter seleção se cancelado
                        this.selectedIndex = this.getAttribute('data-original-index') || 0;
                    }
                });
                
                // Salvar índice original
                select.setAttribute('data-original-index', select.selectedIndex);
            });
        });
    </script>
</body>
</html>

<?php
// Fechar conexão
$conn->close();
?>

