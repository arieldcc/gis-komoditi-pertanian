import './bootstrap';
import 'bootstrap';
import $ from 'jquery';
import 'datatables.net-bs5';
import L from 'leaflet';
import Swal from 'sweetalert2';
import ApexCharts from 'apexcharts';

window.$ = $;
window.jQuery = $;
window.L = L;
window.ApexCharts = ApexCharts;

const datatableInstances = [];

document.addEventListener('DOMContentLoaded', () => {
    initializeCounters();
    initializeFlashAlerts();
    initializeServerDataTables();
    initializeRowActions();
    initializeDynamicSelects();
    initializeRealtimeDashboards();
    decorateButtonsWithIcons();

    document.querySelectorAll('[data-map-picker]').forEach((mapElement, index) => {
        initializeMapPicker(mapElement, index);
    });

    document.querySelectorAll('[data-map-filter-map]').forEach((mapElement, index) => {
        initializeFilteredMap(mapElement, index);
    });
});

function initializeCounters() {
    document.querySelectorAll('[data-counter-target]').forEach((el) => {
        const target = Number(el.getAttribute('data-counter-target') || 0);
        const suffix = el.getAttribute('data-counter-suffix') || '';
        let count = 0;
        const step = Math.max(1, Math.floor(target / 60));

        const timer = setInterval(() => {
            count += step;
            if (count >= target) {
                count = target;
                clearInterval(timer);
            }
            el.textContent = `${count.toLocaleString('id-ID')}${suffix}`;
        }, 18);
    });
}

function initializeFlashAlerts() {
    const flashSuccess = document.body.dataset.flashSuccess || '';
    const flashError = document.body.dataset.flashError || '';
    const validationErrors = parseJson(document.body.dataset.flashValidationErrors, []);

    if (flashSuccess) {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: flashSuccess,
            timer: 2200,
            showConfirmButton: false,
        });
        return;
    }

    if (flashError) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: flashError,
        });
        return;
    }

    if (validationErrors.length > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Validasi Gagal',
            html: `<ul class="text-start mb-0">${validationErrors.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`,
        });
    }
}

function initializeServerDataTables() {
    document.querySelectorAll('table[data-dt-server="true"]').forEach((table) => {
        const source = table.dataset.dtSource;
        if (!source) {
            return;
        }

        const columns = Array.from(table.querySelectorAll('thead th')).map((th) => {
            const columnName = th.dataset.col || '';

            return {
                data: columnName,
                name: columnName,
                orderable: th.dataset.orderable !== 'false',
                searchable: th.dataset.searchable !== 'false',
            };
        });

        const dt = $(table).DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: `/datatable/${source}`,
                type: 'GET',
            },
            columns,
            pageLength: Number.parseInt(table.dataset.dtPageLength || '10', 10),
            language: {
                processing: 'Memuat data...',
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    first: 'Awal',
                    last: 'Akhir',
                    next: 'Berikut',
                    previous: 'Sebelumnya',
                },
            },
        });

        dt.on('draw', () => {
            decorateButtonsWithIcons(table.closest('.panel-card') || document);
        });

        const autoRefreshMs = Number.parseInt(table.dataset.dtAutoRefresh || '0', 10);
        if (Number.isFinite(autoRefreshMs) && autoRefreshMs >= 5000) {
            window.setInterval(() => {
                if (document.body.contains(table)) {
                    dt.ajax.reload(null, false);
                }
            }, autoRefreshMs);
        }

        datatableInstances.push(dt);
    });
}

function initializeRowActions() {
    document.addEventListener('click', async (event) => {
        const viewButton = event.target.closest('.js-row-view');
        if (viewButton) {
            const payload = decodePayload(viewButton.dataset.row);
            if (payload) {
                openViewDialog(payload);
            }
            return;
        }

        const editButton = event.target.closest('.js-row-edit');
        if (editButton) {
            const payload = decodePayload(editButton.dataset.row);
            if (payload) {
                await openEditDialog(payload);
            }
            return;
        }

        const deleteButton = event.target.closest('.js-row-delete');
        if (deleteButton) {
            const payload = decodePayload(deleteButton.dataset.row);
            if (payload) {
                await runDeleteAction(payload);
            }
        }
    });
}

function openViewDialog(payload) {
    const responseFormId = `dt-view-response-${Math.random().toString(36).slice(2)}`;
    Swal.fire({
        title: payload.title || 'Detail Data',
        width: 760,
        showConfirmButton: true,
        confirmButtonText: 'Tutup',
        html: buildViewHtml(payload, responseFormId),
        didOpen: (el) => {
            setupDialogMap(el, payload, false);
            setupViewResponseForm(el, payload, responseFormId);
        },
    });
}

