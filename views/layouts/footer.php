<?php
declare(strict_types=1);
?>
        </main>
        <!-- Main Content Area Ends -->

        <!-- Institutional Footer Bar -->
        <footer class="bg-white border-t border-slate-200/80 px-6 py-3 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between">
            <p>&copy; <?= date('Y') ?> Sistema de Evaluación Directiva. Panel de Administración Interno.</p>
            <p class="mt-1 sm:mt-0 text-slate-400">Versión 1.0 &bull; Entorno Seguro</p>
        </footer>

    </div>
    <!-- Main Content Wrapper Ends -->

    <!-- Global Loading Overlay -->
    <div id="globalLoadingOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center flex-col text-white transition-opacity">
        <div class="w-14 h-14 border-4 border-indigo-400 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p id="globalLoadingText" class="text-sm font-semibold tracking-wide">Cargando datos...</p>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="fixed bottom-5 right-5 z-[110] space-y-2 pointer-events-none"></div>

    <!-- Custom App JavaScript -->
    <script src="<?= htmlspecialchars(asset('js/app.js')) ?>"></script>
</body>
</html>
