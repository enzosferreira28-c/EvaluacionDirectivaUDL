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

$pageTitle = $pageTitle ?? 'Evaluación Directiva - Panel Institucional';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
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
<body class="h-full font-sans antialiased text-slate-800 bg-slate-100 flex flex-col md:flex-row overflow-hidden">

    <!-- Mobile Header -->
    <header class="md:hidden bg-slate-900 text-white flex items-center justify-between px-4 py-3 border-b border-slate-800 z-30">
        <div class="flex items-center space-x-3">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-600 text-white font-bold text-lg shadow">
                <i class="fa-solid fa-graduation-cap"></i>
            </span>
            <span class="font-bold text-base tracking-tight">Evaluación Directiva</span>
        </div>
        <button id="mobileMenuBtn" type="button" class="text-slate-300 hover:text-white focus:outline-none p-2 rounded-md hover:bg-slate-800 transition" aria-label="Abrir menú">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </header>

    <!-- Mobile Sidebar Backdrop -->
    <div id="mobileBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

    <!-- Sidebar Navigation -->
    <aside id="mainSidebar" class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-200 flex flex-col transition-transform duration-300 transform -translate-x-full md:translate-x-0 shadow-2xl md:shadow-none">
        
        <!-- Sidebar Brand -->
        <div class="h-16 flex items-center justify-between px-5 bg-slate-950/60 border-b border-slate-800/80">
            <a href="<?= htmlspecialchars(url('dashboard')) ?>" class="flex items-center space-x-3 text-white group">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-600/30 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-graduation-cap text-lg"></i>
                </div>
                <div>
                    <span class="block text-sm font-bold tracking-tight text-white leading-tight">Evaluación</span>
                    <span class="block text-xs font-medium text-indigo-400 uppercase tracking-wider">Directiva</span>
                </div>
            </a>
            <button id="closeMobileMenuBtn" class="md:hidden text-slate-400 hover:text-white p-1 rounded">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- User Profile Card in Sidebar -->
        <div class="p-4 mx-3 my-3 rounded-xl bg-slate-800/60 border border-slate-700/50 flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-indigo-500/20 text-indigo-300 font-bold border border-indigo-400/30 flex items-center justify-center text-sm uppercase">
                <?= htmlspecialchars($userInitials) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-white truncate" title="<?= htmlspecialchars($userName) ?>">
                    <?= htmlspecialchars($userName) ?>
                </p>
                <p class="text-[11px] text-slate-400 truncate" title="<?= htmlspecialchars($userEmail) ?>">
                    <?= htmlspecialchars($userEmail) ?>
                </p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto text-sm font-medium">
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
                    'active'=> in_array($currentRoute, ['reportes', 'resultados', 'reportes/index'], true)
                ],
            ];

            foreach ($navLinks as $item):
                $activeClasses = $item['active']
                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-semibold'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white';
            ?>
                <a href="<?= htmlspecialchars(url($item['route'])) ?>" 
                   class="flex items-center space-x-3 px-3.5 py-2.5 rounded-lg transition-colors <?= $activeClasses ?>">
                    <span class="w-5 text-center text-sm <?= $item['active'] ? 'text-white' : 'text-slate-400' ?>">
                        <i class="fa-solid <?= $item['icon'] ?>"></i>
                    </span>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Sidebar Footer / Logout -->
        <div class="p-3 border-t border-slate-800 bg-slate-950/40">
            <a href="<?= htmlspecialchars(url('logout')) ?>" 
               class="flex items-center space-x-3 px-3.5 py-2.5 rounded-lg text-rose-300 hover:bg-rose-500/10 hover:text-rose-200 text-sm font-medium transition-colors">
                <span class="w-5 text-center text-rose-400">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        
        <!-- Desktop Header Bar -->
        <header class="hidden md:flex items-center justify-between h-16 px-6 lg:px-8 bg-white border-b border-slate-200/80 shadow-sm z-10">
            <div class="flex items-center space-x-2 text-sm text-slate-500">
                <span class="font-medium text-slate-400">Sistema Institucional</span>
                <span class="text-slate-300">/</span>
                <span class="font-semibold text-slate-800"><?= htmlspecialchars($pageTitle) ?></span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Sesión Activa
                </span>
                <div class="text-xs text-slate-400 font-medium border-l border-slate-200 pl-4">
                    <?= date('d/m/Y') ?>
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

        <?php if ($flashSuccess || $flashError): ?>
            <div class="px-4 sm:px-6 lg:px-8 pt-4">
                <?php if ($flashSuccess): ?>
                    <div class="flex items-center justify-between p-4 mb-2 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm alert-banner" role="alert">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                            <div>
                                <strong class="font-semibold">Operación exitosa:</strong>
                                <span><?= htmlspecialchars($flashSuccess) ?></span>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('.alert-banner').remove()" class="text-emerald-500 hover:text-emerald-700 p-1" aria-label="Cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="flex items-center justify-between p-4 mb-2 text-sm text-rose-800 rounded-xl bg-rose-50 border border-rose-200 shadow-sm alert-banner" role="alert">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg"></i>
                            <div>
                                <strong class="font-semibold">Atención:</strong>
                                <span><?= htmlspecialchars($flashError) ?></span>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('.alert-banner').remove()" class="text-rose-500 hover:text-rose-700 p-1" aria-label="Cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Main Content Area Starts -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 focus:outline-none">