async function openEditDialog(payload) {
    const editConfig = payload.edit || null;
    if (!editConfig || !editConfig.url || !Array.isArray(editConfig.fields) || editConfig.fields.length === 0) {
        await Swal.fire({
            icon: 'info',
            title: 'Edit Belum Tersedia',
            text: 'Aksi edit tidak tersedia untuk data ini.',
        });
        return;
    }

    const formId = `dt-edit-form-${Math.random().toString(36).slice(2)}`;
    const result = await Swal.fire({
        title: `Edit ${payload.title || 'Data'}`,
        width: 860,
        showCancelButton: true,
        confirmButtonText: 'Simpan Perubahan',
        cancelButtonText: 'Batal',
        html: buildEditHtml(payload, formId),
        didOpen: (el) => {
            setupDialogMap(el, payload, true);
        },
        preConfirm: async () => {
            const form = document.getElementById(formId);
            if (!form) {
                Swal.showValidationMessage('Form edit tidak ditemukan.');
                return false;
            }

            try {
                await submitEditForm(form, editConfig);
                return true;
            } catch (error) {
                Swal.showValidationMessage(error.message || 'Gagal menyimpan data.');
                return false;
            }
        },
    });

    if (result.isConfirmed) {
        await Swal.fire({
            icon: 'success',
            title: 'Data Diperbarui',
            timer: 1800,
            showConfirmButton: false,
        });
        reloadAllDataTables();
    }
}

async function runDeleteAction(payload) {
    const deleteConfig = payload.delete || null;
    if (!deleteConfig || !deleteConfig.url) {
        await Swal.fire({
            icon: 'info',
            title: 'Delete Belum Tersedia',
            text: 'Aksi hapus tidak tersedia untuk data ini.',
        });
        return;
    }

    const confirmed = await Swal.fire({
        icon: 'warning',
        title: 'Konfirmasi Hapus',
        text: deleteConfig.label || 'Yakin menghapus data ini?',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#c0392b',
    });

    if (!confirmed.isConfirmed) {
        return;
    }

    try {
        await fetchWithMethod(deleteConfig.url, deleteConfig.method || 'DELETE', {});
        await Swal.fire({
            icon: 'success',
            title: 'Data Dihapus',
            timer: 1600,
            showConfirmButton: false,
        });
        reloadAllDataTables();
    } catch (error) {
        await Swal.fire({
            icon: 'error',
            title: 'Gagal Menghapus',
            text: error.message || 'Terjadi kesalahan saat menghapus data.',
        });
    }
}

async function submitEditForm(form, editConfig) {
    const formData = new FormData(form);
    const payload = new FormData();

    for (const [key, value] of formData.entries()) {
        if (value instanceof File && value.size === 0) {
            continue;
        }

        const isPasswordField = key === 'password'
            || key === 'password_confirmation'
            || key.endsWith('_password')
            || key.endsWith('_password_confirmation');
        if (isPasswordField && String(value).trim() === '') {
            continue;
        }

        payload.append(key, value);
    }

    await fetchWithMethod(editConfig.url, editConfig.method || 'PUT', payload);
}

async function fetchWithMethod(url, method, fields) {
    let body = null;
    if (fields instanceof FormData) {
        body = fields;
        body.set('_method', method.toUpperCase());
    } else {
        const payload = new URLSearchParams();
        payload.append('_method', method.toUpperCase());
        Object.entries(fields || {}).forEach(([key, value]) => {
            payload.append(key, value ?? '');
        });
        body = payload;
    }

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    });

    if (response.ok) {
        return true;
    }

    let message = 'Terjadi kesalahan server.';
    try {
        const body = await response.json();
        if (body.message) {
            message = body.message;
        } else if (body.errors) {
            const first = Object.values(body.errors)[0];
            if (Array.isArray(first) && first[0]) {
                message = first[0];
            }
        }
    } catch (_error) {
        // ignore json parse error
    }

    throw new Error(message);
}

function reloadAllDataTables() {
    if (datatableInstances.length === 0) {
        window.location.reload();
        return;
    }

    datatableInstances.forEach((dt) => {
        dt.ajax.reload(null, false);
    });

    refreshDynamicSelects();
}

function initializeDynamicSelects() {
    refreshDynamicSelects();
}

function initializeRealtimeDashboards() {
    document.querySelectorAll('[data-dashboard-endpoint]').forEach((dashboardEl, index) => {
        setupRealtimeDashboard(dashboardEl, index);
    });
}

