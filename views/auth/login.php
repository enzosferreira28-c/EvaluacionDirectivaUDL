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
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?= htmlspecialchars(asset('img/16x16.png')) ?>" type="image/x-icon">
    <title>Universidad de León - Panel de Evaluación Directiva</title>
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
                            border: '#e2e8f0'
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
<body class="min-h-full flex flex-col justify-between relative overflow-x-hidden udl-login-bg font-sans">

    <!-- Top Space -->
    <div class="h-6"></div>

    <!-- Center Login Card Container -->
    <div class="w-full max-w-md mx-auto px-4 py-8 z-10 flex-1 flex items-center justify-center">

        <div class="w-full bg-white rounded-xl shadow-2xl border border-slate-100 p-8 sm:p-10 text-slate-800">
            
            <!-- Logo & Institutional Header -->
            <div class="text-center mb-6">
                <img src="<?= htmlspecialchars(asset('img/UDL_LogotipoColor.png')) ?>" alt="Universidad de León" class="h-20 w-auto mx-auto mb-4 object-contain">
                <h1 class="text-xl font-extrabold tracking-wide text-[#007547] uppercase leading-tight">
                    PANEL DE EVALUACIÓN DIRECTIVA
                </h1>
                <p class="text-xs font-medium text-slate-500 mt-1 uppercase tracking-wider">
                    Control y Análisis Estadístico
                </p>
            </div>

            <!-- Flash Error / Validation Error -->
            <?php if (!empty($errorMessage)): ?>
                <div class="mb-5 p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start space-x-2.5" role="alert">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base mt-0.5 flex-shrink-0"></i>
                    <div>
                        <span class="font-bold block">Error al ingresar:</span>
                        <span><?= htmlspecialchars((string)$errorMessage) ?></span>
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
                <div class="mb-5 p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center space-x-2.5" role="alert">
                    <i class="fa-solid fa-circle-check text-[#007547] text-base flex-shrink-0"></i>
                    <span class="font-medium"><?= htmlspecialchars((string)$flashSuccess) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= htmlspecialchars(url('login')) ?>" method="POST" class="space-y-4" autocomplete="on">
                <?= \App\Core\Security::csrfField() ?>

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                        Correo Institucional
                    </label>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#007547]">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               autocomplete="email"
                               value="<?= htmlspecialchars((string)$previousEmail) ?>"
                               placeholder="profesor@universidaddeleon.edu.mx" 
                               class="block w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#007547] focus:border-[#007547] text-sm transition">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">
                        Contraseña
                    </label>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#007547]">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               required 
                               autocomplete="current-password"
                               placeholder="••••••••" 
                               class="block w-full pl-10 pr-10 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#007547] focus:border-[#007547] text-sm transition">
                        <button type="button" 
                                id="togglePasswordBtn" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                aria-label="Mostrar u ocultar contraseña">
                            <i class="fa-solid fa-eye text-sm" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button (Exacto a Imagen 3 de referencia con Hover Llamativo) -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-lg text-white bg-[#007547] hover:bg-[#0f392b] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#007547] font-bold uppercase text-sm tracking-wider shadow-md hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200">
                        <i class="fa-solid fa-right-to-bracket text-base"></i>
                        <span>INGRESAR</span>
                    </button>
                </div>
            </form>

            
            <div class="mt-8 text-center space-y-1">
                <p class="text-xs text-slate-500">Sitio Exclusivo para Personal</p>
                <!--<a href="mailto:soporte@universidaddeleon.edu.mx" class="inline-block text-xs font-bold text-[#007547] hover:text-[#0f392b] uppercase tracking-wide hover:underline transition">
                    CONTACTAR A SOPORTE
                </a>-->
            </div>

        </div>

    </div>

    <!-- Institutional Bottom Green Bar (Idéntico a Imagen 3) -->
    <footer class="w-full bg-[#007547] text-white py-3.5 px-6 sm:px-12 text-xs flex flex-col sm:flex-row items-center justify-between z-20 shadow-inner">
        <p class="font-medium text-slate-100 text-center sm:text-left">
            &copy; 2026 Universidad de León. Todos los derechos reservados.
        </p>
        <p class="font-semibold text-emerald-100 text-center sm:text-right mt-1 sm:mt-0">
            Dirección de Investigación
        </p>
    </footer>

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