<?php
declare(strict_types=1);
?>
        </main>
        <!-- Main Content Area Ends -->

        <!-- Institutional Footer Bar (Estilo UDL) -->
        <footer class="bg-white border-t border-slate-200 px-6 py-3.5 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between">
            <p class="font-medium text-slate-600">&copy; <?= date('Y') ?> Universidad de León. Todos los derechos reservados.</p>
            <p class="mt-1 sm:mt-0 font-semibold text-udl-green flex items-center space-x-1.5">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Universidad de León · Evaluación Institucional</span>
            </p>
        </footer>

    </div>
    <!-- Main Content Wrapper Ends -->

    <!-- Global Loading Overlay -->
    <div id="globalLoadingOverlay" class="fixed inset-0 bg-[#0f392b]/70 backdrop-blur-sm z-[100] hidden items-center justify-center flex-col text-white transition-opacity">
        <div class="w-14 h-14 border-4 border-[#a2cf6d] border-t-transparent rounded-full animate-spin mb-4"></div>
        <p id="globalLoadingText" class="text-sm font-bold tracking-wide">Cargando datos del sistema...</p>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="fixed bottom-5 right-5 z-[110] space-y-2 pointer-events-none"></div>

    <!-- Custom App JavaScript -->
    <script src="<?= htmlspecialchars(asset('js/app.js')) ?>"></script>
</body>
</html>