function setupRealtimeDashboard(dashboardEl, index) {
    if (dashboardEl.dataset.dashboardReady === '1') {
        return;
    }

    const endpoint = dashboardEl.dataset.dashboardEndpoint;
    if (!endpoint) {
        return;
    }

    dashboardEl.dataset.dashboardReady = '1';

    const pieEl = dashboardEl.querySelector('[data-chart="pie"]');
    const barEl = dashboardEl.querySelector('[data-chart="bar"]');
    const candlestickEl = dashboardEl.querySelector('[data-chart="candlestick"]');
    const cards = Array.from(dashboardEl.querySelectorAll('[data-card-key]'));
    const activityEl = dashboardEl.querySelector('[data-dashboard-activity]');
    const updatedAtEl = dashboardEl.querySelector('[data-dashboard-updated-at]');
    const refreshMs = Math.max(5000, Number.parseInt(dashboardEl.dataset.dashboardRefreshMs || '10000', 10));

    const charts = {
        pie: pieEl ? createPieChart(pieEl, index) : null,
        bar: barEl ? createBarChart(barEl, index) : null,
        candlestick: candlestickEl ? createCandlestickChart(candlestickEl, index) : null,
    };

    let isFetching = false;

    const applyPayload = (payload) => {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        applyDashboardCards(cards, payload.cards || []);
        applyPieChart(charts.pie, payload.pie || {});
        applyBarChart(charts.bar, payload.bar || {});
        applyCandlestickChart(charts.candlestick, payload.candlestick || {});
        applyActivity(activityEl, payload.activity || []);

        if (updatedAtEl) {
            const text = payload.updated_at ? `Terakhir update: ${payload.updated_at}` : 'Terakhir update: -';
            updatedAtEl.textContent = text;
        }
    };

    const fetchDashboardData = async () => {
        if (isFetching) {
            return;
        }

        isFetching = true;
        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            applyPayload(payload);
        } catch (_error) {
            // Keep last dashboard state if realtime fetch fails.
        } finally {
            isFetching = false;
        }
    };

    fetchDashboardData();

    window.setInterval(() => {
        if (!document.body.contains(dashboardEl)) {
            return;
        }
        fetchDashboardData();
    }, refreshMs);
}

function createPieChart(target, index) {
    const chart = new ApexCharts(target, {
        chart: {
            type: 'pie',
            height: 320,
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 450 },
        },
        labels: [],
        series: [],
        legend: {
            position: 'bottom',
            fontSize: '12px',
        },
        stroke: {
            width: 1,
            colors: ['#ffffff'],
        },
        dataLabels: {
            enabled: true,
            formatter: (value) => `${Number(value).toFixed(0)}%`,
        },
        colors: ['#1e7b4f', '#8ebf3f', '#d59b30', '#e35d6a', '#7a8699'],
        noData: {
            text: 'Belum ada data',
        },
    });
    chart.render();
    target.dataset.chartInstance = `pie-${index}`;
    return chart;
}

function createBarChart(target, index) {
    const chart = new ApexCharts(target, {
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 450 },
        },
        series: [],
        plotOptions: {
            bar: {
                borderRadius: 5,
                horizontal: false,
                columnWidth: '56%',
            },
        },
        xaxis: {
            categories: [],
            labels: {
                rotate: -35,
            },
        },
        yaxis: {
            labels: {
                formatter: (value) => Number(value || 0).toLocaleString('id-ID'),
            },
        },
        colors: ['#1e7b4f'],
        dataLabels: {
            enabled: false,
        },
        grid: {
            borderColor: '#edf1e7',
        },
        noData: {
            text: 'Belum ada data',
        },
    });
    chart.render();
    target.dataset.chartInstance = `bar-${index}`;
    return chart;
}

function createCandlestickChart(target, index) {
    const chart = new ApexCharts(target, {
        chart: {
            type: 'candlestick',
            height: 360,
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 450 },
        },
        series: [{
            data: [],
        }],
        xaxis: {
            type: 'category',
            labels: {
                rotate: -30,
            },
        },
        yaxis: {
            tooltip: {
                enabled: true,
            },
            labels: {
                formatter: (value) => Number(value || 0).toLocaleString('id-ID'),
            },
        },
        plotOptions: {
            candlestick: {
                colors: {
                    upward: '#1e7b4f',
                    downward: '#d94e5d',
                },
                wick: {
                    useFillColor: true,
                },
            },
        },
        grid: {
            borderColor: '#edf1e7',
        },
        noData: {
            text: 'Belum ada data',
        },
    });
    chart.render();
    target.dataset.chartInstance = `candlestick-${index}`;
    return chart;
}

function applyDashboardCards(cardElements, cards) {
    if (!Array.isArray(cardElements) || cardElements.length === 0) {
        return;
    }

    const map = new Map();
    (Array.isArray(cards) ? cards : []).forEach((card) => {
        if (!card || typeof card !== 'object') {
            return;
        }
        map.set(String(card.key || ''), card);
    });

    cardElements.forEach((el) => {
        const key = String(el.dataset.cardKey || '');
        if (!map.has(key)) {
            return;
        }

        const card = map.get(key);
        const valueEl = el.querySelector('[data-card-value]');
        const suffixEl = el.querySelector('[data-card-suffix]');
        if (valueEl) {
            valueEl.textContent = formatCardValue(card.value);
        }
        if (suffixEl) {
            suffixEl.textContent = String(card.suffix || '');
        }
    });
}

