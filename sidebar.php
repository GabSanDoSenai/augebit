<?php
// components/sidebar.php
// Componente de sidebar reutilizável

// Verificar se a sessão está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir navegação baseada no tipo de usuário
$navigation = [
    'admin' => [
        [
            'icon' => '📊',
            'label' => 'Dashboard',
            'url' => 'dashboard_gestor.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'dashboard_gestor.php'
        ],
        [
            'icon' => '📋',
            'label' => 'Projetos',
            'url' => 'gestor/projetos/listar_projetos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'projetos') !== false
        ],
        [
            'icon' => '✅',
            'label' => 'Tarefas',
            'url' => 'tarefas/listar_tarefas.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'tarefas') !== false
        ],
        [
            'icon' => '👥',
            'label' => 'Funcionários',
            'url' => 'gestor/funcionariosGestor.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'funcionarios') !== false
        ],
        [
            'icon' => '📁',
            'label' => 'Documentos',
            'url' => 'gestor/documentos/visualizar_documentos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'documentos') !== false
        ],
        [
            'icon' => '📈',
            'label' => 'Relatórios',
            'url' => 'gestor/projetos/avaliar_projetos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'avaliar_projetos') !== false
        ],
        [
            'icon' => '⚙️',
            'label' => 'Configurações',
            'url' => 'gestor/configuracoes.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'configuracoes') !== false
        ]
    ],
    'funcionario' => [
        [
            'icon' => '📊',
            'label' => 'Dashboard',
            'url' => 'dashboard_funcionario.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'dashboard_funcionario.php'
        ],
        [
            'icon' => '✅',
            'label' => 'Minhas Tarefas',
            'url' => 'funcionario/tarefas.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'tarefas') !== false
        ],
        [
            'icon' => '📋',
            'label' => 'Projetos',
            'url' => 'funcionario/projetos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'projetos') !== false
        ],
        [
            'icon' => '📁',
            'label' => 'Documentos',
            'url' => 'funcionario/documentos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'documentos') !== false
        ]
    ],
    'cliente' => [
        [
            'icon' => '📊',
            'label' => 'Dashboard',
            'url' => 'dashboard_cliente.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'dashboard_cliente.php'
        ],
        [
            'icon' => '📋',
            'label' => 'Meus Projetos',
            'url' => 'cliente/projetos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'projetos') !== false
        ],
        [
            'icon' => '📁',
            'label' => 'Documentos',
            'url' => 'cliente/documentos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'documentos') !== false
        ]
    ]
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
        <h1>AugeBit</h1>
        <p>Sistema de Gestão</p>
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
               title="<?= htmlspecialchars($item['label']) ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span class="nav-text"><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
        
        <li class="nav-item nav-separator">
            <hr>
        </li>
        
        <li class="nav-item">
            <a href="perfil.php" class="nav-link" title="Meu Perfil">
                <span class="nav-icon">👤</span>
                <span class="nav-text">Meu Perfil</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="ajuda.php" class="nav-link" title="Ajuda">
                <span class="nav-icon">❓</span>
                <span class="nav-text">Ajuda</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="logout.php" class="nav-link nav-logout" title="Sair do Sistema">
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

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

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
</style>

<script>
// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
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
    
    // Close sidebar on window resize to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        }
    });
    
    // Add active class to current page
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath.includes(linkPath) && linkPath !== '#') {
            link.classList.add('active');
        }
    });
});
</script>