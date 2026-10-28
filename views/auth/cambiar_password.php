<?php
declare(strict_types=1);

$pageTitle = 'Cambiar Contraseña - Universidad de León';
require APP_ROOT . '/views/layouts/header.php';

$user = $data['user'] ?? \App\Core\Security::getCurrentUser();
$userName = $user['nombre'] ?? 'Administrador';
$userEmail = $user['email'] ?? '';
?>

<div class="max-w-2xl mx-auto space-y-6">

    <!-- Header Section (Estilo UDL) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#0f392b] flex items-center space-x-2.5">
                <i class="fa-solid fa-shield-halved text-udl-green"></i>
                <span>Seguridad de la Cuenta</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Actualice periódicamente su contraseña para mantener protegida su cuenta administrativa</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <a href="<?= htmlspecialchars(url('dashboard')) ?>" class="inline-flex items-center space-x-2 text-xs font-bold text-slate-600 hover:text-udl-dark bg-white hover:bg-slate-50 border border-slate-300 px-3.5 py-2 rounded-lg transition shadow-sm">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver al Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Password Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-md p-6 sm:p-8 transition-all hover:shadow-lg">
        
        <!-- User identity header -->
        <div class="flex items-center space-x-4 p-4 rounded-xl bg-emerald-50/60 border border-emerald-200/80 mb-6">
            <div class="w-12 h-12 rounded-full bg-[#007547] text-white flex items-center justify-center text-lg font-bold shadow-sm flex-shrink-0">
                <i class="fa-solid fa-user-lock"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-udl-green">Usuario Activo</p>
                <p class="text-base font-extrabold text-[#0f392b] truncate">Prof. <?= htmlspecialchars((string)$userName) ?></p>
                <p class="text-xs text-slate-500 font-medium truncate"><?= htmlspecialchars((string)$userEmail) ?></p>
            </div>
        </div>

        <form action="<?= htmlspecialchars(url('cambiar-password')) ?>" method="POST" class="space-y-5" id="changePasswordForm">
            <?= \App\Core\Security::csrfField() ?>

            <!-- Contraseña Actual -->
            <div>
                <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Contraseña Actual <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </div>
                    <input type="password" 
                           name="current_password" 
                           id="current_password" 
                           required 
                           placeholder="Ingresa tu contraseña actual" 
                           class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-udl-green focus:border-udl-green transition">
                    <button type="button" 
                            onclick="togglePasswordVisibility('current_password', 'current_toggle_icon')" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-udl-green transition">
                        <i id="current_toggle_icon" class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Separator -->
            <div class="pt-2 border-t border-slate-100"></div>

            <!-- Nueva Contraseña -->
            <div>
                <label for="new_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Nueva Contraseña <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-key text-sm"></i>
                    </div>
                    <input type="password" 
                           name="new_password" 
                           id="new_password" 
                           required 
                           minlength="6"
                           placeholder="Mínimo 6 caracteres" 
                           class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-udl-green focus:border-udl-green transition">
                    <button type="button" 
                            onclick="togglePasswordVisibility('new_password', 'new_toggle_icon')" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-udl-green transition">
                        <i id="new_toggle_icon" class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Recomendación: use combinación de letras, números y símbolos.</p>
            </div>

            <!-- Confirmar Nueva Contraseña -->
            <div>
                <label for="confirm_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Confirmar Nueva Contraseña <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                    </div>
                    <input type="password" 
                           name="confirm_password" 
                           id="confirm_password" 
                           required 
                           minlength="6"
                           placeholder="Repita la nueva contraseña" 
                           class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-udl-green focus:border-udl-green transition">
                    <button type="button" 
                            onclick="togglePasswordVisibility('confirm_password', 'confirm_toggle_icon')" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-udl-green transition">
                        <i id="confirm_toggle_icon" class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                <a href="<?= htmlspecialchars(url('dashboard')) ?>" class="w-full sm:w-auto text-center px-5 py-2.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition shadow-sm">
                    Cancelar
                </a>
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-2.5 rounded-lg text-white bg-[#007547] hover:bg-[#0f392b] font-bold text-sm shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition duration-200">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Actualizar Contraseña</span>
                </button>
            </div>
        </form>

    </div>

</div>

<script>
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
