<?php
session_start();
require_once '../../conexao.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

// Processar exclusão de funcionário
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $funcionario_id = (int)$_GET['excluir'];
    
    // Verificar se o funcionário existe
    $stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ? AND tipo = 'funcionario'");
    $stmt->bind_param("i", $funcionario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $funcionario = $resultado->fetch_assoc();
        
        // Verificar se o funcionário tem projetos ou tarefas associadas
        $stmt_check = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM projetos_usuarios WHERE funcionario_id = ?) as projetos,
                (SELECT COUNT(*) FROM tarefas WHERE funcionario_id = ?) as tarefas
        ");
        $stmt_check->bind_param("ii", $funcionario_id, $funcionario_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        $check_data = $check_result->fetch_assoc();
        
        if ($check_data['projetos'] > 0 || $check_data['tarefas'] > 0) {
            $mensagem = "Não é possível excluir o funcionário " . htmlspecialchars($funcionario['nome']) . " pois ele possui projetos ou tarefas associadas.";
            $tipo_mensagem = 'erro';
        } else {
            // Excluir funcionário
            $stmt_delete = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'funcionario'");
            $stmt_delete->bind_param("i", $funcionario_id);
            
            if ($stmt_delete->execute()) {
                $mensagem = "Funcionário " . htmlspecialchars($funcionario['nome']) . " excluído com sucesso!";
                $tipo_mensagem = 'sucesso';
            } else {
                $mensagem = "Erro ao excluir funcionário: " . $conn->error;
                $tipo_mensagem = 'erro';
            }
            $stmt_delete->close();
        }
        $stmt_check->close();
    } else {
        $mensagem = "Funcionário não encontrado.";
        $tipo_mensagem = 'erro';
    }
    $stmt->close();
}

// Buscar todos os funcionários com informações adicionais
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        u.nome, 
        u.email, 
        u.criado_em,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id) AS total_tarefas,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id AND t.status = 'em_progresso') AS em_progresso,
        (SELECT COUNT(*) FROM tarefas t WHERE t.funcionario_id = u.id AND t.status = 'concluido') AS concluidas,
        (SELECT COUNT(*) FROM projetos_usuarios pu WHERE pu.funcionario_id = u.id) AS total_projetos
    FROM usuarios u
    WHERE u.tipo = 'funcionario'
    ORDER BY u.nome
");
$stmt->execute();
$funcionarios = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Funcionários - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        .mensagem {
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tabela-funcionarios {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tabela-funcionarios th,
        .tabela-funcionarios td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .tabela-funcionarios th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .tabela-funcionarios tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }
        
        .btn-editar {
            background-color: #007bff;
            color: white;
        }
        
        .btn-excluir {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-ver {
            background-color: #28a745;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .acoes {
            white-space: nowrap;
        }
        
        .stats {
            font-size: 11px;
            color: #666;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-novo {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .sem-funcionarios {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header-actions">
            <h2>👥 Gerenciar Funcionários</h2>
            <a href="adicionar_funcionario.php" class="btn-novo">+ Novo Funcionário</a>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <?php if ($funcionarios->num_rows > 0): ?>
            <table class="tabela-funcionarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Data de Cadastro</th>
                        <th>Estatísticas</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($funcionario = $funcionarios->fetch_assoc()): ?>
                        <tr>
                            <td><?= $funcionario['id'] ?></td>
                            <td><?= htmlspecialchars($funcionario['nome']) ?></td>
                            <td><?= htmlspecialchars($funcionario['email']) ?></td>
                            <td><?= date('d/m/Y', strtotime($funcionario['criado_em'])) ?></td>
                            <td>
                                <div class="stats">
                                    📊 <?= $funcionario['total_projetos'] ?> projeto(s)<br>
                                    📋 <?= $funcionario['total_tarefas'] ?> tarefa(s)<br>
                                    🔄 <?= $funcionario['em_progresso'] ?> em progresso<br>
                                    ✅ <?= $funcionario['concluidas'] ?> concluída(s)
                                </div>
                            </td>
                            <td class="acoes">
                                <a href="editar_funcionario.php?id=<?= $funcionario['id'] ?>" 
                                   class="btn btn-editar" 
                                   title="Editar funcionário">
                                    ✏️ Editar
                                </a>
                                
                                <a href="../tarefas/listar_tarefas.php?funcionario_id=<?= $funcionario['id'] ?>" 
                                   class="btn btn-ver" 
                                   title="Ver tarefas do funcionário">
                                    🔍 Tarefas
                                </a>
                                
                                <a href="?excluir=<?= $funcionario['id'] ?>" 
                                   class="btn btn-excluir" 
                                   title="Excluir funcionário"
                                   onclick="return confirm('Tem certeza que deseja excluir o funcionário <?= htmlspecialchars($funcionario['nome'], ENT_QUOTES) ?>?\n\nATENÇÃO: Esta ação não pode ser desfeita!')">
                                    🗑️ Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="sem-funcionarios">
                <h3>Nenhum funcionário cadastrado</h3>
                <p>Clique em "Novo Funcionário" para adicionar o primeiro funcionário ao sistema.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Remover mensagem após 5 segundos
        setTimeout(function() {
            const mensagem = document.querySelector('.mensagem');
            if (mensagem) {
                mensagem.style.opacity = '0';
                setTimeout(() => mensagem.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>