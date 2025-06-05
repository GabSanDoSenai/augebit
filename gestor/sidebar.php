<?php
// components/sidebar.php
// Componente de sidebar reutilizável

// Verificar se a sessão está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir navegação baseada no tipo de usuário
$basePath = '/augebit/gestor/'; // Caminho base absoluto

$navigation = [
    'admin' => [
        [
            'icon' => '📊',
            'label' => 'Dashboard',
            'url' => $basePath . 'dashboard_gestor.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'dashboard_gestor.php'
        ],
        [
            'icon' => '📋',
            'label' => 'Projetos',
            'url' => $basePath . 'projetos/listar_projetos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'projetos') !== false
        ],
        [
            'icon' => '✅',
            'label' => 'Tarefas',
            'url' => $basePath . 'tarefas/tarefas.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'tarefas') !== false
        ],
        [
            'icon' => '👥',
            'label' => 'Funcionários',
            'url' => $basePath . 'funcionarios.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'funcionarios') !== false
        ],
        [
            'icon' => '📁',
            'label' => 'Documentos',
            'url' => $basePath . 'documentos/visualizar_documentos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'documentos') !== false
        ],
        [
            'icon' => '📈',
            'label' => 'Relatórios',
            'url' => $basePath . 'relatorio/relatorios.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'avaliar_projetos') !== false
        ],
        [
            'icon' => '⚙️',
            'label' => 'Configurações',
            'url' => $basePath . 'configuracoes.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'configuracoes') !== false
        ],
        [
            'icon' => '👤',
            'label' => 'Perfil',
            'url' => $basePath . 'perfil/perfil.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'configuracoes') !== false
        ]
    ],
];

// Obter tipo de usuário e navegação correspondente
$userType = $_SESSION['usuario_tipo'] ?? 'cliente';
$navItems = $navigation[$userType] ?? $navigation['cliente'];

// Função para obter o nome do tipo de usuário
function getUserTypeName($type) {
    $types = [
        'admin' => 'Gestor do Sistema',
        'funcionario' => 'Funcionário',
        'cliente' => 'Cliente'
    ];
    return $types[$type] ?? 'Usuário';
}
?>

<nav class="sidebar" id="sidebar">
    <div class="logo">
    </div>
    
    <div class="user-info">
        <div class="user-avatar">
            <span><?= strtoupper(substr($_SESSION['usuario_nome'] ?? 'U', 0, 1)) ?></span>
        </div>
        <h3><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?></h3>
        <span><?= getUserTypeName($userType) ?></span>
    </div>
    
    <ul class="nav-menu">
        <?php foreach ($navItems as $item): ?>
        <li class="nav-item">
            <a href="<?= htmlspecialchars($item['url']) ?>" 
               class="nav-link <?= $item['active'] ? 'active' : '' ?>"
               title="<?= htmlspecialchars($item['label']) ?>"
               data-url="<?= htmlspecialchars($item['url']) ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span class="nav-text"><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
        
        <li class="nav-item nav-separator">
            <hr>
        </li>
        
        <li class="nav-item">
            <a href="logout.php" class="nav-link nav-logout" title="Sair do Sistema" data-url="logout.php">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Sair</span>
            </a>
        </li>
    </ul>
    
    <!-- Toggle button for mobile -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
</nav>

<style>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 280px;
    height: 100vh;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 2px 0 20px rgba(0,0,0,0.1);
    padding: 0;
    z-index: 1000;
    transition: transform 0.3s ease;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #ddd transparent;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #bbb;
}

.logo {
    text-align: center;
    padding: 2rem 2rem 1rem;
    border-bottom: 1px solid #eee;
}

.logo h1 {
    color: #667eea;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.logo p {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
}

.user-info {
    text-align: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #eee;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

.user-info h3 {
    color: #333;
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.user-info span {
    color: #666;
    font-size: 0.85rem;
    font-weight: 500;
}

.nav-menu {
    list-style: none;
    padding: 1rem;
    margin: 0;
}

.nav-item {
    margin-bottom: 0.25rem;
}

.nav-separator {
    margin: 1rem 0;
}

.nav-separator hr {
    border: none;
    height: 1px;
    background: #eee;
    margin: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.875rem 1rem;
    color: #555;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.95rem;
    position: relative;
}

.nav-link:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    transform: translateX(5px);
}

.nav-link.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: -1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 100%;
    background: #667eea;
    border-radius: 0 2px 2px 0;
}

