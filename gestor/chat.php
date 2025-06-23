<?php include 'sidebar.php'; ?>
<?php

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
        </div>
        ");
    }
}

$gestor_id = $_SESSION['usuario_id'];
$gestor_nome = $_SESSION['nome'] ?? 'Gestor';

// Verificar se foi selecionada uma conversa específica
$conversa_id = isset($_GET['conversa']) ? (int)$_GET['conversa'] : null;
$cliente_selecionado = null;

if ($conversa_id) {
    // Buscar dados da conversa
    $stmt = $pdo->prepare("
        SELECT c.*, u.nome as cliente_nome 
        FROM conversas c
        JOIN usuarios u ON c.cliente_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$conversa_id]);
    $cliente_selecionado = $stmt->fetch();
}

// Processar envio de mensagem
if (isset($_POST['acao']) && $_POST['acao'] == 'enviar_mensagem' && !empty($_POST['mensagem']) && $conversa_id) {
    $mensagem = trim($_POST['mensagem']);
    
    $stmt = $pdo->prepare("
        INSERT INTO chat_mensagens (conversa_id, remetente_id, mensagem) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$conversa_id, $gestor_id, $mensagem]);
    
    // Atualizar timestamp da conversa
    $stmt = $pdo->prepare("
        UPDATE conversas SET ultima_mensagem = NOW() WHERE id = ?
    ");
    $stmt->execute([$conversa_id]);
    
    echo json_encode(['status' => 'success']);
    exit();
}

