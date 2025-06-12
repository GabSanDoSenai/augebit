<?php
session_start();

// Verificar se o usuário está autenticado e é admin/gestor
if (!isset($_SESSION['usuario_id']) || 
    ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'gestor')) {
    header('Location: ../index.php');
    exit();
}

// Incluir arquivo de conexão (usando MySQLi como no seu sistema)
include '../conexao.php';

// Processar busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$where = "WHERE tipo = 'cliente'";

// Paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Contar total de registros
if (!empty($busca)) {
    $sql_count = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente' AND (nome LIKE ? OR email LIKE ?)";
    $stmt_count = $conn->prepare($sql_count);
    $busca_param = "%$busca%";
    $stmt_count->bind_param("ss", $busca_param, $busca_param);
} else {
    $sql_count = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente'";
    $stmt_count = $conn->prepare($sql_count);
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_registros = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar clientes
if (!empty($busca)) {
    $sql = "SELECT id, nome, email, data_cadastro FROM usuarios WHERE tipo = 'cliente' AND (nome LIKE ? OR email LIKE ?) ORDER BY nome ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $busca_param = "%$busca%";
    $stmt->bind_param("ssii", $busca_param, $busca_param, $registros_por_pagina, $offset);
} else {
    $sql = "SELECT id, nome, email, data_cadastro FROM usuarios WHERE tipo = 'cliente' ORDER BY nome ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $registros_por_pagina, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$clientes = $result->fetch_all(MYSQLI_ASSOC);

// Recuperar mensagens da sessão
$mensagem_sucesso = isset($_SESSION['mensagem_sucesso']) ? $_SESSION['mensagem_sucesso'] : '';
$mensagem_erro = isset($_SESSION['mensagem_erro']) ? $_SESSION['mensagem_erro'] : '';
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .header h1,
        .header p {
            position: relative;
            z-index: 1;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
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
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            padding: 8px 12px;
            font-size: 12px;
        }

        .btn-edit:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            padding: 8px 12px;
            font-size: 12px;
        }

        .btn-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.4);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .table tr:hover {
            background-color: #f8f9ff;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-results h3 {
            margin-bottom: 10px;
            color: #999;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .current {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
        }

        .stats {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .stats h3 {
            color: #667eea;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2em;
            }

            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                max-width: none;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
            }

            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧑‍💼 Gestão de Clientes</h1>
            <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>! Administre todos os clientes do sistema</p>
        </div>

        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($mensagem_sucesso); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem_erro): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <h3><?php echo $total_registros; ?></h3>
            <p><?php echo $total_registros == 1 ? 'Cliente cadastrado' : 'Clientes cadastrados'; ?></p>
        </div>

        <div class="actions-bar">
            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="busca" 
                    class="search-input" 
                    placeholder="🔍 Buscar por nome ou email..." 
                    value="<?php echo htmlspecialchars($busca); ?>"
                >
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if (!empty($busca)): ?>
                    <a href="gerenciar_clientes.php" class="btn btn-primary">Limpar</a>
                <?php endif; ?>
            </form>
            
            <a href="cadastrar_cliente.php" class="btn btn-success">
                ➕ Novo Cliente
            </a>
            
            <a href="../gestor/dashboard_gestor.php" class="btn btn-primary">
                🏠 Dashboard
            </a>
        </div>

        <div class="table-container">
            <?php if (empty($clientes)): ?>
                <div class="no-results">
                    <h3>Nenhum cliente encontrado</h3>
                    <p>
                        <?php if (!empty($busca)): ?>
                            Tente ajustar os termos da busca ou <a href="gerenciar_clientes.php">visualizar todos os clientes</a>.
                        <?php else: ?>
                            Comece cadastrando o primeiro cliente do sistema.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($cliente['id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cliente['data_cadastro'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-edit"
                                           title="Editar cliente">
                                            ✏️ Editar
                                        </a>
                                        <a href="excluir_cliente.php?id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-delete"
                                           title="Excluir cliente"
                                           onclick="return confirm('Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita.')">
                                            🗑️ Excluir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina_atual > 1): ?>
                    <a href="?pagina=<?php echo ($pagina_atual - 1); ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>">
                        ← Anterior
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i == $pagina_atual): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?php echo $i; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina_atual < $total_paginas): ?>
                    <a href="?pagina=<?php echo ($pagina_atual + 1); ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>">
                        Próxima →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>