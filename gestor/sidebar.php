<?php
// components/sidebar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para obter o nome do tipo de usuário
function getUserTypeName($type)
{
    $types = [
        'admin' => 'Administrador',
        'funcionario' => 'Funcionário',
        'cliente' => 'Cliente'
    ];
    return $types[$type] ?? 'Usuário';
}

$basePath = '/augebit/gestor/';

// Menu COMPLETO para todos os usuários
$navigation = [
    'all' => [
        [
            'icon' => 'dashboard.png',
            'label' => 'Dashboard',
            'url' => $basePath . 'dashboard_gestor.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'dashboard_gestor.php'
        ],
        [
            'icon' => 'projetos.png',
            'label' => 'Projetos',
            'url' => $basePath . 'projetos/listar_projetos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'projetos') !== false
        ],
        [
            'icon' => 'tarefas.png',
            'label' => 'Tarefas',
            'url' => $basePath . 'tarefas/tarefas.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'tarefas') !== false
        ],
        [
            'icon' => 'funcionarios.png',
            'label' => 'Funcionários',
            'url' => $basePath . 'usuarios/funcionarios.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'funcionarios') !== false
        ],
        [
            'icon' => 'documentos.png',
            'label' => 'Documentos',
            'url' => $basePath . 'documentos/visualizar_documentos.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'documentos') !== false
        ]
    ],
    'cliente' => [
        // Itens específicos para clientes (se necessário)
    ]
];

$userType = $_SESSION['usuario_tipo'] ?? 'cliente';

// Combina menus 'all' com menus específicos do tipo de usuário
$navItems = array_merge(
    $navigation['all'] ?? [],
    $navigation[$userType] ?? []
);
?>

<nav class="sidebar" id="sidebar">
    <div class="logo">
        <img src="../assets/img/augebit.logo.png" alt="Logo" class="logo-img">
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
                <a href="<?= htmlspecialchars($item['url']) ?>" class="nav-link <?= $item['active'] ? 'active' : '' ?>"
                    title="<?= htmlspecialchars($item['label']) ?>" data-url="<?= htmlspecialchars($item['url']) ?>">
                    <span class="nav-icon">
                        <img src="../assets/img/<?= $item['icon'] ?>" alt="<?= htmlspecialchars($item['label']) ?>">
                    </span>
                    <span class="nav-text"><?= htmlspecialchars($item['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>

        <li class="nav-item nav-separator">
            <hr>
        </li>

        <li class="nav-item">
            <a href="/augebit/logout.php" class="nav-link nav-logout" title="Sair do Sistema"
                data-url="/augebit/logout.php">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Sair</span>
            </a>
        </li>

    </ul>

    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
</nav>

<style>
    /* Sidebar Styles - Versão Atualizada */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 280px;
        height: 100vh;
        background: rgba(153, 153, 255, 0.95);
        backdrop-filter: blur(5px);
        box-shadow: 15px 0 25px rgba(0, 0, 0, 0.1);
        padding: 0;
        z-index: 1000;
        transition: all 0.3s ease;
        overflow-y: auto;
        border-radius: 0 30px 30px 0;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
    }

    /* Logo Section */
    .logo {
        text-align: center;
        padding: 1.5rem 1rem 1rem;
        position: relative;
    }

    .logo-img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #3E236A;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .logo-img:hover {
        transform: scale(1.05);
    }

    /* User Info Section */
    .user-info {
        text-align: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 0.5rem;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3E236A, #9999FF);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
        color: white;
        font-size: 1.25rem;
        font-weight: 600;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    }

    .user-info h3 {
        color: #3E236A;
        margin-bottom: 0.25rem;
        font-size: 1rem;
        font-weight: 600;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }

    .user-info span {
        color: #3E236A;
        font-size: 0.8rem;
        font-weight: 500;
        opacity: 0.9;
    }

    /* Navigation Menu */
    .nav-menu {
        list-style: none;
        padding: 0.5rem 1rem;
        margin: 0;
    }

    .nav-item {
        margin-bottom: 0.5rem;
    }

    .nav-separator {
        margin: 1rem 0;
    }

    .nav-separator hr {
        border: none;
        height: 1px;
        background: rgba(255, 255, 255, 0.3);
        margin: 0 1rem;
    }

    /* Navigation Links */
    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: #3E236A;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
        font-size: 0.9rem;
        background: rgba(255, 255, 255, 0.3);
        margin: 0.25rem 0;
    }

    .nav-link:hover {
        background: rgba(62, 35, 106, 0.7);
        color: white;
        transform: translateX(5px);
    }

    .nav-link:hover .nav-icon img {
        filter: brightness(0) invert(1);
    }

    .nav-link.active {
        background: #3E236A;
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 8px rgba(62, 35, 106, 0.3);
    }

    .nav-link.active .nav-icon img {
        filter: brightness(0) invert(1);
    }

    .nav-icon {
        margin-right: 0.75rem;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-icon img {
        width: 100%;
        height: auto;
        transition: all 0.2s ease;
    }

    .nav-text {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Logout Button */
    .nav-link.nav-logout {
        background: rgba(238, 90, 90, 0.1);
        color: #ee5a5a;
    }

    .nav-link.nav-logout:hover {
        background: rgba(238, 90, 90, 0.3);
        color: white;
    }

    /* Sidebar Toggle Button */
    .sidebar-toggle {
        display: none;
        position: absolute;
        top: 1rem;
        right: -50px;
        width: 40px;
        height: 40px;
        background: rgba(153, 153, 255, 0.9);
        border: none;
        border-radius: 50%;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 4px;
        z-index: 1001;
        transition: all 0.3s ease;
    }

    .sidebar-toggle:hover {
        background: #3E236A;
    }

    .sidebar-toggle span {
        width: 20px;
        height: 2px;
        background: white;
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
        transform: rotate(-45deg) translate(5px, -5px);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: none;
        }

        .sidebar.active {
            transform: translateX(0);
            box-shadow: 15px 0 25px rgba(0, 0, 0, 0.2);
        }

        .sidebar-toggle {
            display: flex;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }
    }

    /* Compact Sidebar Option */
    .sidebar.compact {
        width: 80px;
    }

    .sidebar.compact .logo-img {
        width: 50px;
        height: 50px;
    }

    .sidebar.compact .user-info,
    .sidebar.compact .nav-text {
        display: none;
    }

    .sidebar.compact .nav-link {
        justify-content: center;
        padding: 0.75rem;
    }

    .sidebar.compact .nav-icon {
        margin-right: 0;
    }

    .sidebar.compact .user-avatar {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }

    /* Animations */
    .nav-link.clicked {
        transform: scale(0.95);
    }

    .nav-link.loading {
        position: relative;
        opacity: 0.7;
        pointer-events: none;
    }

    .nav-link.loading::after {
        content: "";
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 12px;
        height: 12px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: translateY(-50%) rotate(360deg);
        }
    }
