<?php
declare(strict_types=1);

$pageTitle = 'Resultados de Evaluación - Evaluación Directiva';
require APP_ROOT . '/views/layouts/header.php';

$periodos = $data['periodos'] ?? [];
$planteles = $data['planteles'] ?? [];
$licenciaturas = $data['licenciaturas'] ?? [];
$regiones = $data['regiones'] ?? [];
$resultados = $data['resultados'] ?? [];
$resumen = $data['resumen'] ?? null;
$filters = $data['filters'] ?? [];

$selectedPeriodo = (string)($filters['id_periodo'] ?? '');
$selectedPlantel = !empty($filters['id_plantel']) ? (int)$filters['id_plantel'] : null;
$selectedLic = !empty($filters['id_licenciatura']) ? (int)$filters['id_licenciatura'] : null;
$selectedRegion = !empty($filters['id_region']) ? (int)$filters['id_region'] : null;

$totalResultados = count($resultados);
?>

<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Resultados y Jerarquización de Evaluación</h1>
            <p class="text-sm text-slate-500 mt-1">Análisis estadístico multivariable y ranking general de directores</p>
        </div>
        <div class="mt-3 sm:mt-0 flex items-center space-x-2">
            <a href="<?= htmlspecialchars(url('import')) ?>" class="inline-flex items-center space-x-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-2 rounded-lg transition border border-emerald-200">
                <i class="fa-solid fa-file-excel"></i>
                <span>Importar Nuevas Encuestas</span>
            </a>
            <a href="<?= htmlspecialchars(url('comparativa')) ?>" class="inline-flex items-center space-x-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg transition border border-indigo-200">
                <i class="fa-solid fa-code-compare"></i>
                <span>Comparativa Histórica</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <form action="<?= htmlspecialchars(url('reportes')) ?>" method="GET" class="space-y-4">
            <input type="hidden" name="route" value="reportes">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Periodo Dropdown (Required) -->
                <div>
                    <label for="filter_periodo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Periodo de Evaluación <span class="text-rose-500">*</span>
                    </label>
                    <select name="id_periodo" id="filter_periodo" required class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Seleccionar Periodo --</option>
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= htmlspecialchars((string)$p['id_periodo']) ?>" <?= $selectedPeriodo === (string)$p['id_periodo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p['id_periodo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Región Dropdown -->
                <div>
                    <label for="filter_region" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Región
                    </label>
                    <select name="id_region" id="filter_region" class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todas las regiones</option>
                        <?php foreach ($regiones as $reg): ?>
                            <option value="<?= (int)$reg['id_region'] ?>" <?= $selectedRegion === (int)$reg['id_region'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$reg['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Plantel Dropdown -->
                <div>
                    <label for="filter_plantel" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Plantel
                    </label>
                    <select name="id_plantel" id="filter_plantel" class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todos los planteles</option>
                        <?php foreach ($planteles as $pl): ?>
                            <option value="<?= (int)$pl['id_plantel'] ?>" <?= $selectedPlantel === (int)$pl['id_plantel'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$pl['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Licenciatura Dropdown -->
                <div>
                    <label for="filter_licenciatura" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Licenciatura
                    </label>
                    <select name="id_licenciatura" id="filter_licenciatura" class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todas las licenciaturas</option>
                        <?php foreach ($licenciaturas as $lic): ?>
                            <option value="<?= (int)$lic['id_licenciatura'] ?>" <?= $selectedLic === (int)$lic['id_licenciatura'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$lic['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <a href="<?= htmlspecialchars(url('reportes')) ?>" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Limpiar Filtros
                </a>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm shadow-indigo-600/20 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <i class="fa-solid fa-filter mr-1"></i> Aplicar Filtros
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($selectedPeriodo)): ?>
        <!-- No Period Selected State -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Seleccione un periodo de evaluación</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                Seleccione un ciclo académico en el filtro superior para visualizar las estadísticas consolidadas.
            </p>
        </div>

    <?php elseif (empty($resultados)): ?>
        <!-- No Results Found State -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-2xl mx-auto mb-3">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">No se encontraron resultados para los filtros seleccionados</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                No existen registros procesados para el periodo <strong><?= htmlspecialchars($selectedPeriodo) ?></strong> con los criterios aplicados. Puede cargar el archivo Excel correspondiente desde el módulo de importación.
            </p>
            <div class="mt-4">
                <a href="<?= htmlspecialchars(url('import') . '&id_periodo=' . urlencode($selectedPeriodo)) ?>" class="inline-flex items-center space-x-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg transition">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Cargar Encuestas para este Periodo</span>
                </a>
            </div>
        </div>

    <?php else: ?>

        <!-- Summary Cards Section -->
        <?php
        $promedioGlobal = isset($resumen['promedio_general']) ? number_format((float)$resumen['promedio_general'], 2) : '0.00';
        $totalEncuestas = isset($resumen['total_encuestas']) ? number_format((int)$resumen['total_encuestas']) : '0';

        $mejorPuntuadoNombre = $resumen['mejor_puntuado']['nombre'] ?? ($resultados[0]['director_nombre'] ?? 'N/D');
        $mejorPuntuadoCal = isset($resumen['mejor_puntuado']['promedio']) ? number_format((float)$resumen['mejor_puntuado']['promedio'], 2) : ($resultados[0]['promedio_global'] ?? '0.00');

        $lastItem = end($resultados);
        $peorPuntuadoNombre = $resumen['peor_puntuado']['nombre'] ?? ($lastItem['director_nombre'] ?? 'N/D');
        $peorPuntuadoCal = isset($resumen['peor_puntuado']['promedio']) ? number_format((float)$resumen['peor_puntuado']['promedio'], 2) : ($lastItem['promedio_global'] ?? '0.00');
        ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Promedio Global General -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Promedio General</p>
                    <p class="text-3xl font-extrabold text-indigo-600 mt-1"><?= $promedioGlobal ?></p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Escala de 5.0 a 10.0</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-star-half-stroke"></i>
                </div>
            </div>

            <!-- Card 2: Total Encuestas -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Encuestas Analizadas</p>
                    <p class="text-3xl font-extrabold text-slate-800 mt-1"><?= $totalEncuestas ?></p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Respuestas procesadas</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
            </div>

            <!-- Card 3: Mejor Puntuado -->
            <div class="bg-emerald-50/70 rounded-2xl p-5 border border-emerald-200 shadow-sm flex items-center justify-between">
                <div class="min-w-0 pr-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Mejor Puntuado</p>
                    <p class="text-lg font-bold text-emerald-950 truncate mt-1" title="<?= htmlspecialchars((string)$mejorPuntuadoNombre) ?>">
                        <?= htmlspecialchars((string)$mejorPuntuadoNombre) ?>
                    </p>
                    <p class="text-xs font-extrabold text-emerald-600 mt-0.5">
                        <i class="fa-solid fa-trophy mr-1 text-amber-500"></i> Calificación: <?= $mejorPuntuadoCal ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>

            <!-- Card 4: Menor Calificación -->
            <div class="bg-rose-50/70 rounded-2xl p-5 border border-rose-200 shadow-sm flex items-center justify-between">
                <div class="min-w-0 pr-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-800">Menor Calificación</p>
                    <p class="text-lg font-bold text-rose-950 truncate mt-1" title="<?= htmlspecialchars((string)$peorPuntuadoNombre) ?>">
                        <?= htmlspecialchars((string)$peorPuntuadoNombre) ?>
                    </p>
                    <p class="text-xs font-extrabold text-rose-600 mt-0.5">
                        <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-500"></i> Calificación: <?= $peorPuntuadoCal ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                </div>
            </div>

        </div>

        <!-- Ranking Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                        <i class="fa-solid fa-ranking-star text-indigo-600"></i>
                        <span>Tabla de Resultados y Jerarquización (Ranking)</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Directores ordenados del más alto al más bajo puntaje global. Haga clic en los encabezados para ordenar.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span> Top 3
                    </span>
                    <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        <span class="w-2 h-2 rounded-full bg-rose-500 mr-1.5"></span> Menor Desempeño
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 sortable-table" id="resultadosTable">
                    <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-center cursor-pointer hover:bg-slate-100 transition select-none">#</th>
                            <th scope="col" class="px-4 py-3 cursor-pointer hover:bg-slate-100 transition select-none">Director</th>
                            <th scope="col" class="px-4 py-3 cursor-pointer hover:bg-slate-100 transition select-none">Plantel</th>
                            <th scope="col" class="px-4 py-3 cursor-pointer hover:bg-slate-100 transition select-none">Licenciatura</th>
                            <th scope="col" class="px-2 py-3 text-center cursor-pointer hover:bg-slate-100 transition select-none">Turno</th>
                            <th scope="col" class="px-3 py-3 text-center cursor-pointer hover:bg-slate-100 transition select-none">Encuestas</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P1</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P2</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P3</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P4</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P5</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P6</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P7</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P8</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P9</th>
                            <th scope="col" class="px-2 py-3 text-center font-mono">P10</th>
                            <th scope="col" class="px-3 py-3 text-center cursor-pointer hover:bg-slate-100 transition select-none bg-indigo-50/50 text-indigo-900 font-extrabold">Prom. Global</th>
                            <th scope="col" class="px-3 py-3 text-center cursor-pointer hover:bg-slate-100 transition select-none">Desv. Est.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-sans">
                        <?php foreach ($resultados as $idx => $r): 
                            $pos = (int)($r['posicion_ranking'] ?? ($idx + 1));
                            $directorName = (string)($r['director_nombre'] ?? $r['nombre'] ?? 'Sin asignar');
                            $plantelName = (string)($r['plantel_nombre'] ?? 'N/D');
                            $licName = (string)($r['licenciatura_nombre'] ?? 'N/D');
                            $turno = (string)($r['turno'] ?? 'T');
                            $totalEnc = (int)($r['total_encuestas'] ?? 0);
                            $promGlobal = number_format((float)($r['promedio_global'] ?? 0), 2);
                            $desvEst = number_format((float)($r['desviacion_estandar'] ?? 0), 2);

                            $isTop3 = $pos <= 3;
                            $isBottom3 = ($totalResultados > 5 && $pos > ($totalResultados - 3));

                            $rowClasses = 'transition-colors ';
                            if ($isTop3) {
                                $rowClasses .= 'bg-emerald-50/40 hover:bg-emerald-50/80 border-l-4 border-emerald-500';
                            } elseif ($isBottom3) {
                                $rowClasses .= 'bg-rose-50/40 hover:bg-rose-50/80 border-l-4 border-rose-500';
                            } else {
                                $rowClasses .= 'hover:bg-slate-50/80';
                            }
                        ?>
                            <tr class="<?= $rowClasses ?>">
                                <td class="px-3 py-3 text-center font-bold">
                                    <?php if ($pos === 1): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-400 text-amber-950 font-black text-xs shadow-sm">1</span>
                                    <?php elseif ($pos === 2): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-300 text-slate-800 font-bold text-xs shadow-sm">2</span>
                                    <?php elseif ($pos === 3): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-600/30 text-amber-900 font-bold text-xs shadow-sm">3</span>
                                    <?php else: ?>
                                        <span class="text-slate-500 font-mono"><?= $pos ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-800 whitespace-nowrap">
                                    <?= htmlspecialchars($directorName) ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    <?= htmlspecialchars($plantelName) ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600 font-medium whitespace-nowrap">
                                    <?= htmlspecialchars($licName) ?>
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($turno) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center font-mono font-semibold text-slate-700">
                                    <?= number_format($totalEnc) ?>
                                </td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p1_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p2_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p3_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p4_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p5_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p6_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p7_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p8_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p9_prom'] ?? 0), 1) ?></td>
                                <td class="px-2 py-3 text-center font-mono text-slate-500"><?= number_format((float)($r['p10_prom'] ?? 0), 1) ?></td>
                                <td class="px-3 py-3 text-center font-mono font-extrabold text-indigo-700 text-sm bg-indigo-50/30">
                                    <?= $promGlobal ?>
                                </td>
                                <td class="px-3 py-3 text-center font-mono text-xs text-slate-600">
                                    &plusmn;<?= $desvEst ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

    <!-- Bottom Institutional Notice: PDF Feature in Week 3 -->
    <div class="p-4 rounded-xl bg-slate-100 border border-slate-200/80 text-xs text-slate-600 flex items-center justify-between">
        <div class="flex items-center space-x-2.5">
            <i class="fa-solid fa-file-pdf text-rose-500 text-base"></i>
            <span><strong>Nota Institucional:</strong> La generación de reportes PDF oficiales estará disponible en la Semana 3.</span>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700">
            Semana 3 En Camino
        </span>
    </div>

</div>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
