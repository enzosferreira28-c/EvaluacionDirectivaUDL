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

$errorMessage = $data['error'] ?? null;
$previousEmail = $data['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Evaluación Directiva - Iniciar Sesión</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/app.css')) ?>">
</head>
<body class="h-full flex items-center justify-center p-4 relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 font-sans text-slate-200">

    <!-- Decorative background elements -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">

        <!-- Card Container -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-100/20 p-8 sm:p-10 text-slate-800">
            
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600 text-white text-3xl shadow-lg shadow-indigo-600/30 mb-4">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Panel de Evaluación Directiva</h1>
                <p class="text-sm text-slate-500 mt-1">Acceso seguro para personal autorizado</p>
            </div>

            <!-- Flash Error / Validation Error -->
            <?php if (!empty($errorMessage)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start space-x-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-lg mt-0.5 flex-shrink-0"></i>
                    <div>
                        <span class="font-semibold block">Error de autenticación</span>
                        <span class="text-rose-700"><?= htmlspecialchars((string)$errorMessage) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Flash Success (e.g. after logout) -->
            <?php
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $flashSuccess = $_SESSION['flash_success'] ?? null;
            unset($_SESSION['flash_success']);
            if (!empty($flashSuccess)):
            ?>
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start space-x-3" role="alert">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg mt-0.5 flex-shrink-0"></i>
                    <div>
                        <span class="text-emerald-700"><?= htmlspecialchars((string)$flashSuccess) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= htmlspecialchars(url('login')) ?>" method="POST" class="space-y-5" autocomplete="on">
                <?= \App\Core\Security::csrfField() ?>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Correo Institucional
                    </label>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               autocomplete="email"
                               value="<?= htmlspecialchars((string)$previousEmail) ?>"
                               placeholder="coordinacion@universidad.edu.mx" 
                               class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Contraseña
                    </label>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               required 
                               autocomplete="current-password"
                               placeholder="••••••••" 
                               class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition">
                        <button type="button" 
                                id="togglePasswordBtn" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                aria-label="Mostrar u ocultar contraseña">
                            <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-semibold shadow-lg shadow-indigo-600/30 transition-all hover:shadow-indigo-600/40">
                        <span>Iniciar Sesión</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-200/80 text-center">
                <p class="text-xs text-slate-500 flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-shield-halved text-indigo-500"></i>
                    <span>Conexión protegida mediante cifrado seguro</span>
                </p>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            &copy; <?= date('Y') ?> Universidad &bull; Sistema de Análisis Estadístico
        </p>
    </div>

    <script>
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passInput = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passInput && icon) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = passInput.type === 'password';
                passInput.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        }
    </script>
</body>
</html>
