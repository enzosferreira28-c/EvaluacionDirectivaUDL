<?php
declare(strict_types=1);

if (!function_exists('url')) {
    function url(string $route): string {
        return 'index.php?route=' . urlencode($route);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return 'assets/' . ltrim($path, '/');
    }
}

$currentUser = \App\Core\Security::getCurrentUser();
$userName = $currentUser['nombre'] ?? 'Administrador';
$userEmail = $currentUser['email'] ?? '';
$userInitials = mb_substr($userName, 0, 2);

$currentRoute = (string)($_GET['route'] ?? 'dashboard');
if ($currentRoute === '' || $currentRoute === '/') {
    $currentRoute = 'dashboard';
}

$pageTitle = $pageTitle ?? 'Evaluación Directiva - Universidad de León';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#f4f7f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="shortcut icon" href="<?= htmlspecialchars(asset('img/16x16.png')) ?>" type="image/x-icon">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        udl: {
                            green: '#007547',
                            lime: '#a2cf6d',
                            dark: '#0f392b',
                            'light-bg': '#f4f7f6',
                            border: '#e2e8f0',
                            hover: '#0a261c',
                            accent: '#005a36'
                        }
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/app.css')) ?>">
</head>
<body class="h-full flex flex-col antialiased text-slate-800 bg-[#f4f7f6]">

