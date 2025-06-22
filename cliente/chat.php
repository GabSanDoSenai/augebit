<?php
session_start();

// Incluir conexão com banco - tentar diferentes caminhos
$conexao_paths = [
    '../conexao.php',
    'conexao.php',
    '../config/conexao.php',
    'config/conexao.php',
    'chat_conexao.php'
];

$pdo = null;
foreach ($conexao_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        if (isset($pdo) && $pdo !== null) {
            break;
        }
    }
}

// Se ainda não temos conexão, tentar criar uma diretamente
if (!isset($pdo) || $pdo === null) {
    try {
        // Configurações do banco - AJUSTE CONFORME SUA CONFIGURAÇÃO
        $host = 'localhost';
        $dbname = 'augebit';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("
        <div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>
            <h3>❌ Erro de Conexão</h3>
            <p><strong>Não foi possível conectar ao banco de dados.</strong></p>
            <p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>Verifique:</strong></p>
            <ul>
                <li>Se o MySQL está rodando</li>
                <li>Se o banco 'augebit' existe</li>
                <li>Se as credenciais estão corretas</li>
                <li>Se as tabelas do chat foram criadas (execute setup_chat.sql)</li>
            </ul>
            <p><a href='../dashboard_cliente.php' style='color: #721c24;'>← Voltar ao Dashboard</a></p>
        </div>
        ");
    }
}

$cliente_id = $_SESSION['usuario_id'];

// Buscar nome do cliente se não estiver na sessão
if (!isset($_SESSION['nome']) || empty($_SESSION['nome'])) {
    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $usuario = $stmt->fetch();
    $cliente_nome = $usuario ? $usuario['nome'] : 'Cliente';
    $_SESSION['nome'] = $cliente_nome;
} else {
    $cliente_nome = $_SESSION['nome'];
}

// Buscar ou criar conversa do cliente
$stmt = $pdo->prepare("
    SELECT id FROM conversas 
    WHERE cliente_id = ? AND status = 'ativa' 
    ORDER BY criado_em DESC LIMIT 1
");
$stmt->execute([$cliente_id]);
$conversa = $stmt->fetch();

if (!$conversa) {
    // Criar nova conversa
    $stmt = $pdo->prepare("
        INSERT INTO conversas (cliente_id, titulo) 
        VALUES (?, ?)
    ");
    $stmt->execute([$cliente_id, "Conversa - " . $cliente_nome]);
    $conversa_id = $pdo->lastInsertId();
} else {
    $conversa_id = $conversa['id'];
}

// Processar envio de mensagem
if (isset($_POST['acao']) && $_POST['acao'] == 'enviar_mensagem' && !empty($_POST['mensagem'])) {
    $mensagem = trim($_POST['mensagem']);
    
    $stmt = $pdo->prepare("
        INSERT INTO chat_mensagens (conversa_id, remetente_id, mensagem) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$conversa_id, $cliente_id, $mensagem]);
    
    // Atualizar timestamp da conversa
    $stmt = $pdo->prepare("
        UPDATE conversas SET ultima_mensagem = NOW() WHERE id = ?
    ");
    $stmt->execute([$conversa_id]);
    
    echo json_encode(['status' => 'success']);
    exit();
}

// Buscar mensagens se for requisição AJAX
if (isset($_GET['buscar_mensagens'])) {
    $ultimo_id = isset($_GET['ultimo_id']) ? (int)$_GET['ultimo_id'] : 0;
    
    $stmt = $pdo->prepare("
        SELECT m.*, u.nome as remetente_nome, u.tipo as remetente_tipo
        FROM chat_mensagens m
        JOIN usuarios u ON m.remetente_id = u.id
        WHERE m.conversa_id = ? AND m.id > ?
        ORDER BY m.enviado_em ASC
    ");
    $stmt->execute([$conversa_id, $ultimo_id]);
    $mensagens = $stmt->fetchAll();
    
    // Marcar mensagens como lidas
    if (!empty($mensagens)) {
        $stmt = $pdo->prepare("
            UPDATE chat_mensagens 
            SET lida = TRUE 
            WHERE conversa_id = ? AND remetente_id != ? AND lida = FALSE
        ");
        $stmt->execute([$conversa_id, $cliente_id]);
    }
    
    echo json_encode($mensagens);
    exit();
}

// Buscar mensagens iniciais
$stmt = $pdo->prepare("
    SELECT m.*, u.nome as remetente_nome, u.tipo as remetente_tipo
    FROM chat_mensagens m
    JOIN usuarios u ON m.remetente_id = u.id
    WHERE m.conversa_id = ?
    ORDER BY m.enviado_em ASC
    LIMIT 50
");
$stmt->execute([$conversa_id]);
$mensagens_iniciais = $stmt->fetchAll();

// Marcar mensagens como lidas
$stmt = $pdo->prepare("
    UPDATE chat_mensagens 
    SET lida = TRUE 
    WHERE conversa_id = ? AND remetente_id != ? AND lida = FALSE
");
$stmt->execute([$conversa_id, $cliente_id]);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Suporte</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="chat_style.css">
    <style>
        /* Reset básico caso o CSS principal não carregue */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f5f6fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header básico */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            position: relative;
            z-index: 10;
            padding: 10px 0;
        }

        .logo-header {
            display: flex;
            align-items: center;
        }

        .logo-header img {
            position: relative;
            width: 70px;
            height: 80px;
            top: -16px; 
            left:30px
        }

        .logo-header h1 {
            position:relative; 
            font-size: 30px;
            font-family: 'Poppins';
            font-weight: 300;
            left: 30px;
        }

        nav ul {
            padding-right: 100px;
            display: flex;
            gap: 80px;
            list-style: none;
        }

        nav a {
            color: #3E236A;
            text-decoration: none;
            font-size: 19px;
            font-family: 'Poppins';
            transition: all 0.3s ease;
            font-weight: 450;
        }
        /* Variáveis CSS */
        :root {
            --primary-color: #9999FF;
            --accent-color: #6c5ce7;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            height: 600px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .status-online {
            width: 10px;
            height: 10px;
            background: #2ecc71;
            border-radius: 50%;
            display: inline-block;
            margin-left: 8px;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .message {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            line-height: 1.4;
            position: relative;
        }
        
        .message.cliente {
            background: var(--primary-color);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        
        .message.gestor, .message.admin, .message.funcionario {
            background: white;
            color: #333;
            align-self: flex-start;
            border: 1px solid #e1e5e9;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
        }
        
        .message.cliente .message-time {
            color: rgba(255,255,255,0.8);
        }
        
        .message-sender {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 2px;
            color: var(--accent-color);
        }
        
        .chat-input {
            padding: 20px;
            border-top: 1px solid #e1e5e9;
            background: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        .input-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 25px;
            resize: none;
            max-height: 100px;
            min-height: 45px;
            outline: none;
            transition: var(--transition);
        }
        
        .message-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(153, 153, 255, 0.1);
        }
        
        .send-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        
        .send-btn:hover {
            background: var(--accent-color);
            transform: scale(1.05);
        }
        
        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .typing-indicator {
            display: none;
            padding: 8px 16px;
            color: #666;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }
        
        .empty-state p {
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                margin: 10px;
                height: calc(100vh - 120px);
                border-radius: 8px;
            }
            
            .message {
                max-width: 85%;
                font-size: 0.9rem;
            }
            
            .chat-header {
                padding: 12px 15px;
            }
            
            .chat-messages {
                padding: 15px;
            }
            
            .chat-input {
                padding: 15px;
            }
        }
        
        /* Scrollbar personalizada */
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        
        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .chat-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
<header>
        <div class="logo-header">    
            <img src="../assets/img/augebit.logo.png" alt="Logo da empresa"> 
            <h1>AUGEBIT</h1>
        </div>    
        <nav>       
            <ul>         
                <li><a href="../index.php" class="active">Home</a></li>         
                <li><a href="dashboard_cliente.php" class="acount">Projetos</a></li>
                <li><a href="chat.php" class="bate-papo">Chat</a></li>            
                <li><a href="../logout.php" class="contato">Sair</a></li>    
            </ul>     
        </nav>   
    </header>   

    <main class="container">
        <div class="chat-container">
            <div class="chat-header">
                <div>
                    <h3>💬 Suporte AugeBit</h3>
                    <small>Converse com nossa equipe</small>
                </div>
                <div>
                    <span class="status-online"></span>
                    <small>Online</small>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($mensagens_iniciais)): ?>
                    <div class="empty-state">
                        <h4>👋 Olá, <?= htmlspecialchars($cliente_nome) ?>!</h4>
                        <p>Bem-vindo ao nosso chat de suporte.</p>
                        <p>Nossa equipe está pronta para ajudá-lo. Envie sua primeira mensagem!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($mensagens_iniciais as $msg): ?>
                        <div class="message <?= $msg['remetente_tipo'] ?>" data-id="<?= $msg['id'] ?>">
                            <?php if ($msg['remetente_tipo'] != 'cliente'): ?>
                                <div class="message-sender">
                                    <?= htmlspecialchars($msg['remetente_nome']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($msg['mensagem'])) ?>
                            </div>
                            <div class="message-time">
                                <?= date('H:i', strtotime($msg['enviado_em'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="typing-indicator" id="typingIndicator">
                Suporte está digitando...
            </div>

            <div class="chat-input">
                <form id="chatForm" class="input-group">
                    <textarea 
                        id="messageInput" 
                        class="message-input" 
                        placeholder="Digite sua mensagem..."
                        rows="1"
                        maxlength="1000"
                        required
                    ></textarea>
                    <button type="submit" class="send-btn" id="sendBtn">
                        ➤
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        class ChatCliente {
            constructor() {
                this.chatMessages = document.getElementById('chatMessages');
                this.messageInput = document.getElementById('messageInput');
                this.chatForm = document.getElementById('chatForm');
                this.sendBtn = document.getElementById('sendBtn');
                this.ultimoIdMensagem = this.getUltimoIdMensagem();
                this.isTyping = false;
                
                this.init();
            }
            
            init() {
                // Event listeners
                this.chatForm.addEventListener('submit', (e) => this.enviarMensagem(e));
                this.messageInput.addEventListener('keydown', (e) => this.handleKeydown(e));
                this.messageInput.addEventListener('input', () => this.autoResize());
                
                // Iniciar polling para novas mensagens
                this.iniciarPolling();
                
                // Scroll para o final
                this.scrollToBottom();
            }
            
            getUltimoIdMensagem() {
                const mensagens = this.chatMessages.querySelectorAll('.message[data-id]');
                if (mensagens.length > 0) {
                    const ultimaMensagem = mensagens[mensagens.length - 1];
                    return parseInt(ultimaMensagem.dataset.id) || 0;
                }
                return 0;
            }
            
            handleKeydown(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.chatForm.dispatchEvent(new Event('submit'));
                }
            }
            
            autoResize() {
                this.messageInput.style.height = 'auto';
                this.messageInput.style.height = Math.min(this.messageInput.scrollHeight, 100) + 'px';
            }
            
            async enviarMensagem(e) {
                e.preventDefault();
                
                const mensagem = this.messageInput.value.trim();
                if (!mensagem) return;
                
                // Desabilitar form
                this.sendBtn.disabled = true;
                this.messageInput.disabled = true;
                
                try {
                    const formData = new FormData();
                    formData.append('acao', 'enviar_mensagem');
                    formData.append('mensagem', mensagem);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        this.messageInput.value = '';
                        this.autoResize();
                        this.buscarNovasMensagens();
                    }
                } catch (error) {
                    console.error('Erro ao enviar mensagem:', error);
                    alert('Erro ao enviar mensagem. Tente novamente.');
                } finally {
                    // Reabilitar form
                    this.sendBtn.disabled = false;
                    this.messageInput.disabled = false;
                    this.messageInput.focus();
                }
            }
            
            async buscarNovasMensagens() {
                try {
                    const response = await fetch(`?buscar_mensagens=1&ultimo_id=${this.ultimoIdMensagem}`);
                    const mensagens = await response.json();
                    
                    if (mensagens.length > 0) {
                        // Remover empty state se existir
                        const emptyState = this.chatMessages.querySelector('.empty-state');
                        if (emptyState) {
                            emptyState.remove();
                        }
                        
                        mensagens.forEach(msg => this.adicionarMensagem(msg));
                        this.ultimoIdMensagem = Math.max(...mensagens.map(m => m.id));
                        this.scrollToBottom();
                    }
                } catch (error) {
                    console.error('Erro ao buscar mensagens:', error);
                }
            }
            
            adicionarMensagem(msg) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.remetente_tipo}`;
                messageDiv.dataset.id = msg.id;
                
                let senderHtml = '';
                if (msg.remetente_tipo !== 'cliente') {
                    senderHtml = `<div class="message-sender">${this.escapeHtml(msg.remetente_nome)}</div>`;
                }
                
                const time = new Date(msg.enviado_em).toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                messageDiv.innerHTML = `
                    ${senderHtml}
                    <div class="message-content">${this.escapeHtml(msg.mensagem).replace(/\n/g, '<br>')}</div>
                    <div class="message-time">${time}</div>
                `;
                
                this.chatMessages.appendChild(messageDiv);
            }
            
            scrollToBottom() {
                setTimeout(() => {
                    this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
                }, 100);
            }
            
            iniciarPolling() {
                setInterval(() => {
                    this.buscarNovasMensagens();
                }, 3000); // Verificar a cada 3 segundos
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // Inicializar chat quando página carregar
        document.addEventListener('DOMContentLoaded', () => {
            new ChatCliente();
        });
    </script>
</body>
</html>