.nav-link.nav-logout:hover {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
}

.nav-icon {
    margin-right: 0.75rem;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.nav-text {
    flex: 1;
}

.sidebar-toggle {
    display: none;
    position: absolute;
    top: 1rem;
    right: -45px;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 50%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    cursor: pointer;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 3px;
    z-index: 1001;
}

.sidebar-toggle span {
    width: 20px;
    height: 2px;
    background: #333;
    border-radius: 1px;
    transition: all 0.3s ease;
}

.sidebar-toggle.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.sidebar-toggle.active span:nth-child(2) {
    opacity: 0;
}

.sidebar-toggle.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: flex;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    .main-content {
        margin-left: 0;
    }
}

/* Compact sidebar option */
.sidebar.compact {
    width: 70px;
}

.sidebar.compact .logo,
.sidebar.compact .user-info {
    padding: 1rem 0.5rem;
}

.sidebar.compact .logo h1,
.sidebar.compact .logo p,
.sidebar.compact .user-info h3,
.sidebar.compact .user-info span,
.sidebar.compact .nav-text {
    display: none;
}

.sidebar.compact .nav-link {
    justify-content: center;
    padding: 0.875rem;
}

.sidebar.compact .nav-icon {
    margin-right: 0;
}

.sidebar.compact .user-avatar {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
}

/* Animação suave para feedback visual */
.nav-link.clicked {
    transform: scale(0.95);
}

.nav-link.loading {
    opacity: 0.7;
    pointer-events: none;
}
</style>

<script>
// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Variáveis para controle de navegação
    let isNavigating = false;
    let lastClickTime = 0;
    const DOUBLE_CLICK_THRESHOLD = 300; // ms
    
    // Toggle sidebar para mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Fechar sidebar quando redimensionar para desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        }
    });
    
    // Função para verificar se o link está ativo (página atual)
    function isCurrentPage(linkUrl) {
        const currentPath = window.location.pathname;
        const currentFile = currentPath.split('/').pop();
        const linkFile = linkUrl.split('/').pop();
        
        // Verificar correspondência exata do arquivo
        if (currentFile === linkFile) {
            return true;
        }
        
        // Verificar se estamos em uma subpasta relacionada
        const linkFolder = linkUrl.includes('/') ? linkUrl.split('/')[0] : '';
        if (linkFolder && currentPath.includes(linkFolder)) {
            return true;
        }
        
        return false;
    }
    
    // Controle inteligente de navegação
    const navLinks = document.querySelectorAll('.nav-link:not(.nav-logout)');
    
    navLinks.forEach(link => {
        const linkUrl = link.getAttribute('data-url') || link.getAttribute('href');
        
        // Marcar link ativo baseado na página atual
        if (isCurrentPage(linkUrl)) {
            link.classList.add('active');
        }
        
        link.addEventListener('click', function(e) {
            const currentTime = Date.now();
            const timeDiff = currentTime - lastClickTime;
            
            // Prevenir navegação se:
            // 1. Já estamos navegando
            // 2. É um clique duplo muito rápido
            // 3. É a página atual
            if (isNavigating || timeDiff < DOUBLE_CLICK_THRESHOLD || isCurrentPage(linkUrl)) {
                e.preventDefault();
                
                // Feedback visual para indicar que o clique foi registrado
                link.classList.add('clicked');
                setTimeout(() => {
                    link.classList.remove('clicked');
                }, 150);
                
                return false;
            }
            
            // Marcar que estamos navegando
            isNavigating = true;
            lastClickTime = currentTime;
            
            // Adicionar classe de loading
            link.classList.add('loading');
            
            // Remover classe active de todos os links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Adicionar classe active no link clicado
            link.classList.add('active');
            
            // Reset após timeout (caso a navegação falhe)
            setTimeout(() => {
                isNavigating = false;
                link.classList.remove('loading');
            }, 2000);
        });
    });
    
    // Tratamento especial para logout
    const logoutLink = document.querySelector('.nav-logout');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            // Sempre permitir logout
            logoutLink.classList.add('loading');
        });
    }
    
    // Reset do estado de navegação quando a página carrega
    window.addEventListener('beforeunload', function() {
        isNavigating = false;
    });
    
    // Reset do estado quando a página é totalmente carregada
    window.addEventListener('load', function() {
        isNavigating = false;
        navLinks.forEach(link => {
            link.classList.remove('loading');
        });
    });
});
</script>