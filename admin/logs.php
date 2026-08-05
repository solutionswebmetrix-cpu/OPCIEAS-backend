<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📜 Activity Logs</h2>
        <div style="display:flex;gap:0.5rem;">
            <span class="btn-secondary btn-sm" onclick="loadLogs()">🔄 Refresh</span>
            <button class="btn-secondary btn-sm" onclick="exportLogs()">⬇️ Export CSV</button>
        </div>
    </div>

    <div class="filters-bar">
        <div class="search-input">
            <input type="text" id="searchInput" class="form-input" placeholder="Search logs..." onkeypress="if(event.key==='Enter')loadLogs(1)">
        </div>
        <select id="moduleFilter" class="form-select" style="max-width:200px;" onchange="loadLogs(1)">
            <option value="">All Modules</option>
            <option value="auth">Auth</option>
            <option value="seller">Seller</option>
            <option value="buyer">Buyer</option>
            <option value="product">Product</option>
            <option value="order">Order</option>
            <option value="rfq">RFQ</option>
            <option value="contact">Contact</option>
            <option value="admin">Admin</option>
            <option value="system">System</option>
        </select>
        <button class="btn-secondary btn-sm" onclick="loadLogs(1)">🔍 Apply</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>Admin</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody id="logsTbody">
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading logs...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>

<script>
let currentPage = 1;

async function loadLogs(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const module = document.getElementById('moduleFilter').value;
    const tbody = document.getElementById('logsTbody');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading logs...</td></tr>';
    const result = await apiGet('/admin/activity_logs/list.php', { page, limit: 15, search, module });
    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;color:#fca5a5;">❌ ${escapeHtml(result.message || 'Failed to load')}</td></tr>`;
        return;
    }
    const data = result.data;
    const items = data.items || [];
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No activity logs found.</td></tr>';
    } else {
        tbody.innerHTML = items.map(l => `<tr>
            <td style="width:60px;"><strong>#${l.id}</strong></td>
            <td style="font-size:0.75rem;color:rgba(255,255,255,0.7);white-space:nowrap;">${formatDate(l.created_at)}</td>
            <td><span class="status-badge status-draft" style="text-transform:uppercase;font-size:0.7rem;">${escapeHtml(l.module || '-')}</span></td>
            <td><strong>${escapeHtml(l.action || '-')}</strong></td>
            <td style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(l.description || '')}">${escapeHtml(l.description || '-')}</td>
            <td>${l.user_id ? ('#' + l.user_id) : '-'}</td>
            <td>${l.admin_user_id ? ('#' + l.admin_user_id) : '-'}</td>
            <td style="font-family:monospace;font-size:0.75rem;color:rgba(255,255,255,0.6);">${escapeHtml(l.ip_address || '-')}</td>
        </tr>`).join('');
    }
    renderPagination('pagination', data.pagination, loadLogs);
}

function exportLogs() {
    alert('Export CSV triggered. Connect to a CSV export endpoint for production use.');
}

loadLogs(1);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
