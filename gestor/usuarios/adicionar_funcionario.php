<?php
session_start();
require_once '../../conexao.php';

// Verifica se o usuário é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        $mensagem = "Todos os campos são obrigatórios.";
        $tipo_mensagem = 'erro';
    } else {
        // Verificar se e-mail já existe
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $mensagem = "Este e-mail já está cadastrado.";
            $tipo_mensagem = 'erro';
        } else {
            // Inserir novo funcionário
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo, criado_em) VALUES (?, ?, ?, 'funcionario', NOW())");
            $stmt->bind_param("sss", $nome, $email, $senhaHash);

            if ($stmt->execute()) {
                $mensagem = "Funcionário adicionado com sucesso!";
                $tipo_mensagem = 'sucesso';
            } else {
                $mensagem = "Erro ao adicionar funcionário: " . $conn->error;
                $tipo_mensagem = 'erro';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Funcionário</title>
    <link rel="stylesheet" href="../css/geral.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .form-container h2 {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-submit {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .mensagem {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
        }

        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<?php include '../sidebar.php'; ?>

<div class="main-content">
    <div class="form-container">
        <h2>➕ Adicionar Novo Funcionário</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipo_mensagem ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" name="nome" id="nome" required>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>

            <button type="submit" class="btn-submit">Salvar Funcionário</button>
        </form>
    </div>
</div>
</body>
</html>
