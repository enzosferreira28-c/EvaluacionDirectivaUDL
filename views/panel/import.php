<?php
declare(strict_types=1);

$pageTitle = 'Importar Encuestas Excel - Evaluación Directiva';
require APP_ROOT . '/views/layouts/header.php';

$periodos = $data['periodos'] ?? [];
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Carga y Procesamiento de Encuestas</h1>
            <p class="text-sm text-slate-500 mt-1">Importe las respuestas de los alumnos desde una hoja de cálculo Excel (.xlsx / .xls)</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <a href="<?= htmlspecialchars(url('periodos')) ?>" class="inline-flex items-center space-x-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg transition">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo Periodo</span>
            </a>
        </div>
    </div>

    <!-- Instructions Banner -->
    <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs sm:text-sm flex items-start space-x-3">
        <i class="fa-solid fa-circle-info text-blue-500 text-lg mt-0.5 flex-shrink-0"></i>
        <div class="space-y-1">
            <p class="font-semibold text-blue-950">Estructura del archivo requerida:</p>
            <p class="text-blue-800 leading-relaxed">
                El archivo debe contener las columnas: <em>Marca temporal, Plantel, Grupo, Licenciatura, y las 10 preguntas evaluativas (P1 a P10)</em>. La primera fila se interpretará como encabezados y será omitida de los cálculos.
            </p>
        </div>
    </div>

    <!-- Main Import Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form id="importForm" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= \App\Core\Security::csrfField() ?>

            <!-- Periodo Selection -->
            <div>
                <label for="id_periodo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    1. Seleccione el Periodo de Evaluación <span class="text-rose-500">*</span>
                </label>
                <?php if (empty($periodos)): ?>
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs flex items-center justify-between">
                        <span>No hay periodos creados. Debe crear un periodo antes de poder cargar encuestas.</span>
                        <a href="<?= htmlspecialchars(url('periodos')) ?>" class="font-bold underline ml-2">Crear Periodo</a>
                    </div>
                <?php else: ?>
                    <select name="id_periodo" id="id_periodo" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">-- Seleccione un ciclo académico --</option>
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= htmlspecialchars((string)$p['id_periodo']) ?>">
                                <?= htmlspecialchars((string)$p['id_periodo']) ?> (<?= htmlspecialchars((string)($p['estatus'] ?? 'ACTIVO')) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <!-- File Upload Dropzone -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    2. Seleccione el Archivo Excel (.xlsx / .xls) <span class="text-rose-500">*</span>
                </label>
                <div id="dropzone" class="border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-2xl p-6 sm:p-8 text-center bg-slate-50/50 hover:bg-indigo-50/30 transition-all cursor-pointer">
                    <input type="file" name="archivo_excel" id="archivo_excel" accept=".xlsx, .xls" required class="hidden">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl shadow-inner">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-slate-700">Arrastre su archivo aquí o </span>
                            <span class="text-sm font-bold text-indigo-600 hover:underline">examine en su equipo</span>
                        </div>
                        <p id="fileDetails" class="text-xs text-slate-500 font-mono">Archivos soportados: Microsoft Excel (.xlsx, .xls)</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" id="btnSubmit" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-3 rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 font-semibold shadow-md shadow-indigo-600/20 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-play text-xs"></i>
                    <span>Iniciar Importación y Cálculo Estadístico</span>
                </button>
            </div>
        </form>

        <!-- Progress Indicator (Hidden Initially) -->
        <div id="progressContainer" class="mt-8 pt-6 border-t border-slate-200 hidden">
            <div class="flex items-center justify-between mb-2">
                <span id="progressStatus" class="text-xs font-semibold text-slate-700 flex items-center space-x-2">
                    <i class="fa-solid fa-spinner animate-spin text-indigo-600"></i>
                    <span>Procesando archivo... Por favor espere.</span>
                </span>
                <span id="progressPercent" class="text-xs font-bold text-indigo-600">0%</span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden shadow-inner">
                <div id="progressBar" class="bg-indigo-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 text-center">
                El sistema lee el archivo en memoria, vincula directores por clave de grupo y computa rankings y desviaciones estándar.
            </p>
        </div>

        <!-- Dynamic Results Container (Hidden Initially) -->
        <div id="resultsContainer" class="mt-8 pt-6 border-t border-slate-200 hidden space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800 flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                    <span>Resultado del Procesamiento</span>
                </h3>
                <a id="btnVerResultados" href="<?= htmlspecialchars(url('reportes')) ?>" class="inline-flex items-center space-x-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 px-3.5 py-2 rounded-lg shadow-sm transition">
                    <span>Ver Resultados y Rankings</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- Stats Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <p class="text-xs text-slate-500 font-semibold uppercase">Total Leídos</p>
                    <p id="resTotalRead" class="text-2xl font-bold text-slate-800 mt-1">0</p>
                </div>
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200">
                    <p class="text-xs text-emerald-700 font-semibold uppercase">Insertados</p>
                    <p id="resInserted" class="text-2xl font-bold text-emerald-700 mt-1">0</p>
                </div>
                <div class="bg-amber-50 p-4 rounded-xl border border-amber-200">
                    <p class="text-xs text-amber-700 font-semibold uppercase">Omitidos</p>
                    <p id="resSkipped" class="text-2xl font-bold text-amber-700 mt-1">0</p>
                </div>
                <div class="bg-rose-50 p-4 rounded-xl border border-rose-200">
                    <p class="text-xs text-rose-700 font-semibold uppercase">Errores</p>
                    <p id="resErrorsCount" class="text-2xl font-bold text-rose-700 mt-1">0</p>
                </div>
            </div>

            <!-- Errors/Skipped Log Details (If any) -->
            <div id="errorsSection" class="hidden">
                <h4 class="text-xs font-bold uppercase tracking-wider text-rose-700 mb-2">Detalle de Incidencias / Filas Omitidas:</h4>
                <div class="max-h-48 overflow-y-auto bg-rose-50/50 border border-rose-200 rounded-xl p-3 text-xs text-rose-900 font-mono space-y-1">
                    <ul id="errorsList" class="list-disc pl-5 space-y-1"></ul>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('importForm');
    const fileInput = document.getElementById('archivo_excel');
    const dropzone = document.getElementById('dropzone');
    const fileDetails = document.getElementById('fileDetails');
    const btnSubmit = document.getElementById('btnSubmit');

    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');

    const resultsContainer = document.getElementById('resultsContainer');
    const resTotalRead = document.getElementById('resTotalRead');
    const resInserted = document.getElementById('resInserted');
    const resSkipped = document.getElementById('resSkipped');
    const resErrorsCount = document.getElementById('resErrorsCount');
    const errorsSection = document.getElementById('errorsSection');
    const errorsList = document.getElementById('errorsList');
    const btnVerResultados = document.getElementById('btnVerResultados');

    // Drag and drop events
    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.add('border-indigo-500', 'bg-indigo-50/40');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/40');
            }, false);
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                fileInput.files = dt.files;
                updateFileLabel();
            }
        });

        fileInput.addEventListener('change', updateFileLabel);
    }

    function updateFileLabel() {
        if (fileInput.files && fileInput.files.length > 0) {
            const f = fileInput.files[0];
            const sizeMb = (f.size / (1024 * 1024)).toFixed(2);
            fileDetails.innerHTML = `<strong class="text-indigo-600">${f.name}</strong> (${sizeMb} MB)`;
        } else {
            fileDetails.textContent = 'Archivos soportados: Microsoft Excel (.xlsx, .xls)';
        }
    }

    // Submit handler via fetch
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const periodoSelect = document.getElementById('id_periodo');
        const selectedPeriodo = periodoSelect.value;

        if (!selectedPeriodo) {
            showAlert('Por favor seleccione un periodo de evaluación.', 'warning');
            return;
        }

        if (!fileInput.files || fileInput.files.length === 0) {
            showAlert('Por favor seleccione un archivo Excel.', 'warning');
            return;
        }

        // Confirmation warning
        const confirmMsg = `¿Está seguro de procesar las encuestas para el periodo ${selectedPeriodo}? Si ya existen respuestas previas, los cálculos serán recalculados.`;
        if (!confirm(confirmMsg)) {
            return;
        }

        const formData = new FormData(form);

        // UI Feedback: Start progress
        btnSubmit.disabled = true;
        progressContainer.classList.remove('hidden');
        resultsContainer.classList.add('hidden');
        errorsSection.classList.add('hidden');
        errorsList.innerHTML = '';

        progressBar.style.width = '30%';
        progressPercent.textContent = '30%';
        progressStatus.innerHTML = '<i class="fa-solid fa-spinner animate-spin text-indigo-600 mr-2"></i> Subiendo archivo al servidor...';

        try {
            // Simulated progress tick while server computes
            setTimeout(() => {
                if (progressBar.style.width === '30%') {
                    progressBar.style.width = '65%';
                    progressPercent.textContent = '65%';
                    progressStatus.innerHTML = '<i class="fa-solid fa-cog animate-spin text-indigo-600 mr-2"></i> Analizando respuestas y calculando rankings...';
                }
            }, 800);

            const response = await fetch('index.php?route=import/upload', {
                method: 'POST',
                body: formData
            });

            progressBar.style.width = '100%';
            progressPercent.textContent = '100%';

            const result = await response.json();

            if (!result.success) {
                progressStatus.innerHTML = '<i class="fa-solid fa-circle-xmark text-rose-500 mr-2"></i> Error en la importación';
                showAlert(result.message || 'Ocurrió un error al procesar el archivo.', 'error');
                return;
            }

            // Success display
            progressStatus.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-500 mr-2"></i> Proceso completado exitosamente';
            showAlert(result.message || 'Importación completada con éxito.', 'success');

            const importData = result.data?.import || {};
            resTotalRead.textContent = importData.total_read ?? 0;
            resInserted.textContent = importData.inserted ?? 0;
            resSkipped.textContent = importData.skipped ?? 0;

            const errors = importData.errors || [];
            resErrorsCount.textContent = errors.length;

            if (errors.length > 0) {
                errorsSection.classList.remove('hidden');
                errorsList.innerHTML = '';
                errors.forEach(err => {
                    const li = document.createElement('li');
                    li.textContent = typeof err === 'string' ? err : JSON.stringify(err);
                    errorsList.appendChild(li);
                });
            }

            // Configure button to view reports
            btnVerResultados.href = `index.php?route=reportes&id_periodo=${encodeURIComponent(selectedPeriodo)}`;
            resultsContainer.classList.remove('hidden');

        } catch (error) {
            progressBar.style.width = '0%';
            progressPercent.textContent = '0%';
            progressStatus.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-rose-500 mr-2"></i> Error en la comunicación con el servidor';
            showAlert('Error de conexión al subir el archivo: ' + error.message, 'error');
        } finally {
            btnSubmit.disabled = false;
        }
    });
});
</script>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
