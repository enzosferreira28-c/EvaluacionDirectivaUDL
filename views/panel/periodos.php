<?php
declare(strict_types=1);

$pageTitle = 'Gestión de Periodos - Evaluación Directiva';
require APP_ROOT . '/views/layouts/header.php';

$periodos = $data['periodos'] ?? [];
?>

<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Periodos de Evaluación</h1>
            <p class="text-sm text-slate-500 mt-1">Cree y administre los ciclos cuatrimestrales de evaluación institucional</p>
        </div>
        <div class="mt-3 sm:mt-0 flex items-center space-x-2">
            <a href="<?= htmlspecialchars(url('import')) ?>" class="inline-flex items-center space-x-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-2 rounded-lg transition border border-emerald-200">
                <i class="fa-solid fa-file-excel"></i>
                <span>Importar Encuestas</span>
            </a>
            <a href="<?= htmlspecialchars(url('comparativa')) ?>" class="inline-flex items-center space-x-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg transition border border-indigo-200">
                <i class="fa-solid fa-code-compare"></i>
                <span>Comparar Periodos</span>
            </a>
        </div>
    </div>

    <!-- Create Period Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4 flex items-center space-x-2">
            <i class="fa-solid fa-calendar-plus text-indigo-600"></i>
            <span>Nuevo Ciclo Académico</span>
        </h2>
        
        <form action="<?= htmlspecialchars(url('periodos/crear')) ?>" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
            <?= \App\Core\Security::csrfField() ?>

            <div class="flex-1 max-w-sm">
                <label for="id_periodo" class="block text-xs font-semibold uppercase text-slate-600 mb-1.5">
                    Identificador del Periodo <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <input type="text" 
                           name="id_periodo" 
                           id="id_periodo" 
                           required 
                           placeholder="ej. 2027-1" 
                           class="block w-full pl-10 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Formato sugerido: AAAA-C (ej: 2026-3, 2027-1)</p>
            </div>

            <div>
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-5 py-2.5 rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 font-semibold text-sm shadow-md shadow-indigo-600/20 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Crear Periodo</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-list-check text-indigo-600"></i>
                <span>Listado de Periodos Registrados</span>
            </h2>
            <span class="text-xs text-slate-500 font-medium">Total: <?= count($periodos) ?> periodo(s)</span>
        </div>

        <?php if (empty($periodos)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                    <i class="fa-regular fa-calendar-xmark"></i>
                </div>
                <h3 class="text-base font-bold text-slate-700">No hay periodos registrados</h3>
                <p class="text-xs text-slate-500 mt-1">Utilice el formulario superior para registrar el primer ciclo de evaluación.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200/80 tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">ID Periodo</th>
                            <th scope="col" class="px-6 py-3.5">Estatus</th>
                            <th scope="col" class="px-6 py-3.5">Fecha de Creación</th>
                            <th scope="col" class="px-6 py-3.5 text-center"># Respuestas</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($periodos as $p): 
                            $idPeriodo = (string)($p['id_periodo'] ?? '');
                            $estatus = strtoupper((string)($p['estatus'] ?? 'ACTIVO'));
                            $respuestasCount = (int)($p['respuestas_count'] ?? 0);
                            $creadoEn = !empty($p['creado_en']) ? date('d/m/Y H:i', strtotime((string)$p['creado_en'])) : 'N/D';
                        ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-800">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= htmlspecialchars($idPeriodo) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($estatus === 'ACTIVO'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span>
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            Cerrado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= htmlspecialchars($creadoEn) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($respuestasCount > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            <i class="fa-solid fa-list-check mr-1.5 text-[10px]"></i>
                                            <?= number_format($respuestasCount) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Sin datos</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Ver resultados -->
                                        <a href="<?= htmlspecialchars(url('reportes') . '&id_periodo=' . urlencode($idPeriodo)) ?>" 
                                           title="Ver Resultados" 
                                           class="p-2 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                            <i class="fa-solid fa-chart-simple text-sm"></i>
                                        </a>

                                        <!-- Importar encuestas en este periodo -->
                                        <a href="<?= htmlspecialchars(url('import') . '&id_periodo=' . urlencode($idPeriodo)) ?>" 
                                           title="Cargar Archivo Excel" 
                                           class="p-2 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                            <i class="fa-solid fa-file-excel text-sm"></i>
                                        </a>

                                        <!-- Formulario de eliminación -->
                                        <form action="<?= htmlspecialchars(url('periodos/eliminar')) ?>" method="POST" class="inline" onsubmit="return confirmDelete('¿Está seguro de eliminar el periodo <?= htmlspecialchars($idPeriodo) ?>? ATENCIÓN: Se eliminarán todas las encuestas y resultados asociados a este periodo.')">
                                            <?= \App\Core\Security::csrfField() ?>
                                            <input type="hidden" name="id_periodo" value="<?= htmlspecialchars($idPeriodo) ?>">
                                            <button type="submit" 
                                                    title="Eliminar Periodo" 
                                                    class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
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