function applyPieChart(chart, pie) {
    if (!chart) {
        return;
    }

    const labels = Array.isArray(pie.labels) ? pie.labels : [];
    const series = Array.isArray(pie.series) ? pie.series.map((x) => Number(x) || 0) : [];
    chart.updateOptions({
        labels,
    }, false, true);
    chart.updateSeries(series, true);
}

function applyBarChart(chart, bar) {
    if (!chart) {
        return;
    }

    const categories = Array.isArray(bar.categories) ? bar.categories : [];
    const series = Array.isArray(bar.series) ? bar.series : [];
    chart.updateOptions({
        xaxis: { categories },
    }, false, true);
    chart.updateSeries(series, true);
}

function applyCandlestickChart(chart, candlestick) {
    if (!chart) {
        return;
    }

    const series = Array.isArray(candlestick.series) ? candlestick.series : [{ data: [] }];
    chart.updateSeries(series, true);
}

function applyActivity(activityEl, items) {
    if (!activityEl) {
        return;
    }

    const rows = Array.isArray(items) ? items : [];
    if (rows.length === 0) {
        activityEl.innerHTML = '<div class="text-muted small">Belum ada aktivitas laporan terbaru.</div>';
        return;
    }

    activityEl.innerHTML = rows.map((item) => {
        const status = String(item.status || 'draft').toLowerCase();
        const badgeClass = resolveDashboardStatusClass(status);
        return `
            <div class="dashboard-activity-item">
                <div class="fw-semibold">${escapeHtml(item.title || '-')}</div>
                <small class="text-muted d-block">${escapeHtml(item.subtitle || '-')}</small>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="badge rounded-pill ${badgeClass}">${escapeHtml(status.toUpperCase())}</span>
                    <small class="text-muted">${escapeHtml(item.time || '-')}</small>
                </div>
            </div>
        `;
    }).join('');
}

function resolveDashboardStatusClass(status) {
    if (status === 'menunggu') {
        return 'dashboard-status-menunggu';
    }
    if (status === 'revisi') {
        return 'dashboard-status-revisi';
    }
    if (status === 'disetujui') {
        return 'dashboard-status-disetujui';
    }
    if (status === 'ditolak') {
        return 'dashboard-status-ditolak';
    }
    return 'dashboard-status-draft';
}

function formatCardValue(value) {
    if (typeof value === 'number') {
        if (Number.isInteger(value)) {
            return value.toLocaleString('id-ID');
        }
        return value.toLocaleString('id-ID', {
            maximumFractionDigits: 2,
        });
    }

    if (typeof value === 'string' && value !== '' && !Number.isNaN(Number(value))) {
        const n = Number(value);
        if (Number.isInteger(n)) {
            return n.toLocaleString('id-ID');
        }
        return n.toLocaleString('id-ID', {
            maximumFractionDigits: 2,
        });
    }

    return String(value ?? '-');
}

async function refreshDynamicSelects() {
    const targets = Array.from(document.querySelectorAll('select[data-dynamic-source-url]'));
    if (targets.length === 0) {
        return;
    }

    await Promise.all(targets.map((select) => refreshDynamicSelect(select)));
}

async function refreshDynamicSelect(select) {
    const url = select.dataset.dynamicSourceUrl;
    if (!url) {
        return;
    }

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const rows = await response.json();
        if (!Array.isArray(rows)) {
            return;
        }

        const previousValue = String(select.value || '');
        const placeholder = select.dataset.placeholder || 'Pilih data';
        const options = [{
            value: '',
            label: placeholder,
        }, ...rows.map((row) => ({
            value: String(row.value ?? row.id ?? ''),
            label: String(row.label ?? row.nama_kecamatan ?? row.name ?? ''),
        }))];

        select.innerHTML = '';
        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            select.appendChild(option);
        });

        const hasPrevious = options.some((item) => item.value === previousValue && previousValue !== '');
        select.value = hasPrevious ? previousValue : '';
    } catch (_error) {
        // silent fallback: keep current options on fetch failure
    }
}

function decorateButtonsWithIcons(root = document) {
    const targets = root.querySelectorAll('button.btn, a.btn');
    targets.forEach((button) => {
        if (button.dataset.iconized === '1') {
            return;
        }

        if (button.querySelector('i.bi')) {
            button.dataset.iconized = '1';
            return;
        }

        const iconClass = resolveButtonIcon(button);
        if (!iconClass) {
            return;
        }

        const icon = document.createElement('i');
        icon.className = `bi ${iconClass} me-1`;
        button.prepend(icon);
        button.dataset.iconized = '1';
    });
}

