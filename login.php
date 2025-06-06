<?php
// Start session
session_start();

include 'conexao.php';

// Initialize variables
$loginError = "";
$registerError = "";
$registerSuccess = "";

// Process login form
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['login_email']);
    $password = $_POST['login_password'];
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $loginError = "Por favor, preencha todos os campos.";
    } else {
        // Check if user exists
        $sql = "SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['senha'])) {
                // Set session variables
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['usuario_tipo'] = $user['tipo'];
                
                // Redirect based on user type
                switch ($user['tipo']) {
                    case 'admin':
                    case 'gestor':
                        header("Location: gestor/dashboard_gestor.php");
                        break;
                    case 'funcionario':
                        header("Location: funcionario/dashboard_funcionario.php");
                        break;
                    case 'cliente':
                        header("Location: cliente/dashboard_cliente.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                $loginError = "Email ou senha incorretos.";
            }
        } else {
            $loginError = "Email ou senha incorretos.";
        }
        
        $stmt->close();
    }
}

// Process registration form
if (isset($_POST['register'])) {
    $nome = $conn->real_escape_string($_POST['register_nome']);
    $email = $conn->real_escape_string($_POST['register_email']);
    $password = $_POST['register_password'];
    $tipo = 'cliente'; // Default type for new registrations
    
    // Validate inputs
    if (empty($nome) || empty($email) || empty($password)) {
        $registerError = "Por favor, preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = "Por favor, forneça um email válido.";
    } elseif (strlen($password) < 6) {
        $registerError = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $registerError = "Este email já está cadastrado.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nome, $email, $hashedPassword, $tipo);
            
            if ($stmt->execute()) {
                $registerSuccess = "Cadastro realizado com sucesso! Você já pode fazer login.";
                // Clear form
                $nome = $email = "";
            } else {
                $registerError = "Erro ao cadastrar: " . $stmt->error;
            }
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Cadastro - AugeBit</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1e1e2f, #3f3f74);
            overflow: hidden;
        }

        .main-container {
            width: 900px;
            height: 550px;
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            display: flex;
            background-color: #2a2a40;
            transition: transform 0.6s ease-in-out;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .form-container {
            width: 50%;
            height: 100%;
            padding: 50px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            transition: all 0.3s ease-in-out;
            z-index: 2;
            position: relative;
        }

        .login-container {
            transform: translateX(0%);
        }

        .register-container {
            position: absolute;
            top: 0;
            right: 0;
            transform: translateX(100%);
        }

        .main-container.show-register .login-container {
            transform: translateX(-100%);
        }

        .main-container.show-register .register-container {
            transform: translateX(0%);
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(167, 139, 250, 0.1), rgba(139, 92, 246, 0.1));
            z-index: -1;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        form h2 {
            margin-bottom: 30px;
            color: #a78bfa;
            font-size: 2.2em;
            font-weight: 300;
            text-align: center;
            position: relative;
        }

        form h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #a78bfa, #8b5cf6);
            border-radius: 2px;
        }

        form input {
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        form input:focus {
            border-color: #a78bfa;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(167, 139, 250, 0.3);
        }

        form input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        form button {
            padding: 15px;
            border: none;
            border-radius: 25px;
            background: linear-gradient(45deg, #a78bfa, #8b5cf6);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        form button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        form button:hover::before {
            left: 100%;
        }

        form button:hover {
            background: linear-gradient(45deg, #8b5cf6, #7c3aed);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(167, 139, 250, 0.4);
        }

        form button:active {
            transform: translateY(0px);
        }

        form p {
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
        }

        form a {
            color: #a78bfa;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        form a:hover {
            color: #8b5cf6;
            text-decoration: underline;
        }

        .overlay {
            width: 50%;
            height: 100%;
            background: linear-gradient(45deg, #111, #1a1a2e);
            position: absolute;
            top: 0;
            left: 50%;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.6s ease;
            border-radius: 20px 0 0 20px;
        }

        .main-container.show-register .overlay {
            transform: translateX(-100%);
            border-radius: 0 20px 20px 0;
        }

        .logo-panel {
            text-align: center;
            padding: 40px;
        }

        .logo-panel img {
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
            border-radius: 50%;
            border: 3px solid #a78bfa;
            padding: 20px;
            background: rgba(167, 139, 250, 0.1);
            transition: transform 0.3s ease;
        }

        .logo-panel img:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .logo-panel h3 {
            color: #a78bfa;
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .logo-panel p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .error-message {
            background: linear-gradient(45deg, rgba(255, 87, 87, 0.2), rgba(255, 87, 87, 0.1));
            border: 1px solid rgba(255, 87, 87, 0.3);
            border-left: 4px solid #ff5757;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            border-radius: 8px;
            color: #ffb3b3;
            animation: slideIn 0.3s ease;
        }

        .success-message {
            background: linear-gradient(45deg, rgba(87, 255, 147, 0.2), rgba(87, 255, 147, 0.1));
            border: 1px solid rgba(87, 255, 147, 0.3);
            border-left: 4px solid #57ff93;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            border-radius: 8px;
            color: #b3ffcc;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                width: 95%;
                height: 90vh;
                flex-direction: column;
            }

            .form-container {
                width: 100%;
                height: 70%;
                padding: 30px;
            }

            .overlay {
                width: 100%;
                height: 30%;
                position: relative;
                left: 0;
                border-radius: 0;
            }

            .main-container.show-register .overlay {
                transform: translateX(0);
            }

            .logo-panel img {
                width: 80px;
                height: 80px;
            }
        }

        /* Loading animation */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="main-container" id="mainContainer">
        <!-- Login Form -->
        <div class="form-container login-container">
            <form method="POST" action="" id="loginForm">
                <h2>Login</h2>
                <?php if (!empty($loginError)): ?>
                    <div class="error-message"><?php echo $loginError; ?></div>
                <?php endif; ?>
                <input type="email" name="login_email" placeholder="Digite seu email" required />
                <input type="password" name="login_password" placeholder="Digite sua senha" required />
                <button type="submit" name="login" id="loginBtn">Entrar</button>
                <p>Não tem uma conta? <a href="#" id="toRegister">Cadastre-se aqui</a></p>
            </form>
        </div>

        <!-- Register Form -->
        <div class="form-container register-container">
            <form method="POST" action="" id="registerForm">
                <h2>Cadastro</h2>
                <?php if (!empty($registerError)): ?>
                    <div class="error-message"><?php echo $registerError; ?></div>
                <?php endif; ?>
                <?php if (!empty($registerSuccess)): ?>
                    <div class="success-message"><?php echo $registerSuccess; ?></div>
                <?php endif; ?>
                <input type="text" name="register_nome" placeholder="Digite seu nome completo" required value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>" />
                <input type="email" name="register_email" placeholder="Digite seu email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />
                <input type="password" name="register_password" placeholder="Digite sua senha (min. 6 caracteres)" required />
                <button type="submit" name="register" id="registerBtn">Cadastrar</button>
                <p>Já tem uma conta? <a href="#" id="toLogin">Faça login aqui</a></p>
            </form>
        </div>

        <!-- Overlay with Logo -->
        <div class="overlay">
            <div class="overlay-panel logo-panel">
                <img src="assets/images/augebit-logo.png" alt="AugeBit Logo" />
                <p>Bem-vindo ao nosso sistema</p>
            </div>
        </div>
    </div>

    <script>
        const mainContainer = document.getElementById('mainContainer');
        const toRegister = document.getElementById('toRegister');
        const toLogin = document.getElementById('toLogin');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');

        // Show registration form if there was a registration error or success
        <?php if (!empty($registerError) || !empty($registerSuccess)): ?>
            mainContainer.classList.add('show-register');
        <?php endif; ?>

        // Switch to register form
        toRegister.addEventListener('click', (e) => {
            e.preventDefault();
            mainContainer.classList.add('show-register');
        });

        // Switch to login form
        toLogin.addEventListener('click', (e) => {
            e.preventDefault();
            mainContainer.classList.remove('show-register');
        });

        // Add loading state to forms
        loginForm.addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            loginBtn.textContent = '';
        });

        registerForm.addEventListener('submit', function() {
            registerBtn.classList.add('loading');
            registerBtn.textContent = '';
        });

        // Input focus effects
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-switch to login after successful registration
        <?php if (!empty($registerSuccess)): ?>
            setTimeout(() => {
                mainContainer.classList.remove('show-register');
            }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>