</style>

<script>
    // Mantendo o mesmo JavaScript original
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.createElement('div');
        sidebarOverlay.id = 'sidebarOverlay';
        sidebarOverlay.className = 'sidebar-overlay';
        document.body.appendChild(sidebarOverlay);

        let isNavigating = false;
        let lastClickTime = 0;
        const DOUBLE_CLICK_THRESHOLD = 300;

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                sidebarToggle.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }

        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebarToggle.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            }
        });

        function isCurrentPage(linkUrl) {
            const currentPath = window.location.pathname;
            const currentFile = currentPath.split('/').pop();
            const linkFile = linkUrl.split('/').pop();

            if (currentFile === linkFile) {
                return true;
            }

            const linkFolder = linkUrl.includes('/') ? linkUrl.split('/')[0] : '';
            if (linkFolder && currentPath.includes(linkFolder)) {
                return true;
            }

            return false;
        }

        const navLinks = document.querySelectorAll('.nav-link:not(.nav-logout)');

        navLinks.forEach(link => {
            const linkUrl = link.getAttribute('data-url') || link.getAttribute('href');

            if (isCurrentPage(linkUrl)) {
                link.classList.add('active');
            }

            link.addEventListener('click', function (e) {
                const currentTime = Date.now();
                const timeDiff = currentTime - lastClickTime;

                if (isNavigating || timeDiff < DOUBLE_CLICK_THRESHOLD || isCurrentPage(linkUrl)) {
                    e.preventDefault();
                    link.classList.add('clicked');
                    setTimeout(() => {
                        link.classList.remove('clicked');
                    }, 150);
                    return false;
                }

                isNavigating = true;
                lastClickTime = currentTime;
                link.classList.add('loading');
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                setTimeout(() => {
                    isNavigating = false;
                    link.classList.remove('loading');
                }, 2000);
            });
        });

        const logoutLink = document.querySelector('.nav-logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', function (e) {
                logoutLink.classList.add('loading');
            });
        }

        window.addEventListener('beforeunload', function () {
            isNavigating = false;
        });

        window.addEventListener('load', function () {
            isNavigating = false;
            navLinks.forEach(link => {
                link.classList.remove('loading');
            });
        });
    });
</script>