function resolveButtonIcon(button) {
    const text = (button.textContent || '').trim().toLowerCase();

    if (text.includes('view') || text.includes('detail') || text.includes('lihat')) {
        return 'bi-eye';
    }

    if (text.includes('edit') || text.includes('ubah') || text.includes('update')) {
        return 'bi-pencil-square';
    }

    if (text.includes('hapus') || text.includes('delete')) {
        return 'bi-trash';
    }

    if (text.includes('simpan') || text.includes('save')) {
        return 'bi-save';
    }

    if (text.includes('tambah') || text.includes('create') || text.includes('baru')) {
        return 'bi-plus-circle';
    }

    if (text.includes('filter') || text.includes('cari') || text.includes('search')) {
        return 'bi-funnel';
    }

    if (text.includes('unduh') || text.includes('download') || text.includes('export')) {
        return 'bi-download';
    }

    if (text.includes('logout') || text.includes('keluar')) {
        return 'bi-box-arrow-right';
    }

    if (text.includes('profile')) {
        return 'bi-person-circle';
    }

    if (text.includes('menu')) {
        return 'bi-list';
    }

    if (text.includes('landing') || text.includes('beranda') || text.includes('home')) {
        return 'bi-house-door';
    }

    if (button.classList.contains('btn-danger') || button.classList.contains('btn-outline-danger')) {
        return 'bi-trash';
    }

    if (button.classList.contains('btn-primary') || button.classList.contains('btn-outline-primary')) {
        return 'bi-pencil-square';
    }

    if (button.classList.contains('btn-success') || button.classList.contains('btn-outline-success')) {
        return 'bi-check2-circle';
    }

    return null;
}

function buildViewHtml(payload, responseFormId = '') {
    const fields = Array.isArray(payload.fields) ? payload.fields : [];
    const fieldRows = fields.map((item) => (
        `<tr><th class="text-nowrap pe-3">${escapeHtml(item.label || '-')}</th><td>${escapeHtml(item.value ?? '-')}</td></tr>`
    )).join('');

    const imageHtml = payload.image_url
        ? `<div class="mb-3"><img src="${escapeHtml(payload.image_url)}" alt="Foto Data" class="img-fluid rounded border" style="max-height:240px;object-fit:cover;"></div>`
        : '';

    const mapHtml = payload.spatial
        ? '<div class="sig-modal-map mt-3" data-dialog-map></div>'
        : '';

    const responseHtml = payload.response_form
        ? buildViewResponseHtml(payload.response_form, responseFormId)
        : '';

    return `
        ${imageHtml}
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <tbody>${fieldRows || '<tr><td class="text-muted">Tidak ada detail data.</td></tr>'}</tbody>
            </table>
        </div>
        ${mapHtml}
        ${responseHtml}
    `;
}

function buildViewResponseHtml(responseConfig, formId) {
    if (!responseConfig || !responseConfig.url || !Array.isArray(responseConfig.fields) || responseConfig.fields.length === 0) {
        return '';
    }

    const fieldsHtml = responseConfig.fields.map((field) => buildEditInput(field)).join('');

    return `
        <div class="border rounded p-3 mt-3">
            <h6 class="mb-2">Respon Verifikasi</h6>
            <form id="${formId}" class="text-start">
                <div class="row g-2">${fieldsHtml}</div>
                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-success btn-sm">Simpan Respon</button>
                </div>
            </form>
        </div>
    `;
}

function setupViewResponseForm(container, payload, formId) {
    const responseConfig = payload?.response_form || null;
    if (!responseConfig || !responseConfig.url) {
        return;
    }

    const form = container.querySelector(`#${CSS.escape(formId)}`);
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            await submitEditForm(form, responseConfig);
            await Swal.fire({
                icon: 'success',
                title: 'Respon Tersimpan',
                timer: 1700,
                showConfirmButton: false,
            });
            reloadAllDataTables();
            Swal.close();
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan Respon',
                text: error.message || 'Terjadi kesalahan saat menyimpan respon verifikasi.',
            });
        }
    });
}

function buildEditHtml(payload, formId) {
    const editConfig = payload.edit || {};
    const fields = Array.isArray(editConfig.fields) ? editConfig.fields : [];
    const fieldInputs = fields.map((field) => buildEditInput(field)).join('');
    const imageHtml = payload.image_url
        ? `<div class="mb-3"><img src="${escapeHtml(payload.image_url)}" alt="Foto Data" class="img-fluid rounded border" style="max-height:220px;object-fit:cover;"></div>`
        : '';
    const mapHtml = editConfig.spatial
        ? '<div class="sig-modal-map mt-3" data-dialog-map></div><small class="text-muted">Klik peta untuk update koordinat.</small>'
        : '';

    return `
        ${imageHtml}
        <form id="${formId}" class="text-start">
            <div class="row g-2">${fieldInputs}</div>
            ${mapHtml}
        </form>
    `;
}

