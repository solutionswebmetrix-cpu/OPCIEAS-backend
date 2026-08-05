        </main>
    </div>
</div>

<div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width: 420px;">
        <div class="modal-header">
            <h3 class="modal-title" id="confirmTitle">Confirm Action</h3>
            <button class="modal-close" onclick="closeConfirm()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage" style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeConfirm()">Cancel</button>
            <button class="btn-danger" id="confirmOkBtn">Confirm</button>
        </div>
    </div>
</div>

<div id="alertContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 200; display: flex; flex-direction: column; gap: 0.5rem;"></div>

<script>
const API_BASE = '/api';
let _confirmCallback = null;
let _confirmOkBtn = null;

function showAlert(message, type = 'error', duration = 4000) {
    const container = document.getElementById('alertContainer');
    if (!container) return;
    const div = document.createElement('div');
    div.className = `alert alert-${type} show`;
    div.style.minWidth = '280px';
    div.style.margin = '0';
    div.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
    div.innerHTML = message;
    container.appendChild(div);
    setTimeout(() => {
        div.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        div.style.opacity = '0';
        div.style.transform = 'translateX(100%)';
        setTimeout(() => div.remove(), 300);
    }, duration);
}

function confirmDialog(title, message, callback) {
    _confirmCallback = callback;
    document.getElementById('confirmTitle').textContent = title || 'Confirm Action';
    document.getElementById('confirmMessage').textContent = message || 'Are you sure?';
    const overlay = document.getElementById('confirmModal');
    overlay.classList.add('active');
    if (!_confirmOkBtn) {
        _confirmOkBtn = document.getElementById('confirmOkBtn');
        _confirmOkBtn.addEventListener('click', () => {
            closeConfirm();
            if (typeof _confirmCallback === 'function') _confirmCallback();
        });
    }
}

function closeConfirm() {
    const overlay = document.getElementById('confirmModal');
    if (overlay) overlay.classList.remove('active');
    _confirmCallback = null;
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

function closeAllModals() {
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAllModals();
        closeConfirm();
    }
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            if (overlay.id === 'confirmModal') closeConfirm();
            else overlay.classList.remove('active');
        }
    });
});

async function apiFetch(endpoint, options = {}) {
    const url = endpoint.startsWith('http') ? endpoint : (API_BASE + endpoint);
    const opts = {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            ...(options.headers || {})
        },
        ...options
    };
    if (options.body && !(options.body instanceof FormData) && typeof options.body !== 'string') {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(options.body);
    }
    try {
        const res = await fetch(url, opts);
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch { data = { success: false, message: text || 'Invalid response from server' }; }
        if (res.status === 401 && !window.location.href.includes('login.php')) {
            showAlert('Session expired. Please login again.', 'error');
            setTimeout(() => window.location.href = 'login.php', 1200);
            return { success: false, unauthorized: true, ...data };
        }
        return data;
    } catch (err) {
        showAlert('Network error: ' + err.message, 'error');
        return { success: false, message: err.message };
    }
}

function apiGet(endpoint, params = {}) {
    const url = new URL(endpoint.startsWith('http') ? endpoint : (API_BASE + endpoint), window.location.origin);
    Object.keys(params).forEach(k => {
        if (params[k] !== null && params[k] !== undefined && params[k] !== '') {
            url.searchParams.append(k, params[k]);
        }
    });
    return apiFetch(url.toString(), { method: 'GET' });
}

function apiPost(endpoint, body = {}) {
    return apiFetch(endpoint, { method: 'POST', body });
}

function apiPut(endpoint, body = {}) {
    return apiFetch(endpoint, { method: 'PUT', body });
}

function apiDelete(endpoint) {
    return apiFetch(endpoint, { method: 'DELETE' });
}

async function handleLogout() {
    confirmDialog('Logout', 'Are you sure you want to logout?', async () => {
        await apiPost('/auth/logout.php');
        window.location.href = 'login.php';
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth <= 1024) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
    }
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

function statusBadge(status) {
    if (!status) return '';
    const cls = 'status-' + status.toLowerCase().replace(/\s+/g, '-');
    return `<span class="status-badge ${cls}">${escapeHtml(status)}</span>`;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
}

function renderPagination(containerId, pagination, onChange) {
    const container = document.getElementById(containerId);
    if (!container || !pagination) return;
    const { page, total_pages, total, limit } = pagination;
    const start = total === 0 ? 0 : ((page - 1) * limit) + 1;
    const end = Math.min(page * limit, total);
    let html = `<div class="pagination-info">Showing ${start}-${end} of ${total} entries</div><div class="pagination-buttons">`;
    html += `<button class="page-btn" onclick="(${onChange.toString()})(${page - 1})" ${page <= 1 ? 'disabled' : ''}>« Prev</button>`;
    const maxBtns = 5;
    let startP = Math.max(1, page - Math.floor(maxBtns / 2));
    let endP = startP + maxBtns - 1;
    if (endP > total_pages) { endP = total_pages; startP = Math.max(1, endP - maxBtns + 1); }
    for (let i = startP; i <= endP; i++) {
        html += `<button class="page-btn ${i === page ? 'active' : ''}" onclick="(${onChange.toString()})(${i})">${i}</button>`;
    }
    html += `<button class="page-btn" onclick="(${onChange.toString()})(${page + 1})" ${page >= total_pages ? 'disabled' : ''}>Next »</button>`;
    html += '</div>';
    container.innerHTML = html;
}
</script>
</body>
</html>
