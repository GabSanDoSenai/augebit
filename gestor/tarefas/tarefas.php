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
<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Tarefas - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
</head>
<body>
    
    <div class="main-content">
        <h1>Gerenciamento de Tarefas</h1>
        
        <?php if ($mensagem): ?>
            <div style="padding: 10px; margin: 10px 0; border: 1px solid #ccc; background: #f9f9f9;">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd;">
            <h3>Estatísticas das Tarefas</h3>
            <p><strong>Total:</strong> <?= $stats['total'] ?></p>
            <p><strong>A Fazer:</strong> <?= $stats['a_fazer'] ?></p>
            <p><strong>Em Progresso:</strong> <?= $stats['em_progresso'] ?></p>
            <p><strong>Concluídas:</strong> <?= $stats['concluido'] ?></p>
        </div>

        <!-- Formulário de Nova Tarefa / Edição -->
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd;">
            <h3><?= $tarefa_editar ? 'Editar Tarefa' : 'Nova Tarefa' ?></h3>
            <form method="POST">
                <input type="hidden" name="acao" value="<?= $tarefa_editar ? 'editar' : 'criar' ?>">
                <?php if ($tarefa_editar): ?>
                    <input type="hidden" name="tarefa_id" value="<?= $tarefa_editar['id'] ?>">
                <?php endif; ?>
                
                <table>
                    <tr>
                        <td><label for="titulo">Título:</label></td>
                        <td><input type="text" name="titulo" id="titulo" required 
                            value="<?= $tarefa_editar ? htmlspecialchars($tarefa_editar['titulo']) : '' ?>" 
                            size="50"></td>
                    </tr>
                    <tr>
                        <td><label for="descricao">Descrição:</label></td>
                        <td><textarea name="descricao" id="descricao" rows="3" cols="50"><?= $tarefa_editar ? htmlspecialchars($tarefa_editar['descricao']) : '' ?></textarea></td>
                    </tr>
                    <tr>
                        <td><label for="projeto_id">Projeto:</label></td>
                        <td>
                            <select name="projeto_id" id="projeto_id" required>
                                <option value="">-- Selecione um projeto --</option>
                                <?php 
                                $projetos->data_seek(0);
                                while ($p = $projetos->fetch_assoc()): 
                                ?>
                                    <option value="<?= $p['id'] ?>" 
                                        <?= ($tarefa_editar && $tarefa_editar['projeto_id'] == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['titulo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="funcionario_id">Funcionário:</label></td>
                        <td>
                            <select name="funcionario_id" id="funcionario_id" required>
                                <option value="">-- Selecione um funcionário --</option>
                                <?php 
                                $funcionarios->data_seek(0);
                                while ($f = $funcionarios->fetch_assoc()): 
                                ?>
                                    <option value="<?= $f['id'] ?>" 
                                        <?= ($tarefa_editar && $tarefa_editar['funcionario_id'] == $f['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['nome']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                    </tr>
                    <?php if ($tarefa_editar): ?>
                    <tr>
                        <td><label for="status">Status:</label></td>
                        <td>
                            <select name="status" id="status" required>
                                <option value="a_fazer" <?= $tarefa_editar['status'] == 'a_fazer' ? 'selected' : '' ?>>A Fazer</option>
                                <option value="em_progresso" <?= $tarefa_editar['status'] == 'em_progresso' ? 'selected' : '' ?>>Em Progresso</option>
                                <option value="concluido" <?= $tarefa_editar['status'] == 'concluido' ? 'selected' : '' ?>>Concluído</option>
                            </select>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="2">
                            <button type="submit"><?= $tarefa_editar ? 'Atualizar Tarefa' : 'Criar Tarefa' ?></button>
                            <?php if ($tarefa_editar): ?>
                                <a href="tarefas.php">Cancelar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- Filtros -->
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd;">
            <h3>Filtros</h3>
            <form method="GET">
                <table>
                    <tr>
                        <td><label for="status">Status:</label></td>
                        <td>
                            <select name="status" id="status">
                                <option value="">Todos</option>
                                <option value="a_fazer" <?= $filtro_status == 'a_fazer' ? 'selected' : '' ?>>A Fazer</option>
                                <option value="em_progresso" <?= $filtro_status == 'em_progresso' ? 'selected' : '' ?>>Em Progresso</option>
                                <option value="concluido" <?= $filtro_status == 'concluido' ? 'selected' : '' ?>>Concluído</option>
                            </select>
                        </td>
                        <td><label for="projeto">Projeto:</label></td>
                        <td>
                            <select name="projeto" id="projeto">
                                <option value="">Todos</option>
                                <?php 
                                $projetos->data_seek(0);
                                while ($p = $projetos->fetch_assoc()): 
                                ?>
                                    <option value="<?= $p['id'] ?>" <?= $filtro_projeto == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['titulo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="funcionario">Funcionário:</label></td>
                        <td>
                            <select name="funcionario" id="funcionario">
                                <option value="">Todos</option>
                                <?php 
                                $funcionarios->data_seek(0);
                                while ($f = $funcionarios->fetch_assoc()): 
                                ?>
                                    <option value="<?= $f['id'] ?>" <?= $filtro_funcionario == $f['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['nome']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td><label for="ordenar">Ordenar por:</label></td>
                        <td>
                            <select name="ordenar" id="ordenar">
                                <option value="criado_em" <?= $ordenar_por == 'criado_em' ? 'selected' : '' ?>>Data de Criação</option>
                                <option value="titulo" <?= $ordenar_por == 'titulo' ? 'selected' : '' ?>>Título</option>
                                <option value="status" <?= $ordenar_por == 'status' ? 'selected' : '' ?>>Status</option>
                                <option value="nome_projeto" <?= $ordenar_por == 'nome_projeto' ? 'selected' : '' ?>>Projeto</option>
                                <option value="nome_funcionario" <?= $ordenar_por == 'nome_funcionario' ? 'selected' : '' ?>>Funcionário</option>
                            </select>
                            <select name="ordem">
                                <option value="DESC" <?= $ordem == 'DESC' ? 'selected' : '' ?>>Decrescente</option>
                                <option value="ASC" <?= $ordem == 'ASC' ? 'selected' : '' ?>>Crescente</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <button type="submit">Aplicar Filtros</button>
                            <a href="tarefas.php">Limpar Filtros</a>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- Lista de Tarefas -->
        <div style="margin: 20px 0;">
            <h3>Lista de Tarefas</h3>
            
            <?php if ($result->num_rows > 0): ?>
                <table border="1" cellpadding="8" cellspacing="0" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Projeto</th>
                            <th>Funcionário</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $t['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($t['titulo']) ?></strong>
                                    <?php if ($t['descricao']): ?>
                                        <br><small><?= htmlspecialchars(substr($t['descricao'], 0, 50)) ?><?= strlen($t['descricao']) > 50 ? '...' : '' ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($t['nome_projeto'] ?? 'Sem projeto') ?></td>
                                <td><?= htmlspecialchars($t['nome_funcionario'] ?? 'Não atribuído') ?></td>
                                <td>
                                    <form method="POST" style="margin: 0; display: inline;">
                                        <input type="hidden" name="acao" value="atualizar_status">
                                        <input type="hidden" name="tarefa_id" value="<?= $t['id'] ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="a_fazer" <?= $t['status'] === 'a_fazer' ? 'selected' : '' ?>>A Fazer</option>
                                            <option value="em_progresso" <?= $t['status'] === 'em_progresso' ? 'selected' : '' ?>>Em Progresso</option>
                                            <option value="concluido" <?= $t['status'] === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($t['criado_em'])) ?></td>
                                <td>
                                    <a href="tarefas.php?editar=<?= $t['id'] ?>">✏️ Editar</a>
                                    |
                                    <a href="detalhes.php?id=<?= $t['id'] ?>">👁️ Ver</a>
                                    |
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="tarefa_id" value="<?= $t['id'] ?>">
                                        <button type="submit" style="background: none; border: none; color: red; cursor: pointer;">
                                            🗑️ Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma tarefa encontrada com os filtros aplicados.</p>
            <?php endif; ?>
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