function buildEditInput(field) {
    const name = escapeHtml(field.name || '');
    const label = escapeHtml(field.label || field.name || '');
    const required = field.required ? 'required' : '';
    const readonly = field.readonly ? 'readonly' : '';
    const value = field.value ?? '';
    const type = field.type || 'text';
    const step = field.step ? `step="${escapeHtml(field.step)}"` : '';
    const inputClass = 'form-control form-control-sm';

    if (type === 'textarea') {
        return `
            <div class="col-md-6">
                <label class="form-label form-label-sm">${label}</label>
                <textarea class="${inputClass}" name="${name}" rows="2" ${required} ${readonly}>${escapeHtml(value)}</textarea>
            </div>
        `;
    }

    if (type === 'select') {
        const options = Array.isArray(field.options) ? field.options : [];
        const optionHtml = options.map((option) => {
            const optionValue = String(option.value ?? '');
            const selected = String(value ?? '') === optionValue ? 'selected' : '';
            return `<option value="${escapeHtml(optionValue)}" ${selected}>${escapeHtml(option.label ?? optionValue)}</option>`;
        }).join('');

        return `
            <div class="col-md-6">
                <label class="form-label form-label-sm">${label}</label>
                <select class="form-select form-select-sm" name="${name}" ${required}>
                    ${optionHtml}
                </select>
            </div>
        `;
    }

    if (type === 'file') {
        return `
            <div class="col-md-6">
                <label class="form-label form-label-sm">${label}</label>
                <input type="file" class="${inputClass}" name="${name}" ${required}>
            </div>
        `;
    }

    return `
        <div class="col-md-6">
            <label class="form-label form-label-sm">${label}</label>
            <input type="${escapeHtml(type)}" class="${inputClass}" name="${name}" value="${escapeHtml(value)}" ${step} ${required} ${readonly}>
        </div>
    `;
}

function setupDialogMap(container, payload, editable) {
    if (!payload?.spatial) {
        return;
    }

    const mapEl = container.querySelector('[data-dialog-map]');
    if (!mapEl) {
        return;
    }

    const lat = Number.parseFloat(payload.spatial.lat);
    const lng = Number.parseFloat(payload.spatial.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }

    const map = L.map(mapEl).setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const spatialStyle = normalizeInlineStyle(payload.spatial.style) || {
        icon_symbol: 'T',
        icon_color: '#14532d',
        bg_color: '#dcfce7',
        size: 28,
    };

    const marker = L.marker([lat, lng], {
        icon: buildIcon(spatialStyle),
    }).addTo(map);
    marker.bindPopup(escapeHtml(payload.spatial.label || 'Titik Data')).openPopup();
    setTimeout(() => map.invalidateSize(), 120);

    if (!editable || !payload.edit?.spatial) {
        return;
    }

    const latField = payload.edit.spatial.lat_field;
    const lngField = payload.edit.spatial.lng_field;
    const addressField = payload.edit.spatial.address_field;

    map.on('click', async (event) => {
        const pointLat = event.latlng.lat;
        const pointLng = event.latlng.lng;

        marker.setLatLng([pointLat, pointLng]);

        if (latField) {
            const input = container.querySelector(`[name="${CSS.escape(latField)}"]`);
            if (input) {
                input.value = pointLat.toFixed(7);
            }
        }

        if (lngField) {
            const input = container.querySelector(`[name="${CSS.escape(lngField)}"]`);
            if (input) {
                input.value = pointLng.toFixed(7);
            }
        }

        if (addressField) {
            const input = container.querySelector(`[name="${CSS.escape(addressField)}"]`);
            if (input) {
                const address = await reverseGeocode(pointLat, pointLng);
                if (address) {
                    input.value = address;
                }
            }
        }
    });
}

