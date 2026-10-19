<?php
declare(strict_types=1);

$pageTitle = 'Dashboard - Evaluación Directiva';
require APP_ROOT . '/views/layouts/header.php';

$periodosCount = (int)($data['periodos_count'] ?? 0);
$directoresCount = (int)($data['directores_count'] ?? 0);
$ultimoPeriodo = (string)($data['ultimo_periodo'] ?? 'N/A');
$userName = $currentUser['nombre'] ?? 'Usuario';
?>

<div class="space-y-6">

    <!-- Welcome Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white p-6 sm:p-8 shadow-xl">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-200 text-xs font-semibold mb-3 border border-indigo-400/30">
                <i class="fa-solid fa-sparkles"></i>
                <span>Panel de Control Institucional</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                ¡Bienvenido(a), <?= htmlspecialchars($userName) ?>!
            </h1>
            <p class="mt-2 text-indigo-100 text-sm sm:text-base leading-relaxed">
                Sistema automatizado para el análisis estadístico, jerarquización y reportes de la evaluación docente y directiva cuatrimestral.
            </p>
        </div>
        <!-- Decorative abstract element -->
        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <!-- Total Periodos -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Periodos</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2"><?= $periodosCount ?></p>
                    <p class="text-xs text-slate-500 mt-1">Ciclos académicos registrados</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <a href="<?= htmlspecialchars(url('periodos')) ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center space-x-1">
                    <span>Gestionar periodos</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- Directores Activos -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Directores Activos</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2"><?= $directoresCount ?></p>
                    <p class="text-xs text-slate-500 mt-1">En catálogo oficial</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <a href="<?= htmlspecialchars(url('catalogos')) ?>" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 flex items-center space-x-1">
                    <span>Ver catálogo de directores</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- Último Periodo -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Último Periodo</p>
                    <p class="text-2xl font-bold text-slate-800 mt-2 truncate" title="<?= htmlspecialchars($ultimoPeriodo) ?>">
                        <?= htmlspecialchars($ultimoPeriodo) ?>
                    </p>
                    <p class="text-xs text-slate-500 mt-1">Periodo más reciente</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <a href="<?= htmlspecialchars(url('reportes')) ?>" class="text-xs font-semibold text-amber-600 hover:text-amber-800 flex items-center space-x-1">
                    <span>Ver resultados del periodo</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- Quick Action Buttons -->
    <div>
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center space-x-2">
            <i class="fa-solid fa-bolt text-indigo-600"></i>
            <span>Acciones Rápidas</span>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            
            <!-- Action 1: Importar Excel -->
            <a href="<?= htmlspecialchars(url('import')) ?>" 
               class="group bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-indigo-300 hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl shadow-md shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mt-4 group-hover:text-indigo-600 transition-colors">
                        Importar Archivo Excel
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Carga el archivo de respuestas crudas de las encuestas estudiantiles para procesar y calcular los resultados.
                    </p>
                </div>
                <div class="mt-4 pt-3 flex items-center text-xs font-semibold text-indigo-600">
                    <span>Comenzar importación</span>
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            <!-- Action 2: Ver Resultados -->
            <a href="<?= htmlspecialchars(url('reportes')) ?>" 
               class="group bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-indigo-300 hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xl shadow-md shadow-indigo-600/20 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mt-4 group-hover:text-indigo-600 transition-colors">
                        Consultar Resultados
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Analiza los promedios globales, desviaciones estándar, rankings y desglose de preguntas con filtros por plantel y carrera.
                    </p>
                </div>
                <div class="mt-4 pt-3 flex items-center text-xs font-semibold text-indigo-600">
                    <span>Ver reportes</span>
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            <!-- Action 3: Gestionar Catálogos -->
            <a href="<?= htmlspecialchars(url('catalogos')) ?>" 
               class="group bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-indigo-300 hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xl shadow-md shadow-purple-600/20 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-folder-tree"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mt-4 group-hover:text-indigo-600 transition-colors">
                        Gestionar Catálogos
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Administra directores de carrera, asignaciones por plantel, licenciaturas y regiones para garantizar la consistencia de los datos.
                    </p>
                </div>
                <div class="mt-4 pt-3 flex items-center text-xs font-semibold text-indigo-600">
                    <span>Ir a catálogos</span>
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

        </div>
    </div>

    <!-- System Workflow Guide -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4 flex items-center space-x-2">
            <i class="fa-solid fa-circle-nodes text-indigo-500"></i>
            <span>Flujo Operativo de Evaluación Institucional</span>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-start space-x-3">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">
                    1
                </span>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Verificar Catálogos y Periodo</h4>
                    <p class="text-xs text-slate-500 mt-1">
                        Crea el ciclo académico correspondiente (ej. 2027-1) y asegúrate de que los directores y carreras estén al día.
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-start space-x-3">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">
                    2
                </span>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Cargar Archivo Excel</h4>
                    <p class="text-xs text-slate-500 mt-1">
                        Sube las encuestas crudas. El sistema normaliza turnos, vincula directores y computa las fórmulas estadísticas.
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-start space-x-3">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">
                    3
                </span>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Consultar y Comparar</h4>
                    <p class="text-xs text-slate-500 mt-1">
                        Revisa la jerarquización, aplica filtros por plantel o región, y contrasta los resultados con periodos anteriores.
                    </p>
                </div>
            </div>

        </div>
    </div>

</div>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
