<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🛒 Purchase Requirements</h2>
        <div style="display:flex;gap:0.5rem;">
            <span class="btn-secondary btn-sm" onclick="loadRequirements()">🔄 Refresh</span>
        </div>
    </div>

    <div class="filters-bar">
        <div class="search-input">
            <input type="text" id="searchInput" class="form-input" placeholder="Search requirements..." onkeypress="if(event.key==='Enter')loadRequirements(1)">
        </div>
        <select id="statusFilter" class="form-select" style="max-width:200px;" onchange="loadRequirements(1)">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Deleted">Deleted</option>
            <option value="Closed">Closed</option>
            <option value="Fake">Fake</option>
        </select>
        <button class="btn-secondary btn-sm" onclick="loadRequirements(1)">🔍 Apply</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Buyer</th>
                    <th>Location</th>
                    <th>Expected</th>
                    <th>Status</th>
                    <th>Posted</th>
                    <th style="width:280px;">Actions</th>
                </tr>
            </thead>
            <tbody id="reqTbody">
                <tr><td colspan="10" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading requirements...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>

<div class="modal-overlay" id="viewReqModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">🛒 Purchase Requirement Details</h3>
            <button class="modal-close" onclick="closeModal('viewReqModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewReqBody">
            <p style="text-align:center;color:rgba(255,255,255,0.5);">Loading...</p>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentItems = [];

async function loadRequirements(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('reqTbody');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading requirements...</td></tr>';
    const result = await apiGet('/admin/purchase_requirements/list.php', { page, limit: 10, status, search });
    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:2rem;color:#fca5a5;">❌ ${escapeHtml(result.message || 'Failed to load')}</td></tr>`;
        return;
    }
    const data = result.data;
    currentItems = data.items || [];
    if (!currentItems.length) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No purchase requirements found.</td></tr>';
    } else {
        tbody.innerHTML = currentItems.map(r => {
            const actions = renderReqActions(r);
            return `<tr>
                <td><strong>#${r.id}</strong></td>
                <td>${escapeHtml(r.product_name || r.title || '-')}</td>
                <td>${r.required_quantity || r.quantity || '-'}</td>
                <td>${escapeHtml(r.unit || '-')}</td>
                <td>${escapeHtml(r.buyer_name || r.buyer_email || ('#' + (r.buyer_id || '-')))}</td>
                <td>${escapeHtml([r.city, r.state, r.country].filter(Boolean).join(', ') || '-')}</td>
                <td style="font-size:0.8rem;">${formatDate(r.expected_delivery || r.required_by_date)}</td>
                <td>${statusBadge(r.status)}</td>
                <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">${formatDate(r.created_at)}</td>
                <td>${actions}</td>
            </tr>`;
        }).join('');
    }
    renderPagination('pagination', data.pagination, loadRequirements);
}

function renderReqActions(r) {
    let html = '<div style="display:flex;gap:0.3rem;flex-wrap:wrap;">';
    html += `<button class="btn-secondary btn-sm" onclick="viewRequirement(${r.id})">👁️</button>`;
    if (r.status === 'Pending') {
        html += `<button class="btn-primary btn-sm" onclick="updateReqStatus(${r.id},'Approved')">✅ Approve</button>`;
        html += `<button class="btn-danger btn-sm" onclick="updateReqStatus(${r.id},'Rejected')">❌ Reject</button>`;
    }
    if (r.status === 'Approved') {
        html += `<button class="btn-secondary btn-sm" onclick="updateReqStatus(${r.id},'Closed')">✅ Close</button>`;
    }
    html += `<button class="btn-danger btn-sm" onclick="updateReqStatus(${r.id},'Deleted')">🗑️</button>`;
    html += `<button class="btn-secondary btn-sm" onclick="updateReqStatus(${r.id},'Fake')" title="Mark as fake">⚠️ Fake</button>`;
    html += '</div>';
    return html;
}

function viewRequirement(id) {
    const r = currentItems.find(x => x.id === id);
    if (!r) return;
    openModal('viewReqModal');
    const body = document.getElementById('viewReqBody');
    const details = [
        ['ID', r.id],
        ['Product Name', r.product_name || r.title],
        ['Title', r.title],
        ['Quantity', (r.required_quantity || r.quantity || '-') + ' ' + (r.unit || '')],
        ['Description', r.description ? `<div style="white-space:pre-wrap;">${escapeHtml(r.description)}</div>` : '-'],
        ['Explanation Note', r.explanation_note ? `<div style="white-space:pre-wrap;">${escapeHtml(r.explanation_note)}</div>` : '-'],
        ['Buyer ID', r.buyer_id],
        ['Category ID', r.category_id || '-'],
        ['Country', r.country || '-'],
        ['State', r.state || '-'],
        ['City', r.city || '-'],
        ['Delivery Address', r.delivery_address ? `<div style="white-space:pre-wrap;">${escapeHtml(r.delivery_address)}</div>` : '-'],
        ['Expected Delivery', formatDate(r.expected_delivery || r.required_by_date)],
        ['Budget', (r.budget_min ? r.budget_min : '-') + ' - ' + (r.budget_max ? r.budget_max : '-')],
        ['Status', statusBadge(r.status)],
        ['Created At', formatDate(r.created_at)],
    ];
    body.innerHTML = '<div style="display:grid;grid-template-columns:1fr;gap:0.625rem;">' +
        details.map(([k, v]) => `<div style="display:flex;border-bottom:1px solid rgba(255,255,255,0.06);padding:0.5rem 0;"><div style="width:150px;flex-shrink:0;font-weight:600;color:#D4AF37;font-size:0.85rem;">${k}</div><div style="flex:1;color:rgba(255,255,255,0.9);font-size:0.85rem;">${v ? v : '-'}</div></div>`).join('') +
        '</div>';
}

async function updateReqStatus(id, status) {
    confirmDialog('Update Status', `Mark requirement #${id} as "${status}"?`, async () => {
        const result = await apiPost('/admin/purchase_requirements/update_status.php', { id, status });
        if (result.success) {
            showAlert(`✅ Status updated to ${status}!`, 'success');
            loadRequirements(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Failed'), 'error');
        }
    });
}

loadRequirements(1);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