function initializeMapPicker(mapElement, index) {
    if (mapElement.dataset.mapReady === '1') {
        return;
    }

    mapElement.dataset.mapReady = '1';
    const centerLat = Number.parseFloat(mapElement.dataset.centerLat || '-1.35');
    const centerLng = Number.parseFloat(mapElement.dataset.centerLng || '123.25');
    const zoom = Number.parseInt(mapElement.dataset.zoom || '9', 10);
    const map = L.map(mapElement).setView([centerLat, centerLng], Number.isFinite(zoom) ? zoom : 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const styleMap = parseJson(mapElement.dataset.styles, {});
    const markerItems = parseJson(mapElement.dataset.markers, []);
    const staticMarkers = [];

    markerItems.forEach((item) => {
        const marker = renderMarker(map, item, styleMap);
        if (marker) {
            staticMarkers.push(marker);
        }
    });

    if (staticMarkers.length > 0 && mapElement.dataset.fitMarkers !== 'false') {
        const bounds = L.featureGroup(staticMarkers).getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds.pad(0.15));
        }
    }

    const latInput = resolveField(mapElement, mapElement.dataset.latInput);
    const lngInput = resolveField(mapElement, mapElement.dataset.lngInput);
    const addressInput = resolveField(mapElement, mapElement.dataset.addressInput);
    const addressText = resolveTextElement(mapElement, mapElement.dataset.addressText);
    const clickStyleCode = mapElement.dataset.clickStyle || 'entity:komoditas_default';
    let activeSelectionMarker = null;
    const isSelectionEnabled = Boolean(latInput || lngInput || addressInput || addressText);

    const updateSelection = (lat, lng, addressLabel = '') => {
        if (latInput) {
            latInput.value = Number(lat).toFixed(7);
        }

        if (lngInput) {
            lngInput.value = Number(lng).toFixed(7);
        }

        if (addressInput && addressLabel) {
            addressInput.value = addressLabel;
        }

        if (addressText) {
            const renderedAddress = addressLabel || 'Alamat belum ditemukan, silakan koreksi manual.';
            const statusText = `Lat: ${Number(lat).toFixed(7)} | Lng: ${Number(lng).toFixed(7)} | ${renderedAddress}`;
            if ('value' in addressText) {
                addressText.value = renderedAddress;
            } else {
                addressText.textContent = statusText;
            }
        }

        const style = getStyle(styleMap, clickStyleCode);
        if (!activeSelectionMarker) {
            activeSelectionMarker = L.marker([lat, lng], {
                icon: buildIcon(style),
            }).addTo(map);
        } else {
            activeSelectionMarker.setLatLng([lat, lng]);
            activeSelectionMarker.setIcon(buildIcon(style));
        }
    };

    if (isSelectionEnabled) {
        const initialLat = latInput ? Number.parseFloat(latInput.value) : NaN;
        const initialLng = lngInput ? Number.parseFloat(lngInput.value) : NaN;
        if (Number.isFinite(initialLat) && Number.isFinite(initialLng)) {
            updateSelection(initialLat, initialLng, addressInput ? addressInput.value : '');
        }

        map.on('click', async (event) => {
            const { lat, lng } = event.latlng;
            const currentAddress = addressInput ? addressInput.value : '';
            updateSelection(lat, lng, currentAddress);

            const address = await reverseGeocode(lat, lng);
            if (address) {
                updateSelection(lat, lng, address);
            }
        });
    }
}

function initializeFilteredMap(mapElement, index) {
    if (mapElement.dataset.mapFilterReady === '1') {
        return;
    }

    mapElement.dataset.mapFilterReady = '1';

    const centerLat = Number.parseFloat(mapElement.dataset.centerLat || '-1.35');
    const centerLng = Number.parseFloat(mapElement.dataset.centerLng || '123.25');
    const zoom = Number.parseInt(mapElement.dataset.zoom || '9', 10);
    const defaultZoom = Number.isFinite(zoom) ? zoom : 9;
    const map = L.map(mapElement).setView([centerLat, centerLng], defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const styleMap = parseJson(mapElement.dataset.styles, {});
    const markerItems = parseJson(mapElement.dataset.markers, []);
    const filterField = mapElement.dataset.filterField || 'komoditas_id';
    const filterSelect = resolveField(mapElement, mapElement.dataset.filterSelect);
    const filterCountText = resolveTextElement(mapElement, mapElement.dataset.filterCount);

    const markerRefs = markerItems.map((item) => {
        const marker = renderMarker(map, item, styleMap);
        if (!marker) {
            return null;
        }

        return { marker, item };
    }).filter(Boolean);

    const setFilterCountText = (visibleTotal, selectedLabel = '') => {
        if (!filterCountText) {
            return;
        }

        if (visibleTotal <= 0) {
            filterCountText.textContent = selectedLabel
                ? `Tidak ada titik untuk komoditas ${selectedLabel}.`
                : 'Tidak ada titik komoditas yang dapat ditampilkan.';
            return;
        }

        filterCountText.textContent = selectedLabel
            ? `Menampilkan ${visibleTotal} titik komoditas ${selectedLabel}.`
            : `Menampilkan ${visibleTotal} titik komoditas.`;
    };

    const applyFilter = () => {
        const selectedValue = filterSelect ? String(filterSelect.value || '') : '';
        const selectedLabel = filterSelect && selectedValue !== ''
            ? String(filterSelect.options[filterSelect.selectedIndex]?.text || '')
            : '';

        const visibleMarkers = [];

        markerRefs.forEach((ref) => {
            const sourceValue = ref.item?.[filterField];
            const values = Array.isArray(sourceValue)
                ? sourceValue.map((x) => String(x))
                : [String(sourceValue ?? '')];
            const isVisible = selectedValue === '' || values.includes(selectedValue);

            if (isVisible) {
                if (!map.hasLayer(ref.marker)) {
                    ref.marker.addTo(map);
                }
                visibleMarkers.push(ref.marker);
            } else if (map.hasLayer(ref.marker)) {
                map.removeLayer(ref.marker);
            }
        });

        if (visibleMarkers.length > 0) {
            const bounds = L.featureGroup(visibleMarkers).getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds.pad(0.15));
            }
            setFilterCountText(visibleMarkers.length, selectedLabel);
        } else {
            map.setView([centerLat, centerLng], defaultZoom);
            setFilterCountText(0, selectedLabel);
        }
    };

    if (filterSelect) {
        filterSelect.addEventListener('change', applyFilter);
    }

    applyFilter();
    setTimeout(() => map.invalidateSize(), 120);
}

