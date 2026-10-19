<?php
declare(strict_types=1);

$pageTitle = 'Catálogos Institucionales - Evaluación Directiva';
require APP_ROOT . '/views/layouts/header.php';

$directores = $data['directores'] ?? [];
$planteles = $data['planteles'] ?? [];
$licenciaturas = $data['licenciaturas'] ?? [];
$regiones = $data['regiones'] ?? [];
?>

<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Catálogos Institucionales</h1>
            <p class="text-sm text-slate-500 mt-1">Diccionario maestro de directores, planteles, licenciaturas y regiones</p>
        </div>
        <div class="mt-3 sm:mt-0 flex items-center space-x-2">
            <span class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200">
                <i class="fa-solid fa-users-gear mr-1"></i>
                <?= count($directores) ?> Directores Registrados
            </span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6 text-sm font-semibold" aria-label="Tabs">
            <button type="button" id="tabBtnDirectores" onclick="switchCatalogTab('directores')" class="pb-3 border-b-2 border-indigo-600 text-indigo-600 flex items-center space-x-2 focus:outline-none transition">
                <i class="fa-solid fa-user-tie"></i>
                <span>Directores de Carrera</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[11px] bg-indigo-100 text-indigo-700"><?= count($directores) ?></span>
            </button>
            <button type="button" id="tabBtnPlanteles" onclick="switchCatalogTab('planteles')" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 flex items-center space-x-2 focus:outline-none transition">
                <i class="fa-solid fa-school"></i>
                <span>Planteles</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[11px] bg-slate-100 text-slate-600"><?= count($planteles) ?></span>
            </button>
            <button type="button" id="tabBtnLicenciaturas" onclick="switchCatalogTab('licenciaturas')" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 flex items-center space-x-2 focus:outline-none transition">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Licenciaturas</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[11px] bg-slate-100 text-slate-600"><?= count($licenciaturas) ?></span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: DIRECTORES (CRUD) -->
    <div id="tabContentDirectores" class="space-y-6">

        <!-- Form Card (Crear / Editar) -->
        <div id="directorFormContainer" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 transition-all">
            <div class="flex items-center justify-between mb-4">
                <h2 id="formTitle" class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                    <i class="fa-solid fa-user-plus text-indigo-600"></i>
                    <span>Registrar Nuevo Director</span>
                </h2>
                <button type="button" id="btnCancelEdit" onclick="resetDirectorForm()" class="hidden text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded-lg transition">
                    <i class="fa-solid fa-xmark mr-1"></i> Cancelar Edición
                </button>
            </div>

            <form id="directorForm" action="<?= htmlspecialchars(url('catalogos/guardarDirector')) ?>" method="POST" class="space-y-4">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id_director" id="form_id_director" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    
                    <!-- Nombre -->
                    <div>
                        <label for="form_nombre" class="block text-xs font-semibold text-slate-700 mb-1">
                            Nombre(s) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nombre" id="form_nombre" required placeholder="ej. María Elena" class="block w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Apellidos -->
                    <div>
                        <label for="form_apellidos" class="block text-xs font-semibold text-slate-700 mb-1">
                            Apellidos <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="apellidos" id="form_apellidos" required placeholder="ej. Gómez Ramírez" class="block w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Plantel -->
                    <div>
                        <label for="form_id_plantel" class="block text-xs font-semibold text-slate-700 mb-1">
                            Plantel <span class="text-rose-500">*</span>
                        </label>
                        <select name="id_plantel" id="form_id_plantel" required class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Seleccione Plantel --</option>
                            <?php foreach ($planteles as $pl): ?>
                                <option value="<?= (int)$pl['id_plantel'] ?>">
                                    <?= htmlspecialchars((string)$pl['nombre']) ?> (<?= htmlspecialchars((string)($pl['region_nombre'] ?? '')) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Licenciatura -->
                    <div>
                        <label for="form_id_licenciatura" class="block text-xs font-semibold text-slate-700 mb-1">
                            Licenciatura / Carrera <span class="text-rose-500">*</span>
                        </label>
                        <select name="id_licenciatura" id="form_id_licenciatura" required class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Seleccione Licenciatura --</option>
                            <?php foreach ($licenciaturas as $lic): ?>
                                <option value="<?= (int)$lic['id_licenciatura'] ?>">
                                    <?= htmlspecialchars((string)$lic['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Turno -->
                    <div>
                        <label for="form_turno" class="block text-xs font-semibold text-slate-700 mb-1">
                            Turno <span class="text-rose-500">*</span>
                        </label>
                        <select name="turno" id="form_turno" required class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="M">M - Matutino</option>
                            <option value="V">V - Vespertino</option>
                            <option value="D">D - Diurno</option>
                            <option value="N">N - Nocturno</option>
                            <option value="T" selected>T - Todas</option>
                        </select>
                    </div>

                    <!-- Estatus -->
                    <div>
                        <label for="form_estatus" class="block text-xs font-semibold text-slate-700 mb-1">
                            Estatus
                        </label>
                        <select name="estatus" id="form_estatus" class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="ACTIVO" selected>ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                        </select>
                    </div>

                </div>

                <div class="pt-2 flex items-center justify-end space-x-3">
                    <button type="submit" id="btnSubmitDirector" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 font-semibold text-sm shadow-md shadow-indigo-600/20 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span id="btnSubmitText">Guardar Director</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Directors Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                    <i class="fa-solid fa-users text-indigo-600"></i>
                    <span>Listado de Directores</span>
                </h3>
                <div class="w-full sm:w-64">
                    <input type="text" id="directorSearchInput" placeholder="Buscar director o carrera..." onkeyup="filterDirectorTable()" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
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
                    <table class="w-full text-left text-sm text-slate-600" id="directoresTable">
                        <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200/80 tracking-wider">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">ID</th>
                                <th scope="col" class="px-5 py-3.5">Nombre Completo</th>
                                <th scope="col" class="px-5 py-3.5">Plantel</th>
                                <th scope="col" class="px-5 py-3.5">Licenciatura</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Turno</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Estatus</th>
                                <th scope="col" class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($directores as $dir): 
                                $idDir = (int)$dir['id_director'];
                                $nombreCompleto = trim((string)$dir['nombre'] . ' ' . (string)$dir['apellidos']);
                                $plantelName = (string)($dir['plantel_nombre'] ?? 'Sin asignar');
                                $licName = (string)($dir['licenciatura_nombre'] ?? 'Sin asignar');
                                $turnoCode = (string)($dir['turno'] ?? 'T');
                                $estatusVal = strtoupper((string)($dir['estatus'] ?? 'ACTIVO'));

                                $turnoLabel = match ($turnoCode) {
                                    'M' => 'Matutino',
                                    'V' => 'Vespertino',
                                    'D' => 'Diurno',
                                    'N' => 'Nocturno',
                                    'T' => 'Todas',
                                    default => $turnoCode
                                };
                            ?>
                                <tr class="hover:bg-slate-50/60 transition-colors director-row" data-search="<?= strtolower(htmlspecialchars($nombreCompleto . ' ' . $plantelName . ' ' . $licName)) ?>">
                                    <td class="px-5 py-4 font-mono text-xs text-slate-400">#<?= $idDir ?></td>
                                    <td class="px-5 py-4 font-bold text-slate-800">
                                        <?= htmlspecialchars($nombreCompleto) ?>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600">
                                        <?= htmlspecialchars($plantelName) ?>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600 font-medium">
                                        <?= htmlspecialchars($licName) ?>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            <?= htmlspecialchars($turnoLabel) ?> (<?= htmlspecialchars($turnoCode) ?>)
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <?php if ($estatusVal === 'ACTIVO'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end space-x-1.5">
                                            <!-- Editar -->
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
                                                    class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                                <i class="fa-solid fa-pen-to-square text-sm"></i>
                                            </button>

                                            <!-- Eliminar -->
                                            <form action="<?= htmlspecialchars(url('catalogos/eliminarDirector')) ?>" method="POST" class="inline" onsubmit="return confirmDelete('¿Está seguro de eliminar al director <?= htmlspecialchars($nombreCompleto) ?>?')">
                                                <?= \App\Core\Security::csrfField() ?>
                                                <input type="hidden" name="id_director" value="<?= $idDir ?>">
                                                <button type="submit" 
                                                        title="Eliminar Director" 
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
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

    <!-- TAB 2: PLANTELES (READ-ONLY) -->
    <div id="tabContentPlanteles" class="space-y-6 hidden">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                        <i class="fa-solid fa-school text-indigo-600"></i>
                        <span>Planteles Institucionales</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Distribución geográfica organizada por región</p>
                </div>
                <span class="text-xs text-slate-500 font-semibold"><?= count($planteles) ?> Planteles</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200/80 tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">ID Plantel</th>
                            <th scope="col" class="px-6 py-3.5">Nombre del Plantel</th>
                            <th scope="col" class="px-6 py-3.5">Región Asignada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($planteles as $pl): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-3 font-mono text-xs text-slate-400">#<?= (int)$pl['id_plantel'] ?></td>
                                <td class="px-6 py-3 font-bold text-slate-800"><?= htmlspecialchars((string)$pl['nombre']) ?></td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
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

    <!-- TAB 3: LICENCIATURAS (READ-ONLY) -->
    <div id="tabContentLicenciaturas" class="space-y-6 hidden">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                        <i class="fa-solid fa-graduation-cap text-indigo-600"></i>
                        <span>Programas de Licenciatura</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Catálogo oficial de carreras universitarias evaluadas</p>
                </div>
                <span class="text-xs text-slate-500 font-semibold"><?= count($licenciaturas) ?> Programas</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200/80 tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">ID Licenciatura</th>
                            <th scope="col" class="px-6 py-3.5">Nombre del Programa Académico</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($licenciaturas as $lic): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-3 font-mono text-xs text-slate-400">#<?= (int)$lic['id_licenciatura'] ?></td>
                                <td class="px-6 py-3 font-semibold text-slate-800"><?= htmlspecialchars((string)$lic['nombre']) ?></td>
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
            btn.classList.add('border-indigo-600', 'text-indigo-600');
            btn.classList.remove('border-transparent', 'text-slate-500');
            content.classList.remove('hidden');
        } else {
            btn.classList.remove('border-indigo-600', 'text-indigo-600');
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

    document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-user-pen text-indigo-600"></i><span>Editar Director: ' + escapeHtml(data.nombre + ' ' + data.apellidos) + '</span>';
    document.getElementById('btnSubmitText').textContent = 'Actualizar Director';
    document.getElementById('btnCancelEdit').classList.remove('hidden');

    const container = document.getElementById('directorFormContainer');
    container.classList.add('ring-2', 'ring-indigo-500');
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

    document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-user-plus text-indigo-600"></i><span>Registrar Nuevo Director</span>';
    document.getElementById('btnSubmitText').textContent = 'Guardar Director';
    document.getElementById('btnCancelEdit').classList.add('hidden');

    const container = document.getElementById('directorFormContainer');
    container.classList.remove('ring-2', 'ring-indigo-500');
}

function filterDirectorTable() {
    const input = document.getElementById('directorSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.director-row');
    rows.forEach(r => {
        const text = r.getAttribute('data-search') || '';
        r.style.display = text.includes(input) ? '' : 'none';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
