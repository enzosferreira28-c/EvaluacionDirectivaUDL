<?php
declare(strict_types=1);

$pageTitle = 'Periodos de Evaluación - Universidad de León';
require APP_ROOT . '/views/layouts/header.php';

$periodos = $data['periodos'] ?? [];
?>

<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Header Section (Estilo UDL) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#0f392b] flex items-center space-x-2.5">
                <i class="fa-solid fa-calendar-days text-udl-green"></i>
                <span>Periodos de Evaluación</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Cree y administre los ciclos cuatrimestrales de evaluación institucional</p>
        </div>
        <div class="mt-3 sm:mt-0 flex flex-wrap items-center gap-2">
            <a href="<?= htmlspecialchars(url('import')) ?>" class="inline-flex items-center space-x-2 text-xs font-bold text-udl-green bg-emerald-50 hover:bg-emerald-100 hover:text-udl-dark border border-emerald-200 px-3.5 py-2 rounded-lg transition-all hover:shadow-sm hover:-translate-y-0.5">
                <i class="fa-solid fa-file-excel"></i>
                <span>Importar Encuestas</span>
            </a>
            <a href="<?= htmlspecialchars(url('comparativa')) ?>" class="inline-flex items-center space-x-2 text-xs font-bold text-[#0f392b] bg-slate-100 hover:bg-slate-200 border border-slate-300 px-3.5 py-2 rounded-lg transition-all hover:shadow-sm hover:-translate-y-0.5">
                <i class="fa-solid fa-code-compare text-udl-green"></i>
                <span>Comparar Periodos</span>
            </a>
        </div>
    </div>

    <!-- Create Period Form Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm p-6 transition-all hover:shadow-md">
        <h2 class="text-xs font-bold uppercase tracking-wider text-[#0f392b] mb-4 flex items-center space-x-2">
            <i class="fa-solid fa-calendar-plus text-udl-green"></i>
            <span>Registrar Nuevo Ciclo Académico</span>
        </h2>
        
        <form action="<?= htmlspecialchars(url('periodos/crear')) ?>" method="POST" class="flex flex-col sm:flex-row sm:items-start gap-4">
            <?= \App\Core\Security::csrfField() ?>

            <div class="flex-1 max-w-sm">
                <label for="id_periodo" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Identificador del Periodo <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-hashtag text-udl-green"></i>
                    </div>
                    <input type="text" 
                           name="id_periodo" 
                           id="id_periodo" 
                           required 
                           placeholder="ej. 2027-1" 
                           class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-udl-green focus:border-udl-green uppercase transition">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Formato sugerido: AAAA-C (ej: 2026-3, 2027-1). Al crearse, se establecerá automáticamente como el ciclo ACTIVO.</p>
            </div>

            <div class="sm:pt-[24px]">
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-2.5 rounded-lg text-white bg-[#007547] hover:bg-[#0f392b] font-bold text-sm shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition duration-200 whitespace-nowrap">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Crear y Activar Periodo</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-[#0f392b] flex items-center space-x-2">
                <i class="fa-solid fa-list-check text-udl-green"></i>
                <span>Listado de Periodos Registrados</span>
            </h2>
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-50 text-udl-green border border-emerald-200">
                    Total: <?= count($periodos) ?> periodo(s)
                </span>
            </div>
        </div>

        <?php if (empty($periodos)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner">
                    <i class="fa-regular fa-calendar-xmark"></i>
                </div>
                <h3 class="text-base font-bold text-slate-700">No hay periodos registrados</h3>
                <p class="text-xs text-slate-500 mt-1">Utilice el formulario superior para registrar el primer ciclo de evaluación.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 sortable-table">
                    <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">ID Periodo</th>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">Estatus</th>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">Fecha de Creación</th>
                            <th scope="col" class="px-6 py-3.5 text-center cursor-pointer"># Respuestas</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($periodos as $p): 
                            $idPeriodo = (string)($p['id_periodo'] ?? '');
                            $estatus = strtoupper((string)($p['estatus'] ?? 'CERRADO'));
                            $respuestasCount = (int)($p['respuestas_count'] ?? 0);
                            $creadoEn = !empty($p['creado_en']) ? date('d/m/Y H:i', strtotime((string)$p['creado_en'])) : 'N/D';
                            $isActivo = ($estatus === 'ACTIVO');
                        ?>
                            <tr class="hover:bg-emerald-50/30 transition-colors <?= $isActivo ? 'bg-emerald-50/40 font-semibold' : '' ?>">
                                <td class="px-6 py-4 font-bold text-slate-800">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-mono font-bold <?= $isActivo ? 'bg-emerald-100 text-udl-dark border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-200' ?> shadow-sm">
                                        <?= htmlspecialchars($idPeriodo) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($isActivo): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-udl-dark border border-emerald-300 shadow-sm">
                                            <span class="w-2 h-2 mr-1.5 rounded-full bg-udl-green animate-pulse"></span>
                                            Activo Oficial
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                            Cerrado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-mono">
                                    <?= htmlspecialchars($creadoEn) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($respuestasCount > 0): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-udl-green border border-emerald-200 shadow-sm">
                                            <i class="fa-solid fa-list-check mr-1.5 text-[10px]"></i>
                                            <?= number_format($respuestasCount) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Sin datos</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <!-- Botón para Establecer como Activo si no lo está -->
                                        <?php if (!$isActivo): ?>
                                            <form action="<?= htmlspecialchars(url('periodos/activar')) ?>" method="POST" class="inline">
                                                <?= \App\Core\Security::csrfField() ?>
                                                <input type="hidden" name="id_periodo" value="<?= htmlspecialchars($idPeriodo) ?>">
                                                <button type="submit" 
                                                        title="Establecer como el único periodo ACTIVO" 
                                                        class="p-2 rounded-lg text-slate-500 hover:text-udl-green hover:bg-emerald-50 hover:scale-110 active:scale-95 transition-all">
                                                    <i class="fa-solid fa-toggle-on text-sm"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Ver resultados -->
                                        <a href="<?= htmlspecialchars(url('reportes') . '&id_periodo=' . urlencode($idPeriodo)) ?>" 
                                           title="Ver Resultados" 
                                           class="p-2 rounded-lg text-slate-500 hover:text-udl-green hover:bg-emerald-50 hover:scale-110 active:scale-95 transition-all">
                                            <i class="fa-solid fa-chart-simple text-sm"></i>
                                        </a>

                                        <!-- Importar encuestas en este periodo (solo si no tiene respuestas aún) -->
                                        <?php if ($respuestasCount === 0): ?>
                                            <a href="<?= htmlspecialchars(url('import') . '&id_periodo=' . urlencode($idPeriodo)) ?>" 
                                               title="Cargar Archivo Excel" 
                                               class="p-2 rounded-lg text-slate-500 hover:text-udl-green hover:bg-emerald-50 hover:scale-110 active:scale-95 transition-all">
                                                <i class="fa-solid fa-file-excel text-sm"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Formulario de eliminación -->
                                        <form action="<?= htmlspecialchars(url('periodos/eliminar')) ?>" method="POST" class="inline" onsubmit="return confirmDelete('¿Está seguro de eliminar el periodo <?= htmlspecialchars($idPeriodo) ?>? ATENCIÓN: Se eliminarán todas las encuestas y resultados asociados a este periodo.')">
                                            <?= \App\Core\Security::csrfField() ?>
                                            <input type="hidden" name="id_periodo" value="<?= htmlspecialchars($idPeriodo) ?>">
                                            <button type="submit" 
                                                    title="Eliminar Periodo" 
                                                    class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 hover:scale-110 active:scale-95 transition-all">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
