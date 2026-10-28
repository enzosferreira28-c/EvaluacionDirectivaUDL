<?php
declare(strict_types=1);

$pageTitle = 'Dashboard - Universidad de León';
require APP_ROOT . '/views/layouts/header.php';

$periodosCount = (int)($data['periodos_count'] ?? 0);
$directoresCount = (int)($data['directores_count'] ?? 0);
$ultimoPeriodo = (string)($data['ultimo_periodo'] ?? 'N/A');
$userName = $currentUser['nombre'] ?? 'Administrador';
?>

<div class="space-y-8 max-w-7xl mx-auto">

    <!-- Welcome Hero Banner (Estilo Exacto Imagen 1 de Referencia) -->
    <div class="relative overflow-hidden rounded-xl bg-[#007547] text-white p-7 sm:p-9 shadow-md flex items-center justify-between">
        <div class="relative z-10 max-w-2xl">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                ¡Bienvenido de nuevo, <?= htmlspecialchars($userName) ?>!
            </h1>
            <p class="mt-2 text-emerald-100 text-sm sm:text-base leading-relaxed font-normal">
                Sistema de análisis estadístico y gestión de evaluaciones docentes y directivas
            </p>
        </div>
        <!-- Decorative subtle watermark -->
        <div class="hidden lg:block opacity-15 pr-6">
            <i class="fa-solid fa-graduation-cap text-8xl text-white"></i>
        </div>
    </div>

    <!-- 3 Action Cards (Estilo Exacto Imagen 1 con Hovers Llamativos) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Card 1: Importar Excel (Azul) -->
        <div class="bg-white rounded-xl p-7 border border-slate-200/90 shadow-sm flex flex-col justify-between hover-lift transition-all duration-300 text-center group">
            <div>
                <!-- Soft Icon -->
                <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-5 shadow-sm group-hover:scale-110 group-hover:bg-blue-100 transition-transform duration-300">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-800 mb-2 group-hover:text-blue-600 transition-colors">
                    Cargar Evaluación (Excel)
                </h2>
                <p class="text-xs text-slate-500 leading-relaxed min-h-[40px]">
                    Sube archivos Excel con respuestas crudas para cálculo automatizado de promedios y desviaciones.
                </p>
            </div>
            <div class="mt-6">
                <a href="<?= htmlspecialchars(url('import')) ?>" 
                   class="w-full inline-flex items-center justify-center space-x-2 py-3 px-4 rounded-lg text-white bg-[#0066ff] hover:bg-[#0052cc] font-bold text-sm shadow-md hover:shadow-blue-500/30 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200">
                    <span>Comenzar</span>
                    <i class="fa-solid fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>

        <!-- Card 2: Gestionar Catálogos (Amarillo/Dorado) -->
        <div class="bg-white rounded-xl p-7 border border-slate-200/90 shadow-sm flex flex-col justify-between hover-lift transition-all duration-300 text-center group">
            <div>
                <!-- Soft Icon -->
                <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl mb-5 shadow-sm group-hover:scale-110 group-hover:bg-amber-100 transition-transform duration-300">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-800 mb-2 group-hover:text-amber-600 transition-colors">
                    Gestionar Catálogos
                </h2>
                <p class="text-xs text-slate-500 leading-relaxed min-h-[40px]">
                    Administra directores de carrera, licenciaturas, nomenclaturas oficiales y ciclos académicos.
                </p>
            </div>
            <div class="mt-6">
                <a href="<?= htmlspecialchars(url('catalogos')) ?>" 
                   class="w-full inline-flex items-center justify-center space-x-2 py-3 px-4 rounded-lg text-white bg-[#e5a000] hover:bg-[#cc8f00] font-bold text-sm shadow-md hover:shadow-amber-500/30 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200">
                    <span>Ir a Gestión</span>
                    <i class="fa-solid fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>

        <!-- Card 3: Resultados de Evaluación (Turquesa / Celeste) -->
        <div class="bg-white rounded-xl p-7 border border-slate-200/90 shadow-sm flex flex-col justify-between hover-lift transition-all duration-300 text-center group">
            <div>
                <!-- Soft Icon -->
                <div class="w-16 h-16 mx-auto rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-2xl mb-5 shadow-sm group-hover:scale-110 group-hover:bg-cyan-100 transition-transform duration-300">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-800 mb-2 group-hover:text-cyan-600 transition-colors">
                    Resultados y Rankings
                </h2>
                <p class="text-xs text-slate-500 leading-relaxed min-h-[40px]">
                    Consulta estadísticas institucionales, jerarquización, filtros por plantel y comparativas históricas.
                </p>
            </div>
            <div class="mt-6">
                <a href="<?= htmlspecialchars(url('reportes')) ?>" 
                   class="w-full inline-flex items-center justify-center space-x-2 py-3 px-4 rounded-lg text-white bg-[#00b4d8] hover:bg-[#0096c7] font-bold text-sm shadow-md hover:shadow-cyan-500/30 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200">
                    <span>Ver Reportes</span>
                    <i class="fa-solid fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- Summary Metrics Section -->
    <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#0f392b] mb-5 flex items-center space-x-2">
            <i class="fa-solid fa-chart-simple text-udl-green"></i>
            <span>Métricas del Ciclo Actual</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            
            <div class="p-4 rounded-xl bg-[#f4f7f6] border border-slate-200/80 flex items-center justify-between hover-lift-sm transition">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Periodos Activos</p>
                    <p class="text-3xl font-extrabold text-[#0f392b] mt-1"><?= $periodosCount ?></p>
                    <a href="<?= htmlspecialchars(url('periodos')) ?>" class="text-xs font-bold text-udl-green hover:underline mt-1 inline-block">
                        Ver ciclos &rarr;
                    </a>
                </div>
                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 text-udl-green flex items-center justify-center text-xl shadow-sm">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-[#f4f7f6] border border-slate-200/80 flex items-center justify-between hover-lift-sm transition">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Directores en Catálogo</p>
                    <p class="text-3xl font-extrabold text-[#0f392b] mt-1"><?= $directoresCount ?></p>
                    <a href="<?= htmlspecialchars(url('catalogos')) ?>" class="text-xs font-bold text-udl-green hover:underline mt-1 inline-block">
                        Ver directores &rarr;
                    </a>
                </div>
                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 text-udl-green flex items-center justify-center text-xl shadow-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-[#f4f7f6] border border-slate-200/80 flex items-center justify-between hover-lift-sm transition">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Último Periodo Registrado</p>
                    <p class="text-2xl font-extrabold text-[#0f392b] mt-1 truncate" title="<?= htmlspecialchars($ultimoPeriodo) ?>">
                        <?= htmlspecialchars($ultimoPeriodo) ?>
                    </p>
                    <a href="<?= htmlspecialchars(url('reportes')) ?>" class="text-xs font-bold text-udl-green hover:underline mt-1 inline-block">
                        Explorar estadísticas &rarr;
                    </a>
                </div>
                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 text-udl-green flex items-center justify-center text-xl shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>

        </div>
    </div>

</div>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>