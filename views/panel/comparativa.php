<?php
declare(strict_types=1);

$pageTitle = 'Comparativa de Periodos - Universidad de León';
require APP_ROOT . '/views/layouts/header.php';

$periodos = $data['periodos'] ?? [];
?>

<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Header Section (Estilo UDL) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#0f392b] flex items-center space-x-2.5">
                <i class="fa-solid fa-code-compare text-udl-green"></i>
                <span>Comparativa Histórica entre Periodos</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Contraste el rendimiento de directores y carreras a lo largo del tiempo</p>
        </div>
        <div class="mt-3 sm:mt-0 flex items-center space-x-2">
            <!-- Botón PDF Comparativa (aparece dinámicamente) -->
            <a id="btnDescargarPdfComp" href="#" target="_blank" class="hidden inline-flex items-center space-x-2 text-xs font-bold text-white bg-red-700 hover:bg-red-800 px-3.5 py-2 rounded-lg shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Descargar PDF Comparativa</span>
            </a>
            <a href="<?= htmlspecialchars(url('reportes')) ?>" class="inline-flex items-center space-x-2 text-xs font-bold text-udl-green bg-emerald-50 hover:bg-emerald-100 hover:text-udl-dark border border-emerald-200 px-3.5 py-2 rounded-lg transition-all hover:shadow-sm hover:-translate-y-0.5">
                <i class="fa-solid fa-chart-simple"></i>
                <span>Ver Resultados Individuales</span>
            </a>
        </div>
    </div>

    <!-- Selection Controls Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm p-6 transition-all hover:shadow-md">
        <form id="comparisonForm" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <!-- Periodo 1 -->
            <div>
                <label for="periodo1" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Periodo Base (P1) <span class="text-rose-500">*</span>
                </label>
                <select name="periodo1" id="periodo1" required class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 text-sm font-semibold focus:ring-2 focus:ring-udl-green focus:border-udl-green transition">
                    <option value="">-- Seleccionar Periodo 1 --</option>
                    <?php foreach ($periodos as $p): ?>
                        <option value="<?= htmlspecialchars((string)$p['id_periodo']) ?>">
                            <?= htmlspecialchars((string)$p['id_periodo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Periodo 2 -->
            <div>
                <label for="periodo2" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Periodo Comparativo (P2) <span class="text-rose-500">*</span>
                </label>
                <select name="periodo2" id="periodo2" required class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 text-sm font-semibold focus:ring-2 focus:ring-udl-green focus:border-udl-green transition">
                    <option value="">-- Seleccionar Periodo 2 --</option>
                    <?php foreach ($periodos as $p): ?>
                        <option value="<?= htmlspecialchars((string)$p['id_periodo']) ?>">
                            <?= htmlspecialchars((string)$p['id_periodo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Button -->
            <div>
                <button type="submit" id="btnComparar" class="w-full inline-flex items-center justify-center space-x-2 px-5 py-2.5 rounded-lg text-white bg-[#007547] hover:bg-[#0f392b] font-bold text-sm shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition duration-200 disabled:opacity-50">
                    <i class="fa-solid fa-code-compare text-xs"></i>
                    <span>Realizar Comparativa</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Empty State (Shown before search) -->
    <div id="initialState" class="bg-white rounded-xl border border-slate-200/90 shadow-sm p-12 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-50 text-udl-green flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner">
            <i class="fa-solid fa-arrow-right-arrow-left"></i>
        </div>
        <h3 class="text-base font-bold text-[#0f392b]">Seleccione dos periodos para comenzar</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto leading-relaxed">
            El sistema calculará las diferencias en el promedio global de cada director, indicando la tendencia ascendente o descendente entre ambos ciclos de evaluación y permitiendo la descarga en PDF.
        </p>
    </div>

    <!-- Comparison Results Container (Hidden initially) -->
    <div id="comparisonResults" class="space-y-6 hidden">
        
        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Evaluados</p>
                <p id="kpiTotalDirectores" class="text-3xl font-black text-[#0f392b] mt-1">0</p>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">En ambos periodos</p>
            </div>

            <div class="bg-emerald-50 rounded-xl p-5 border border-emerald-200 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-udl-green">Tendencia Positiva</p>
                        <p id="kpiMejora" class="text-3xl font-black text-emerald-800 mt-1">0</p>
                        <p id="kpiMejoraPct" class="text-[11px] text-emerald-700 mt-0.5 font-semibold">0% de directores</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-udl-green flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                    </div>
                </div>
            </div>

            <div class="bg-rose-50 rounded-xl p-5 border border-rose-200 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-rose-800">Tendencia Negativa</p>
                        <p id="kpiDescenso" class="text-3xl font-black text-rose-700 mt-1">0</p>
                        <p id="kpiDescensoPct" class="text-[11px] text-rose-600 mt-0.5 font-semibold">0% de directores</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-arrow-trend-down"></i>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 rounded-xl p-5 border border-amber-200 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-800">Sin Variación</p>
                        <p id="kpiEstable" class="text-3xl font-black text-amber-700 mt-1">0</p>
                        <p id="kpiEstablePct" class="text-[11px] text-amber-600 mt-0.5 font-semibold">0% de directores</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-equals"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- Comparison Table -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#0f392b] flex items-center space-x-2">
                        <i class="fa-solid fa-table-list text-udl-green"></i>
                        <span>Tabla Comparativa de Desempeño</span>
                    </h3>
                    <p id="comparativaSubtitle" class="text-xs text-slate-500 mt-0.5">Diferencial calculado como P2 - P1</p>
                </div>
                <div class="flex items-center space-x-2">
                    <a id="btnPdfInline" href="#" target="_blank" class="inline-flex items-center space-x-1.5 text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-lg transition">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Exportar PDF</span>
                    </a>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                        <input type="text" id="filterComparisonInput" placeholder="Buscar en resultados..." onkeyup="filterComparisonRows()" class="pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-udl-green focus:border-udl-green focus:outline-none transition">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600" id="comparisonTable">
                    <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">Director</th>
                            <th scope="col" class="px-5 py-3.5">Plantel</th>
                            <th scope="col" class="px-5 py-3.5">Licenciatura</th>
                            <th scope="col" class="px-5 py-3.5 text-center font-mono" id="colHeaderP1">Promedio P1</th>
                            <th scope="col" class="px-5 py-3.5 text-center font-mono" id="colHeaderP2">Promedio P2</th>
                            <th scope="col" class="px-5 py-3.5 text-center font-mono">Diferencia</th>
                            <th scope="col" class="px-5 py-3.5 text-center">Tendencia</th>
                        </tr>
                    </thead>
                    <tbody id="comparisonTableBody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('comparisonForm');
    const p1Select = document.getElementById('periodo1');
    const p2Select = document.getElementById('periodo2');
    const btnComparar = document.getElementById('btnComparar');

    const initialState = document.getElementById('initialState');
    const comparisonResults = document.getElementById('comparisonResults');
    const tbody = document.getElementById('comparisonTableBody');

    const colHeaderP1 = document.getElementById('colHeaderP1');
    const colHeaderP2 = document.getElementById('colHeaderP2');
    const comparativaSubtitle = document.getElementById('comparativaSubtitle');

    const btnDescargarPdfComp = document.getElementById('btnDescargarPdfComp');
    const btnPdfInline = document.getElementById('btnPdfInline');

    const kpiTotal = document.getElementById('kpiTotalDirectores');
    const kpiMejora = document.getElementById('kpiMejora');
    const kpiMejoraPct = document.getElementById('kpiMejoraPct');
    const kpiDescenso = document.getElementById('kpiDescenso');
    const kpiDescensoPct = document.getElementById('kpiDescensoPct');
    const kpiEstable = document.getElementById('kpiEstable');
    const kpiEstablePct = document.getElementById('kpiEstablePct');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const p1 = p1Select.value;
        const p2 = p2Select.value;

        if (!p1 || !p2) {
            showAlert('Por favor seleccione ambos periodos.', 'warning');
            return;
        }

        if (p1 === p2) {
            showAlert('Seleccione periodos diferentes para contrastar.', 'warning');
            return;
        }

        btnComparar.disabled = true;
        showLoading('Consultando datos de comparativa...');

        try {
            const url = `index.php?route=api/comparativa&periodo1=${encodeURIComponent(p1)}&periodo2=${encodeURIComponent(p2)}`;
            const response = await fetch(url);
            const res = await response.json();

            if (!res.success) {
                showAlert(res.message || 'Error al obtener la comparativa.', 'error');
                return;
            }

            const data = res.data || {};
            const items = data.comparativa || [];

            colHeaderP1.textContent = `Prom. ${p1}`;
            colHeaderP2.textContent = `Prom. ${p2}`;
            comparativaSubtitle.textContent = `Comparando ciclo ${p1} contra ciclo ${p2} (Diferencia = ${p2} - ${p1})`;

            // Actualizar URL del botón de PDF
            const pdfUrl = `index.php?route=comparativa/pdf&periodo1=${encodeURIComponent(p1)}&periodo2=${encodeURIComponent(p2)}`;
            if (btnDescargarPdfComp) {
                btnDescargarPdfComp.href = pdfUrl;
                btnDescargarPdfComp.classList.remove('hidden');
            }
            if (btnPdfInline) {
                btnPdfInline.href = pdfUrl;
            }

            tbody.innerHTML = '';

            let countMejora = 0;
            let countDescenso = 0;
            let countEstable = 0;

            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-400 italic">
                            No se encontraron directores o carreras coincidentes evaluados en ambos periodos seleccionados.
                        </td>
                    </tr>
                `;
            } else {
                items.forEach(row => {
                    const diff = parseFloat(row.diferencia ?? (row.promedio_p2 - row.promedio_p1));
                    const p1Val = parseFloat(row.promedio_p1 ?? 0).toFixed(2);
                    const p2Val = parseFloat(row.promedio_p2 ?? 0).toFixed(2);

                    let trendBadge = '';
                    let diffText = diff > 0 ? `+${diff.toFixed(2)}` : diff.toFixed(2);
                    let diffClass = '';

                    if (diff > 0.05) {
                        countMejora++;
                        diffClass = 'text-emerald-700 font-extrabold';
                        trendBadge = `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <i class="fa-solid fa-arrow-trend-up mr-1 text-[10px]"></i> Mejora
                            </span>
                        `;
                    } else if (diff < -0.05) {
                        countDescenso++;
                        diffClass = 'text-rose-600 font-extrabold';
                        trendBadge = `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                <i class="fa-solid fa-arrow-trend-down mr-1 text-[10px]"></i> Descenso
                            </span>
                        `;
                    } else {
                        countEstable++;
                        diffClass = 'text-amber-700 font-bold';
                        trendBadge = `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                <i class="fa-solid fa-equals mr-1 text-[10px]"></i> Estable
                            </span>
                        `;
                    }

                    const licNom = escapeHtml(row.nomenclatura || row.licenciatura || 'N/A');
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-emerald-50/20 transition-colors comp-row';
                    tr.setAttribute('data-search', `${row.director ?? ''} ${row.plantel ?? ''} ${row.licenciatura ?? ''} ${row.nomenclatura ?? ''}`.toLowerCase());

                    tr.innerHTML = `
                        <td class="px-5 py-3.5 font-bold text-slate-800">${escapeHtml(row.director || 'N/A')}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-600">${escapeHtml(row.plantel || 'N/A')}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-700 font-bold font-mono">${licNom}</td>
                        <td class="px-5 py-3.5 text-center font-mono text-xs font-semibold text-slate-700">${p1Val}</td>
                        <td class="px-5 py-3.5 text-center font-mono text-xs font-semibold text-slate-700">${p2Val}</td>
                        <td class="px-5 py-3.5 text-center font-mono text-xs ${diffClass}">${diffText}</td>
                        <td class="px-5 py-3.5 text-center">${trendBadge}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            const total = items.length;
            kpiTotal.textContent = total;
            kpiMejora.textContent = countMejora;
            kpiDescenso.textContent = countDescenso;
            kpiEstable.textContent = countEstable;

            if (total > 0) {
                kpiMejoraPct.textContent = `${((countMejora / total) * 100).toFixed(1)}% del total`;
                kpiDescensoPct.textContent = `${((countDescenso / total) * 100).toFixed(1)}% del total`;
                kpiEstablePct.textContent = `${((countEstable / total) * 100).toFixed(1)}% del total`;
            } else {
                kpiMejoraPct.textContent = '0%';
                kpiDescensoPct.textContent = '0%';
                kpiEstablePct.textContent = '0%';
            }

            initialState.classList.add('hidden');
            comparisonResults.classList.remove('hidden');

            showAlert('Comparativa calculada exitosamente.', 'success');

        } catch (error) {
            showAlert('Error de comunicación: ' + error.message, 'error');
        } finally {
            hideLoading();
            btnComparar.disabled = false;
        }
    });
});

function filterComparisonRows() {
    const filter = document.getElementById('filterComparisonInput').value.toLowerCase();
    const rows = document.querySelectorAll('.comp-row');
    rows.forEach(r => {
        const text = r.getAttribute('data-search') || '';
        r.style.display = text.includes(filter) ? '' : 'none';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
