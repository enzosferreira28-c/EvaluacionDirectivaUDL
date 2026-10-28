<?php
declare(strict_types=1);

$pageTitle = 'Catálogos Institucionales - Universidad de León';
require APP_ROOT . '/views/layouts/header.php';

$directores = $data['directores'] ?? [];
$planteles = $data['planteles'] ?? [];
$licenciaturas = $data['licenciaturas'] ?? [];
$regiones = $data['regiones'] ?? [];
?>

<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Header Section (Estilo UDL) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#0f392b] flex items-center space-x-2.5">
                <i class="fa-solid fa-folder-tree text-udl-green"></i>
                <span>Catálogos Institucionales</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Diccionario maestro de directores, planteles, licenciaturas y nomenclaturas oficiales</p>
        </div>
        <div class="mt-3 sm:mt-0 flex items-center space-x-2">
            <span class="text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-50 text-udl-green border border-emerald-200 shadow-sm">
                <i class="fa-solid fa-users mr-1.5"></i>
                <?= count($directores) ?> Directores Registrados
            </span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6 text-sm font-bold" aria-label="Tabs">
            <button type="button" id="tabBtnDirectores" onclick="switchCatalogTab('directores')" class="pb-3 border-b-2 border-udl-green text-udl-green flex items-center space-x-2 focus:outline-none transition">
                <i class="fa-solid fa-user-tie"></i>
                <span>Directores de Carrera</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-udl-dark"><?= count($directores) ?></span>
            </button>
            <button type="button" id="tabBtnPlanteles" onclick="switchCatalogTab('planteles')" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-udl-green hover:border-slate-300 flex items-center space-x-2 focus:outline-none transition">
                <i class="fa-solid fa-school"></i>
                <span>Planteles</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600"><?= count($planteles) ?></span>
            </button>
            <button type="button" id="tabBtnLicenciaturas" onclick="switchCatalogTab('licenciaturas')" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-udl-green hover:border-slate-300 flex items-center space-x-2 focus:outline-none transition">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Licenciaturas y Nomenclaturas</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600"><?= count($licenciaturas) ?></span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: DIRECTORES (CRUD) -->
    <div id="tabContentDirectores" class="space-y-6">

        <!-- Form Card (Crear / Editar) -->
        <div id="directorFormContainer" class="bg-white rounded-xl border border-slate-200/90 shadow-sm p-6 transition-all">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h2 id="formTitle" class="text-sm font-bold uppercase tracking-wider text-[#0f392b] flex items-center space-x-2">
                    <i class="fa-solid fa-user-plus text-udl-green"></i>
                    <span>Registrar Nuevo Director</span>
                </h2>
                <button type="button" id="btnCancelEdit" onclick="resetDirectorForm()" class="hidden text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition">
                    <i class="fa-solid fa-xmark mr-1"></i> Cancelar Edición
                </button>
            </div>

            <form id="directorForm" action="<?= htmlspecialchars(url('catalogos/guardarDirector')) ?>" method="POST" class="space-y-4">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id_director" id="form_id_director" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    
                    <!-- Nombre -->
                    <div>
                        <label for="form_nombre" class="block text-xs font-bold text-slate-700 mb-1">
                            Nombre(s) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nombre" id="form_nombre" required placeholder="ej. María Elena" class="block w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-udl-green focus:border-udl-green">
                    </div>

                    <!-- Apellidos -->
                    <div>
                        <label for="form_apellidos" class="block text-xs font-bold text-slate-700 mb-1">
                            Apellidos <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="apellidos" id="form_apellidos" required placeholder="ej. Gómez Ramírez" class="block w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-udl-green focus:border-udl-green">
                    </div>

                    <!-- Plantel -->
                    <div>
                        <label for="form_id_plantel" class="block text-xs font-bold text-slate-700 mb-1">
                            Plantel <span class="text-rose-500">*</span>
                        </label>
                        <select name="id_plantel" id="form_id_plantel" required class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-udl-green focus:border-udl-green">
                            <option value="">-- Seleccione Plantel --</option>
                            <?php foreach ($planteles as $pl): ?>
                                <option value="<?= (int)$pl['id_plantel'] ?>">
                                    <?= htmlspecialchars((string)$pl['nombre']) ?> (<?= htmlspecialchars((string)($pl['region_nombre'] ?? '')) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Licenciatura con Nomenclatura -->
                    <div>
                        <label for="form_id_licenciatura" class="block text-xs font-bold text-slate-700 mb-1">
                            Licenciatura / Carrera <span class="text-rose-500">*</span>
                        </label>
                        <select name="id_licenciatura" id="form_id_licenciatura" required class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-udl-green focus:border-udl-green">
                            <option value="">-- Seleccione Licenciatura --</option>
                            <?php foreach ($licenciaturas as $lic): ?>
                                <option value="<?= (int)$lic['id_licenciatura'] ?>">
                                    <?= htmlspecialchars((string)$lic['nombre']) ?> <?= !empty($lic['nomenclatura']) ? '[' . htmlspecialchars((string)$lic['nomenclatura']) . ']' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Turno (EXCLUSIVAMENTE T, V y M) -->
                    <div>
                        <label for="form_turno" class="block text-xs font-bold text-slate-700 mb-1">
                            Turno Asignado <span class="text-rose-500">*</span>
                        </label>
                        <select name="turno" id="form_turno" required class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-udl-green focus:border-udl-green font-medium">
                            <option value="T" selected>T - Todas</option>
                            <option value="V">V - Vespertino</option>
                            <option value="M">M - Matutino</option>
                        </select>
                    </div>

                    <!-- Estatus -->
                    <div>
                        <label for="form_estatus" class="block text-xs font-bold text-slate-700 mb-1">
                            Estatus
                        </label>
                        <select name="estatus" id="form_estatus" class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-udl-green focus:border-udl-green font-medium">
                            <option value="ACTIVO" selected>ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                        </select>
                    </div>

                </div>

                <div class="pt-2 flex items-center justify-end space-x-3">
                    <button type="submit" id="btnSubmitDirector" class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-lg text-white bg-[#007547] hover:bg-[#0f392b] font-bold text-sm shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition duration-200">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span id="btnSubmitText">Guardar Director</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Directors Table Card con Filtrado Avanzado Multi-criterio -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm overflow-hidden">
            
            <!-- Barra de Filtros Avanzados -->
            <div class="p-5 border-b border-slate-100 bg-slate-50/60 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-sliders text-udl-green text-base"></i>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-[#0f392b]">Filtrado Avanzado de Directores</h3>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="resetAdvancedFilters()" class="text-xs font-bold text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-100 border border-slate-300 px-3 py-1.5 rounded-lg transition shadow-sm">
                            <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Limpiar Filtros
                        </button>
                        <span id="directoresCounterBadge" class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-udl-dark">
                            <?= count($directores) ?> directores
                        </span>
                    </div>
                </div>

                <!-- Controles de Filtro en Cuadrícula -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Búsqueda por texto (Nombre / Apellidos) -->
                    <div>
                        <label for="filterText" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Búsqueda por texto
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" id="filterText" placeholder="Nombre, apellido o clave..." oninput="applyAdvancedFilters()" class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 focus:ring-2 focus:ring-udl-green focus:outline-none transition">
                        </div>
                    </div>

                    <!-- Filtro por Plantel -->
                    <div>
                        <label for="filterPlantel" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Plantel
                        </label>
                        <select id="filterPlantel" onchange="applyAdvancedFilters()" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 focus:ring-2 focus:ring-udl-green focus:outline-none transition">
                            <option value="">Todos los planteles</option>
                            <?php foreach ($planteles as $pl): ?>
                                <option value="<?= htmlspecialchars((string)$pl['nombre']) ?>"><?= htmlspecialchars((string)$pl['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro por Carrera / Licenciatura -->
                    <div>
                        <label for="filterLicenciatura" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Licenciatura
                        </label>
                        <select id="filterLicenciatura" onchange="applyAdvancedFilters()" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 focus:ring-2 focus:ring-udl-green focus:outline-none transition">
                            <option value="">Todas las licenciaturas</option>
                            <?php foreach ($licenciaturas as $lic): ?>
                                <option value="<?= htmlspecialchars((string)$lic['nombre']) ?>">
                                    <?= htmlspecialchars((string)$lic['nombre']) ?> <?= !empty($lic['nomenclatura']) ? '[' . htmlspecialchars((string)$lic['nomenclatura']) . ']' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro Combinado: Turno y Estatus -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="filterTurno" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                                Turno
                            </label>
                            <select id="filterTurno" onchange="applyAdvancedFilters()" class="w-full px-2 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 focus:ring-2 focus:ring-udl-green focus:outline-none transition">
                                <option value="">Todos</option>
                                <option value="T">T (Todas)</option>
                                <option value="M">M (Matutino)</option>
                                <option value="V">V (Vespertino)</option>
                            </select>
                        </div>
                        <div>
                            <label for="filterEstatus" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                                Estatus
                            </label>
                            <select id="filterEstatus" onchange="applyAdvancedFilters()" class="w-full px-2 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 focus:ring-2 focus:ring-udl-green focus:outline-none transition">
                                <option value="">Todos</option>
                                <option value="ACTIVO">Activos</option>
                                <option value="INACTIVO">Inactivos</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($directores)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <h4 class="text-base font-bold text-slate-700">No hay directores registrados</h4>
                    <p class="text-xs text-slate-500 mt-1">Utilice el formulario superior para registrar al primer director de carrera.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 sortable-table" id="directoresTable">
                        <thead class="bg-slate-50/90 text-xs uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 cursor-pointer">ID</th>
                                <th scope="col" class="px-5 py-3.5 cursor-pointer">Nombre Completo</th>
                                <th scope="col" class="px-5 py-3.5 cursor-pointer">Plantel</th>
                                <th scope="col" class="px-5 py-3.5 cursor-pointer">Licenciatura</th>
                                <th scope="col" class="px-5 py-3.5 text-center cursor-pointer">Turno</th>
                                <th scope="col" class="px-5 py-3.5 text-center cursor-pointer">Estatus</th>
                                <th scope="col" class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($directores as $dir): 
                                $idDir = (int)$dir['id_director'];
                                $nombreCompleto = trim((string)$dir['nombre'] . ' ' . (string)$dir['apellidos']);
                                $plantelName = (string)($dir['plantel_nombre'] ?? 'Sin asignar');
                                $licName = (string)($dir['licenciatura_nombre'] ?? 'Sin asignar');
                                $licNom = (string)($dir['licenciatura_nomenclatura'] ?? '');
                                $turnoCode = (string)($dir['turno'] ?? 'T');
                                $estatusVal = strtoupper((string)($dir['estatus'] ?? 'ACTIVO'));

                                $turnoLabel = match ($turnoCode) {
                                    'M' => 'Matutino',
                                    'V' => 'Vespertino',
                                    'T' => 'Todas',
                                    default => $turnoCode
                                };
                            ?>
                                <tr class="hover:bg-emerald-50/30 transition-colors director-row" 
                                    data-nombre="<?= htmlspecialchars(strtolower($nombreCompleto)) ?>"
                                    data-plantel="<?= htmlspecialchars(strtolower($plantelName)) ?>"
                                    data-licenciatura="<?= htmlspecialchars(strtolower($licName . ' ' . $licNom)) ?>"
                                    data-turno="<?= htmlspecialchars($turnoCode) ?>"
                                    data-estatus="<?= htmlspecialchars($estatusVal) ?>">
                                    <td class="px-5 py-4 font-mono text-xs text-slate-400">#<?= $idDir ?></td>
                                    <td class="px-5 py-4 font-bold text-slate-800">
                                        <?= htmlspecialchars($nombreCompleto) ?>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600">
                                        <?= htmlspecialchars($plantelName) ?>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-700">
                                        <div class="flex items-center space-x-1.5">
                                            <?php if (!empty($licNom)): ?>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                    <?= htmlspecialchars($licNom) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="font-medium"><?= htmlspecialchars($licName) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold <?= $turnoCode === 'T' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($turnoCode === 'M' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-purple-50 text-purple-700 border border-purple-200') ?>">
                                            <?= htmlspecialchars($turnoLabel) ?> (<?= htmlspecialchars($turnoCode) ?>)
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <?php if ($estatusVal === 'ACTIVO'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-udl-green border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-udl-green mr-1.5"></span>
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- Botón Editar -->
                                            <button type="button" 
                                                    onclick="editDirector(<?= htmlspecialchars(json_encode([
                                                        'id_director' => $idDir,
                                                        'nombre' => (string)$dir['nombre'],
                                                        'apellidos' => (string)$dir['apellidos'],
                                                        'id_plantel' => (int)$dir['id_plantel'],
                                                        'id_licenciatura' => (int)$dir['id_licenciatura'],
                                                        'turno' => $turnoCode,
                                                        'estatus' => $estatusVal
                                                    ])) ?>)"
                                                    title="Editar Director" 
                                                    class="p-2 rounded-lg text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 hover:scale-110 active:scale-95 border border-amber-200/80 transition-all shadow-sm">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>

                                            <!-- Botón Eliminar -->
                                            <form action="<?= htmlspecialchars(url('catalogos/eliminarDirector')) ?>" method="POST" class="inline" onsubmit="return confirmDelete('¿Está seguro de eliminar al director <?= htmlspecialchars($nombreCompleto) ?>?')">
                                                <?= \App\Core\Security::csrfField() ?>
                                                <input type="hidden" name="id_director" value="<?= $idDir ?>">
                                                <button type="submit" 
                                                        title="Eliminar Director" 
                                                        class="p-2 rounded-lg text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 hover:scale-110 active:scale-95 border border-rose-200/80 transition-all shadow-sm">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="noResultsAdvanced" class="p-8 text-center hidden">
                    <p class="text-xs text-slate-500 font-semibold">No se encontraron directores que coincidan con los filtros aplicados.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- TAB 2: PLANTELES (READ-ONLY) -->
    <div id="tabContentPlanteles" class="space-y-6 hidden">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center space-x-2">
                        <i class="fa-solid fa-school text-udl-green"></i>
                        <span>Planteles Institucionales</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Distribución geográfica organizada por región</p>
                </div>
                <span class="text-xs text-udl-green font-bold bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-lg">
                    <?= count($planteles) ?> Planteles
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 sortable-table">
                    <thead class="bg-slate-50/90 text-xs uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">ID Plantel</th>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">Nombre del Plantel</th>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">Región Asignada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($planteles as $pl): ?>
                            <tr class="hover:bg-emerald-50/20 transition-colors">
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-400">#<?= (int)$pl['id_plantel'] ?></td>
                                <td class="px-6 py-3.5 font-bold text-slate-800"><?= htmlspecialchars((string)$pl['nombre']) ?></td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= htmlspecialchars((string)($pl['region_nombre'] ?? 'Región ' . $pl['id_region'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: LICENCIATURAS (READ-ONLY CON NOMENCLATURAS) -->
    <div id="tabContentLicenciaturas" class="space-y-6 hidden">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center space-x-2">
                        <i class="fa-solid fa-graduation-cap text-udl-green"></i>
                        <span>Programas de Licenciatura y Nomenclaturas</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Catálogo oficial con nomenclaturas institucionales</p>
                </div>
                <span class="text-xs text-udl-green font-bold bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-lg">
                    <?= count($licenciaturas) ?> Carreras
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 sortable-table">
                    <thead class="bg-slate-50/90 text-xs uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">ID</th>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">Nomenclatura</th>
                            <th scope="col" class="px-6 py-3.5 cursor-pointer">Nombre del Programa Académico</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($licenciaturas as $lic): ?>
                            <tr class="hover:bg-emerald-50/20 transition-colors">
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-400">#<?= (int)$lic['id_licenciatura'] ?></td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-mono font-bold bg-emerald-50 text-udl-green border border-emerald-200">
                                        <?= htmlspecialchars((string)($lic['nomenclatura'] ?? '-')) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 font-bold text-slate-800"><?= htmlspecialchars((string)$lic['nombre']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
function switchCatalogTab(tab) {
    const tabs = ['directores', 'planteles', 'licenciaturas'];
    tabs.forEach(t => {
        const btn = document.getElementById('tabBtn' + capitalize(t));
        const content = document.getElementById('tabContent' + capitalize(t));
        if (t === tab) {
            btn.classList.add('border-udl-green', 'text-udl-green');
            btn.classList.remove('border-transparent', 'text-slate-500');
            content.classList.remove('hidden');
        } else {
            btn.classList.remove('border-udl-green', 'text-udl-green');
            btn.classList.add('border-transparent', 'text-slate-500');
            content.classList.add('hidden');
        }
    });
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function editDirector(data) {
    switchCatalogTab('directores');
    document.getElementById('form_id_director').value = data.id_director;
    document.getElementById('form_nombre').value = data.nombre;
    document.getElementById('form_apellidos').value = data.apellidos;
    document.getElementById('form_id_plantel').value = data.id_plantel;
    document.getElementById('form_id_licenciatura').value = data.id_licenciatura;
    document.getElementById('form_turno').value = data.turno;
    document.getElementById('form_estatus').value = data.estatus;

    document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-user-pen text-udl-green"></i><span>Editar Director: ' + escapeHtml(data.nombre + ' ' + data.apellidos) + '</span>';
    document.getElementById('btnSubmitText').textContent = 'Actualizar Director';
    document.getElementById('btnCancelEdit').classList.remove('hidden');

    const container = document.getElementById('directorFormContainer');
    container.classList.add('ring-2', 'ring-udl-green');
    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function resetDirectorForm() {
    document.getElementById('form_id_director').value = '';
    document.getElementById('form_nombre').value = '';
    document.getElementById('form_apellidos').value = '';
    document.getElementById('form_id_plantel').value = '';
    document.getElementById('form_id_licenciatura').value = '';
    document.getElementById('form_turno').value = 'T';
    document.getElementById('form_estatus').value = 'ACTIVO';

    document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-user-plus text-udl-green"></i><span>Registrar Nuevo Director</span>';
    document.getElementById('btnSubmitText').textContent = 'Guardar Director';
    document.getElementById('btnCancelEdit').classList.add('hidden');

    const container = document.getElementById('directorFormContainer');
    container.classList.remove('ring-2', 'ring-udl-green');
}

/**
 * Sistema de Filtrado Avanzado Multi-criterio en Directores
 */
function applyAdvancedFilters() {
    const textVal = (document.getElementById('filterText')?.value || '').toLowerCase().trim();
    const plantelVal = (document.getElementById('filterPlantel')?.value || '').toLowerCase().trim();
    const licVal = (document.getElementById('filterLicenciatura')?.value || '').toLowerCase().trim();
    const turnoVal = (document.getElementById('filterTurno')?.value || '').trim();
    const estatusVal = (document.getElementById('filterEstatus')?.value || '').trim();

    const rows = document.querySelectorAll('.director-row');
    let visibleCount = 0;

    rows.forEach(r => {
        const rowNombre = r.getAttribute('data-nombre') || '';
        const rowPlantel = r.getAttribute('data-plantel') || '';
        const rowLic = r.getAttribute('data-licenciatura') || '';
        const rowTurno = r.getAttribute('data-turno') || '';
        const rowEstatus = r.getAttribute('data-estatus') || '';

        // Comprobación de criterios
        const matchText = (textVal === '') || rowNombre.includes(textVal) || rowPlantel.includes(textVal) || rowLic.includes(textVal);
        const matchPlantel = (plantelVal === '') || rowPlantel === plantelVal;
        const matchLic = (licVal === '') || rowLic.includes(licVal);
        const matchTurno = (turnoVal === '') || rowTurno === turnoVal;
        const matchEstatus = (estatusVal === '') || rowEstatus === estatusVal;

        if (matchText && matchPlantel && matchLic && matchTurno && matchEstatus) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });

    const badge = document.getElementById('directoresCounterBadge');
    if (badge) {
        badge.textContent = `${visibleCount} director(es)`;
    }

    const noResults = document.getElementById('noResultsAdvanced');
    if (noResults) {
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }
}

function resetAdvancedFilters() {
    if (document.getElementById('filterText')) document.getElementById('filterText').value = '';
    if (document.getElementById('filterPlantel')) document.getElementById('filterPlantel').value = '';
    if (document.getElementById('filterLicenciatura')) document.getElementById('filterLicenciatura').value = '';
    if (document.getElementById('filterTurno')) document.getElementById('filterTurno').value = '';
    if (document.getElementById('filterEstatus')) document.getElementById('filterEstatus').value = '';
    applyAdvancedFilters();
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
