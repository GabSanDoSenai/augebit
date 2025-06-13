listar tarefas <?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require '../../conexao.php';

// Atualizar status se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tarefa_id'], $_POST['status'])) {
    $id = (int)$_POST['tarefa_id'];
    $novo_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE tarefas SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $id);
    $stmt->execute();
    echo "<div class='message success'>Status da tarefa #$id atualizado para <strong>".ucfirst(str_replace('_', ' ', $novo_status))."</strong>.</div>";
}

// Listar tarefas
$sql = "
    SELECT 
        t.id, t.titulo, t.status, t.criado_em,
        p.titulo AS nome_projeto,
        u.nome AS nome_funcionario
    FROM tarefas t
    LEFT JOIN projetos p ON t.projeto_id = p.id
    LEFT JOIN usuarios u ON t.funcionario_id = u.id
    ORDER BY t.criado_em DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Tarefas - AugeBit</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        /* Fontes Poppins */
        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-Regular.ttf') format('truetype');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-SemiBold.ttf') format('truetype');
            font-weight: 600;
        }
        @font-face {
            font-family: 'Poppins';
            src: url('../../assets/fonte/Poppins-Medium.ttf') format('truetype');
            font-weight: 500;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f5ff;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar já está estilizado no arquivo incluído */
        
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 300px; /* Ajuste conforme a largura do seu sidebar */
            transition: margin-left 0.3s;
        }
        
        h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #4b2a80;
            margin-top: 0;
            border-bottom: 2px solid #e6e0ff;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(102, 51, 153, 0.1);
        }
        
        th {
            background-color: #6a3cb5;
            color: white;
            font-weight: 500;
            padding: 12px 15px;
            text-align: left;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e6e0ff;
        }
        
        tr:hover {
            background-color: #f5f0ff;
        }
        
        select {
            padding: 8px 12px;
            border: 1px solid #b399ff;
            border-radius: 6px;
            background-color: #f5f0ff;
            font-family: 'Poppins', sans-serif;
            color: #4b2a80;
        }
        
        select:focus {
            outline: none;
            border-color: #6a3cb5;
            box-shadow: 0 0 0 2px rgba(106, 60, 181, 0.2);
        }
        
        a {
            color: #6a3cb5;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        a:hover {
            color: #8a63d2;
            text-decoration: underline;
        }
        
        .message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .message.success {
            background-color: #e6fffa;
            color: #006644;
            border-left: 4px solid #00b386;
        }
        
        /* Status colors */
        [data-status="a_fazer"] { color: #6a3cb5; background-color: #e6e0ff; }
        [data-status="em_progresso"] { color: #4b2a80; background-color: #d1c4e9; }
        [data-status="concluido"] { color: white; background-color: #6a3cb5; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <h2>Lista de Tarefas</h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Projeto</th>
                    <th>Funcionário</th>
                    <th>Status</th>
                    <th>Alterar Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($t = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><?= htmlspecialchars($t['titulo']) ?></td>
                        <td><?= htmlspecialchars($t['nome_projeto'] ?? 'Sem projeto') ?></td>
                        <td><?= htmlspecialchars($t['nome_funcionario'] ?? 'Não atribuído') ?></td>
                        <td>
                            <span class="status-badge" data-status="<?= $t['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="tarefa_id" value="<?= $t['id'] ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="a_fazer" <?= $t['status'] === 'a_fazer' ? 'selected' : '' ?>>A Fazer</option>
                                    <option value="em_progresso" <?= $t['status'] === 'em_progresso' ? 'selected' : '' ?>>Em Progresso</option>
                                    <option value="concluido" <?= $t['status'] === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                </select>
                            </form>
                        </td>
                        <td><a href="editar_tarefa.php?id=<?= $t['id'] ?>">✏️ Editar</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>