// Buscar mensagens se for requisição AJAX
if (isset($_GET['buscar_mensagens']) && $conversa_id) {
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
    
    // Marcar mensagens do cliente como lidas
    if (!empty($mensagens)) {
        $stmt = $pdo->prepare("
            UPDATE chat_mensagens 
            SET lida = TRUE 
            WHERE conversa_id = ? AND remetente_id != ? AND lida = FALSE
        ");
        $stmt->execute([$conversa_id, $gestor_id]);
    }
    
    echo json_encode($mensagens);
    exit();
}

// Buscar lista de conversas com clientes
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.cliente_id,
        u.nome as cliente_nome,
        c.titulo,
        c.ultima_mensagem,
        c.status,
        COUNT(CASE WHEN m.lida = 0 AND m.remetente_id = c.cliente_id THEN 1 END) as mensagens_nao_lidas,
        (SELECT mensagem FROM chat_mensagens WHERE conversa_id = c.id ORDER BY enviado_em DESC LIMIT 1) as ultima_mensagem_texto
    FROM conversas c
    LEFT JOIN usuarios u ON c.cliente_id = u.id
    LEFT JOIN chat_mensagens m ON c.id = m.conversa_id
    WHERE c.status = 'ativa'
    GROUP BY c.id
    ORDER BY c.ultima_mensagem DESC
");
$stmt->execute();
$conversas = $stmt->fetchAll();

// Buscar mensagens da conversa selecionada
$mensagens_iniciais = [];
if ($conversa_id) {
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
    
    // Marcar como lidas
    $stmt = $pdo->prepare("
        UPDATE chat_mensagens 
        SET lida = TRUE 
        WHERE conversa_id = ? AND remetente_id != ? AND lida = FALSE
    ");
    $stmt->execute([$conversa_id, $gestor_id]);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - AUGEBIT</title>
    <link rel="stylesheet" href="css/geral.css">
    <style>
        /* Variáveis CSS */
        :root {
            --primary-color: #9999FF;
            --accent-color: #6c5ce7;
            --success-color: #2ecc71;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        .chat-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            height: calc(100vh - 40px);
            max-height: 800px;
        }
        
        /* Lista de conversas */
        .conversations-panel {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
        }
        
        .conversations-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .conversations-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
        }
        
        .conversation-item.active {
            background: #e8f2ff;
            border-left: 4px solid var(--primary-color);
        }
        
        .conversation-client {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .conversation-preview {
            font-size: 0.9rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.8rem;
            color: #999;
        }
        
        .unread-badge {
            background: var(--primary-color);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
        }
        
        /* Chat principal */
        .chat-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
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
            background: var(--success-color);
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
            background: white;
            color: #333;
            align-self: flex-start;
            border: 1px solid #e1e5e9;
            border-bottom-left-radius: 4px;
        }
        
        .message.gestor, .message.admin {
            background: var(--primary-color);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
        }
        
        .message.gestor .message-time, .message.admin .message-time {
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
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 40px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .empty-state h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        
        .no-conversation {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100%;
            color: #666;
        }
        
        .no-conversation h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        /* Scrollbar personalizada */
        .chat-messages::-webkit-scrollbar,
        .conversations-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .chat-messages::-webkit-scrollbar-track,
        .conversations-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .chat-messages::-webkit-scrollbar-thumb,
        .conversations-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb:hover,
        .conversations-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .chat-layout {
                grid-template-columns: 1fr;
                height: calc(100vh - 20px);
            }
            
            .conversations-panel {
                height: 200px;
            }
            
            .chat-container {
                height: 400px;
            }
            
            .message {
                max-width: 85%;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="chat-layout">
            <!-- Lista de Conversas -->
            <div class="conversations-panel">
                <div class="conversations-header">
                    <h3>💬 Conversas Ativas</h3>
                    <small>Clientes aguardando resposta</small>
                </div>
                
                <div class="conversations-list">
                    <?php if (empty($conversas)): ?>
                        <div class="empty-state">
                            <p>Nenhuma conversa ativa no momento.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversas as $conv): ?>
                            <div class="conversation-item <?= $conv['id'] == $conversa_id ? 'active' : '' ?>" 
                                 onclick="selecionarConversa(<?= $conv['id'] ?>)">
                                <div class="conversation-client">
                                    <strong><?= htmlspecialchars($conv['cliente_nome']) ?></strong>
                                    <?php if ($conv['mensagens_nao_lidas'] > 0): ?>
                                        <span class="unread-badge"><?= $conv['mensagens_nao_lidas'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-preview">
                                    <?= htmlspecialchars(substr($conv['ultima_mensagem_texto'] ?? 'Nova conversa', 0, 50)) ?>...
                                </div>
                                <div class="conversation-time">
                                    <?= date('d/m H:i', strtotime($conv['ultima_mensagem'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Principal -->
            <div class="chat-container">
                <?php if (!$cliente_selecionado): ?>
                    <div class="no-conversation">
                        <h3>👈 Selecione uma conversa</h3>
                        <p>Escolha um cliente na lista ao lado para iniciar o atendimento.</p>
                    </div>
                <?php else: ?>
                    <div class="chat-header">
                        <div>
                            <h3>💬 <?= htmlspecialchars($cliente_selecionado['cliente_nome']) ?></h3>
                            <small><?= htmlspecialchars($cliente_selecionado['titulo']) ?></small>
                        </div>
                        <div>
                            <span class="status-online"></span>
                            <small>Online</small>
                        </div>
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <?php if (empty($mensagens_iniciais)): ?>
                            <div class="empty-state">
                                <h4>💬 Conversa iniciada</h4>
                                <p>Inicie o atendimento ao cliente <?= htmlspecialchars($cliente_selecionado['cliente_nome']) ?>.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($mensagens_iniciais as $msg): ?>
                                <div class="message <?= $msg['remetente_tipo'] ?>" data-id="<?= $msg['id'] ?>">
                                    <?php if ($msg['remetente_tipo'] == 'cliente'): ?>
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

                    <div class="chat-input">
                        <form id="chatForm" class="input-group">
                            <textarea 
                                id="messageInput" 
                                class="message-input" 
                                placeholder="Digite sua resposta..."
                                rows="1"
                                maxlength="1000"
                                required
                            ></textarea>
                            <button type="submit" class="send-btn" id="sendBtn">
                                ➤
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        const conversaAtual = <?= $conversa_id ?? 'null' ?>;
        
        function selecionarConversa(conversaId) {
            window.location.href = `?conversa=${conversaId}`;
        }
        
        class ChatGestor {
            constructor() {
                if (!conversaAtual) return;
                
                this.chatMessages = document.getElementById('chatMessages');
                this.messageInput = document.getElementById('messageInput');
                this.chatForm = document.getElementById('chatForm');
                this.sendBtn = document.getElementById('sendBtn');
                this.ultimoIdMensagem = this.getUltimoIdMensagem();
                
                this.init();
            }
            
            init() {
                this.chatForm.addEventListener('submit', (e) => this.enviarMensagem(e));
                this.messageInput.addEventListener('keydown', (e) => this.handleKeydown(e));
                this.messageInput.addEventListener('input', () => this.autoResize());
                
                this.iniciarPolling();
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
                    this.sendBtn.disabled = false;
                    this.messageInput.disabled = false;
                    this.messageInput.focus();
                }
            }
            
            async buscarNovasMensagens() {
                try {
                    const response = await fetch(`?buscar_mensagens=1&ultimo_id=${this.ultimoIdMensagem}&conversa=${conversaAtual}`);
                    const mensagens = await response.json();
                    
                    if (mensagens.length > 0) {
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
                if (msg.remetente_tipo === 'cliente') {
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
                }, 3000);
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            new ChatGestor();
        });
    </script>
</body>
</html>