function renderMarker(map, item, styleMap) {
    const lat = Number.parseFloat(item.lat);
    const lng = Number.parseFloat(item.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
    }

    const style = normalizeInlineStyle(item.style) || getStyle(styleMap, item.style_code);
    const marker = L.marker([lat, lng], {
        icon: buildIcon(style),
    }).addTo(map);

    marker.bindPopup(buildMarkerPopup(item));

    return marker;
}

function buildMarkerPopup(item) {
    const title = escapeHtml(item.title || 'Titik Lokasi');
    const description = item.description ? `<div class="small text-muted mb-2">${escapeHtml(item.description)}</div>` : '';
    const imageHtml = item.image_url
        ? `<img src="${escapeHtml(item.image_url)}" alt="Foto Titik" style="max-width:220px;max-height:140px;object-fit:cover;" class="rounded border mb-2">`
        : '';
    const fields = Array.isArray(item.fields) ? item.fields : [];
    const fieldHtml = fields.length > 0
        ? `<div class="small">${fields.map((f) => `<div><strong>${escapeHtml(f.label || '-')}:</strong> ${escapeHtml(f.value ?? '-')}</div>`).join('')}</div>`
        : '';

    return `<div class="sig-popup"><strong>${title}</strong>${description}${imageHtml}${fieldHtml}</div>`;
}

function resolveField(mapElement, pointer) {
    if (!pointer) {
        return null;
    }

    if (pointer.startsWith('#') || pointer.startsWith('.')) {
        return document.querySelector(pointer);
    }

    const localForm = mapElement.closest('form');
    if (localForm) {
        const inFormById = localForm.querySelector(`#${pointer}`);
        if (inFormById) {
            return inFormById;
        }

        const inFormByName = localForm.querySelector(`[name="${pointer}"]`);
        if (inFormByName) {
            return inFormByName;
        }
    }

    return document.getElementById(pointer) || document.querySelector(`[name="${pointer}"]`);
}

function resolveTextElement(mapElement, pointer) {
    if (!pointer) {
        return null;
    }

    if (pointer.startsWith('#') || pointer.startsWith('.')) {
        return document.querySelector(pointer);
    }

    const localForm = mapElement.closest('form');
    if (localForm) {
        const inFormById = localForm.querySelector(`#${pointer}`);
        if (inFormById) {
            return inFormById;
        }
    }

    return document.getElementById(pointer);
}

function getStyle(styleMap, styleCode) {
    return styleMap[styleCode]
        || styleMap['entity:komoditas_default']
        || {
            icon_symbol: 'C',
            icon_color: '#854d0e',
            bg_color: '#fef9c3',
            size: 28,
        };
}

function normalizeInlineStyle(style) {
    if (!style || typeof style !== 'object') {
        return null;
    }

    if (!style.icon_symbol) {
        return null;
    }

    return {
        icon_symbol: style.icon_symbol,
        icon_color: style.icon_color,
        bg_color: style.bg_color,
        size: style.size,
    };
}

function buildIcon(style) {
    const size = Number.parseInt(style.size, 10) || 28;
    const symbol = escapeHtml(String(style.icon_symbol || 'C'));
    const color = style.icon_color || '#14532d';
    const bgColor = style.bg_color || '#ffffff';

    return L.divIcon({
        className: 'sig-map-div-icon',
        html: `<div class="sig-map-marker" style="--sig-size:${size}px;--sig-icon-color:${color};--sig-bg-color:${bgColor};"><span>${symbol}</span></div>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size],
        popupAnchor: [0, -size],
    });
}

async function reverseGeocode(lat, lng) {
    try {
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.searchParams.set('format', 'jsonv2');
        url.searchParams.set('lat', String(lat));
        url.searchParams.set('lon', String(lng));
        url.searchParams.set('accept-language', 'id');
        url.searchParams.set('addressdetails', '1');

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return '';
        }

        const result = await response.json();
        return result.display_name || '';
    } catch (_error) {
        return '';
    }
}

function decodePayload(raw) {
    if (!raw) {
        return null;
    }

    try {
        const bytes = Uint8Array.from(atob(raw), (x) => x.charCodeAt(0));
        const json = new TextDecoder().decode(bytes);
        return JSON.parse(json);
    } catch (_error) {
        return null;
    }
}

function parseJson(raw, fallback) {
    if (!raw) {
        return fallback;
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return fallback;
    }
}

function getCsrfToken() {
    const tokenEl = document.querySelector('meta[name="csrf-token"]');
    return tokenEl ? tokenEl.getAttribute('content') || '' : '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
