<?php
session_start();
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_tipo'] = $user['tipo'];

            // Redireciona para a dashboard correta
            switch ($user['tipo']) {
                case 'funcionario':
                    header("Location: funcionario/dashboard_funcionario.php");
                    break;
                case 'cliente':
                    header("Location: cliente/dashboard_cliente.php");
                    break;
                case 'admin':
                case 'gestor':
                    header("Location: gestor/dashboard_gestor.php");
                    break;
                default:
                    echo "Tipo de usuário inválido.";
                    exit;
            }
            exit;
        } else {
            echo "Senha incorreta";
        }
    } else {
        echo "Usuário não encontrado";
    }
}
?>

<!-- Formulário de Login -->
<h2>Login</h2>
<form method="POST">
    Email: <input type="email" name="email" required><br>
    Senha: <input type="password" name="senha" required><br>
    <button type="submit">Entrar</button>
</form>