<div class="flex h-screen overflow-hidden">

    <!-- Mobile Top Navigation Bar -->
    <header class="md:hidden flex items-center justify-between h-16 px-4 bg-[#0f392b] text-white z-30 shadow-md w-full fixed top-0 left-0">
        <div class="flex items-center space-x-3">
            <img src="<?= htmlspecialchars(asset('img/UDL_LogotipoBlanco.png')) ?>" alt="Universidad de León" class="h-9 w-auto object-contain">
        </div>
        <button id="mobileMenuBtn" type="button" class="text-white hover:text-udl-lime focus:outline-none p-2 rounded-md hover:bg-[#154636] transition" aria-label="Abrir menú">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </header>

    <!-- Mobile Sidebar Backdrop -->
    <div id="mobileBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

    <!-- Sidebar Navigation -->
    <aside id="mainSidebar" class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-[#0f392b] text-slate-200 flex flex-col transition-transform duration-300 transform -translate-x-full md:translate-x-0 shadow-2xl md:shadow-none flex-shrink-0">
        
        <!-- Sidebar Brand with Institutional Logo -->
        <div class="h-24 flex items-center justify-between px-6 bg-[#0f392b] border-b border-[#1b503f]">
            <a href="<?= htmlspecialchars(url('dashboard')) ?>" class="flex items-center space-x-3 group w-full">
                <img src="<?= htmlspecialchars(asset('img/UDL_LogotipoBlanco.png')) ?>" alt="Universidad de León" class="h-14 w-auto object-contain transition-transform group-hover:scale-105">
            </a>
            <button id="closeMobileMenuBtn" class="md:hidden text-slate-300 hover:text-white p-1 rounded">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-5 space-y-1.5 overflow-y-auto text-sm font-medium">
            <?php
            $navLinks = [
                [
                    'route' => 'dashboard',
                    'label' => 'Dashboard',
                    'icon'  => 'fa-chart-pie',
                    'active'=> in_array($currentRoute, ['dashboard', '', 'index'], true)
                ],
                [
                    'route' => 'import',
                    'label' => 'Importar Excel',
                    'icon'  => 'fa-file-excel',
                    'active'=> str_starts_with($currentRoute, 'import')
                ],
                [
                    'route' => 'periodos',
                    'label' => 'Periodos',
                    'icon'  => 'fa-calendar-days',
                    'active'=> in_array($currentRoute, ['periodos', 'periodos/crear', 'periodos/eliminar'], true)
                ],
                [
                    'route' => 'catalogos',
                    'label' => 'Catálogos',
                    'icon'  => 'fa-folder-tree',
                    'active'=> str_starts_with($currentRoute, 'catalogos')
                ],
                [
                    'route' => 'comparativa',
                    'label' => 'Comparativa',
                    'icon'  => 'fa-code-compare',
                    'active'=> str_starts_with($currentRoute, 'comparativa')
                ],
                [
                    'route' => 'reportes',
                    'label' => 'Resultados',
                    'icon'  => 'fa-chart-simple',
                    'active'=> in_array($currentRoute, ['reportes', 'reports', 'resultados', 'reportes/index'], true)
                ],
            ];

            foreach ($navLinks as $item):
                $activeClasses = $item['active']
                    ? 'sidebar-nav-item-active'
                    : 'sidebar-nav-item-inactive';
            ?>
                <a href="<?= htmlspecialchars(url($item['route'])) ?>" 
                   class="flex items-center space-x-3.5 px-4 py-3 rounded-lg transition-colors <?= $activeClasses ?>">
                    <span class="w-5 text-center text-base">
                        <i class="fa-solid <?= $item['icon'] ?>"></i>
                    </span>
                    <span class="tracking-wide"><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Sidebar Footer / User Profile & Logout -->
        <div class="p-3 border-t border-[#1b503f] bg-[#0b2b20] space-y-1">
            <div class="px-3 py-2 flex items-center space-x-3 text-slate-300">
                <div class="w-8 h-8 rounded-full bg-emerald-800 text-udl-lime flex items-center justify-center font-bold text-xs shadow-inner">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-white truncate">Prof. <?= htmlspecialchars($userName) ?></p>
                    <p class="text-[10px] text-slate-400 truncate"><?= htmlspecialchars($userEmail) ?></p>
                </div>
            </div>
            <a href="<?= htmlspecialchars(url('cambiar-password')) ?>" 
               class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-emerald-300 hover:bg-emerald-500/15 hover:text-white text-xs font-semibold transition-colors">
                <span class="w-5 text-center text-udl-lime">
                    <i class="fa-solid fa-key"></i>
                </span>
                <span>Cambiar Contraseña</span>
            </a>
            <a href="<?= htmlspecialchars(url('logout')) ?>" 
               class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-rose-300 hover:bg-rose-500/20 hover:text-rose-100 text-xs font-semibold transition-colors">
                <span class="w-5 text-center text-rose-400">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden pt-16 md:pt-0">
        
        <!-- Desktop Top Header Bar (Estilo UDL) -->
        <header class="hidden md:flex items-center justify-between h-16 px-6 lg:px-8 bg-white border-b border-slate-200 shadow-sm z-20">
            <div class="flex items-center space-x-3 text-slate-700">
                <button id="desktopSidebarToggle" type="button" class="p-2 -ml-2 rounded-lg text-slate-500 hover:text-udl-green hover:bg-slate-100 focus:outline-none transition" title="Alternar barra lateral">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h1 class="text-base font-bold text-[#0f392b] tracking-tight">Panel de Control</h1>
                <span class="text-slate-300">/</span>
                <span class="text-sm font-semibold text-udl-green"><?= htmlspecialchars($pageTitle) ?></span>
            </div>

            <!-- User Menu Dropdown (Estilo UDL) -->
            <div class="relative" id="userMenuDropdownContainer">
                <button id="userMenuBtn" type="button" class="flex items-center space-x-2.5 px-3 py-1.5 rounded-xl hover:bg-slate-100/90 transition focus:outline-none focus:ring-2 focus:ring-udl-green/30 group" aria-expanded="false" aria-haspopup="true">
                    <span class="text-sm font-bold text-[#0f392b] group-hover:text-udl-green transition">
                        Prof. <?= htmlspecialchars($userName) ?>
                    </span>
                    <div class="w-9 h-9 rounded-full bg-emerald-50 border border-emerald-300/80 flex items-center justify-center text-udl-green font-bold text-sm shadow-sm group-hover:border-udl-green transition">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <i id="userMenuChevron" class="fa-solid fa-chevron-down text-xs text-slate-400 group-hover:text-udl-green transition-transform duration-200"></i>
                </button>

                <!-- Dropdown Card -->
                <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-60 rounded-2xl bg-white shadow-2xl border border-slate-200 py-2 z-50 transform origin-top-right transition-all animate-fade-in">
                    <div class="px-4 py-2.5 border-b border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sesión Activa</p>
                        <p class="text-xs font-bold text-[#0f392b] truncate">Prof. <?= htmlspecialchars($userName) ?></p>
                        <?php if (!empty($userEmail)): ?>
                            <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($userEmail) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="py-1">
                        <a href="<?= htmlspecialchars(url('cambiar-password')) ?>" class="flex items-center space-x-3 px-4 py-2.5 text-xs font-bold text-slate-700 hover:text-udl-green hover:bg-emerald-50/80 transition">
                            <i class="fa-solid fa-key text-udl-green text-sm w-4 text-center"></i>
                            <span>Cambiar Contraseña</span>
                        </a>
                    </div>

                    <div class="border-t border-slate-100 py-1">
                        <a href="<?= htmlspecialchars(url('logout')) ?>" class="flex items-center space-x-3 px-4 py-2.5 text-xs font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition">
                            <i class="fa-solid fa-right-from-bracket text-rose-500 text-sm w-4 text-center"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages Banner -->
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        ?>

        <?php if (!empty($flashSuccess)): ?>
            <div class="mx-6 lg:mx-8 mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex items-center justify-between shadow-sm animate-fade-in" role="alert">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-check text-udl-green text-lg"></i>
                    <span class="font-medium"><?= htmlspecialchars((string)$flashSuccess) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="mx-6 lg:mx-8 mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm flex items-center justify-between shadow-sm animate-fade-in" role="alert">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                    <span class="font-medium"><?= htmlspecialchars((string)$flashError) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Primary Scrollable Main Container -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f4f7f6]">
