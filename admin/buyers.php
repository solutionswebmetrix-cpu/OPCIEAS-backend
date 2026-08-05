<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">👥 Buyer Management</h2>
        <div style="display:flex;gap:0.5rem;">
            <span id="refreshBtn" class="btn-secondary btn-sm" onclick="loadBuyers()">🔄 Refresh</span>
        </div>
    </div>

    <div class="filters-bar">
        <div class="search-input">
            <input type="text" id="searchInput" class="form-input" placeholder="Search buyers..." onkeypress="if(event.key==='Enter')loadBuyers(1)">
        </div>
        <select id="statusFilter" class="form-select" style="max-width:200px;" onchange="loadBuyers(1)">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Suspended">Suspended</option>
        </select>
        <button class="btn-secondary btn-sm" onclick="loadBuyers(1)">🔍 Apply</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Phone</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody id="buyersTbody">
                <tr><td colspan="9" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading buyers...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>

<div class="modal-overlay" id="viewBuyerModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">👁️ Buyer Details</h3>
            <button class="modal-close" onclick="closeModal('viewBuyerModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewBuyerBody">
            <p style="text-align:center;color:rgba(255,255,255,0.5);">Loading...</p>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentItems = [];

async function loadBuyers(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('buyersTbody');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading buyers...</td></tr>';

    const result = await apiGet('/admin/buyers/list.php', { page, limit: 10, status, search });
    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:#fca5a5;">❌ ${escapeHtml(result.message || 'Failed to load')}</td></tr>`;
        return;
    }
    const data = result.data;
    currentItems = data.items || [];
    if (!currentItems.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No buyers found.</td></tr>';
    } else {
        tbody.innerHTML = currentItems.map(b => {
            const actions = renderActions(b);
            return `<tr>
                <td><strong>#${b.id}</strong></td>
                <td>${escapeHtml(b.contact_name || b.name || '-')}</td>
                <td><a href="mailto:${escapeHtml(b.email || '')}" style="color:#D4AF37;">${escapeHtml(b.email || '-')}</a></td>
                <td>${escapeHtml(b.company_name || b.company || '-')}</td>
                <td>${escapeHtml(b.phone || b.mobile || '-')}</td>
                <td>${escapeHtml(b.country || '-')}</td>
                <td>${statusBadge(b.status)}</td>
                <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">${formatDate(b.created_at)}</td>
                <td>${actions}</td>
            </tr>`;
        }).join('');
    }
    renderPagination('pagination', data.pagination, loadBuyers);
}

function renderActions(b) {
    let html = '<div style="display:flex;gap:0.3rem;flex-wrap:wrap;">';
    html += `<button class="btn-secondary btn-sm" onclick="viewBuyer(${b.id})">👁️ View</button>`;
    if (b.status === 'Pending') {
        html += `<button class="btn-primary btn-sm" onclick="updateBuyerStatus(${b.id},'Approved')">✅ Approve</button>`;
        html += `<button class="btn-danger btn-sm" onclick="updateBuyerStatus(${b.id},'Rejected')">❌ Reject</button>`;
    } else if (b.status === 'Approved') {
        html += `<button class="btn-secondary btn-sm" onclick="updateBuyerStatus(${b.id},'Suspended')">⏸️ Suspend</button>`;
    } else {
        html += `<button class="btn-primary btn-sm" onclick="updateBuyerStatus(${b.id},'Approved')">✅ Activate</button>`;
    }
    html += `<button class="btn-danger btn-sm" onclick="deleteBuyer(${b.id})">🗑️</button>`;
    html += '</div>';
    return html;
}

function viewBuyer(id) {
    const b = currentItems.find(x => x.id === id);
    if (!b) return;
    openModal('viewBuyerModal');
    const body = document.getElementById('viewBuyerBody');
    const details = [
        ['ID', b.id],
        ['Name', b.contact_name || b.name],
        ['Company', b.company_name || b.company],
        ['Designation', b.designation],
        ['Email', b.email ? `<a href="mailto:${escapeHtml(b.email)}" style="color:#D4AF37;">${escapeHtml(b.email)}</a>` : '-'],
        ['Phone', b.phone || b.mobile || '-'],
        ['Address', b.address],
        ['City', b.city],
        ['Country', b.country],
        ['Postal Code', b.postal_code],
        ['Status', statusBadge(b.status)],
        ['Registered At', formatDate(b.created_at)],
        ['Last Login', formatDate(b.last_login_at)],
    ];
    body.innerHTML = '<div style="display:grid;grid-template-columns:1fr;gap:0.625rem;">' +
        details.map(([k, v]) => `<div style="display:flex;border-bottom:1px solid rgba(255,255,255,0.06);padding:0.5rem 0;"><div style="width:150px;flex-shrink:0;font-weight:600;color:#D4AF37;font-size:0.85rem;">${k}</div><div style="flex:1;color:rgba(255,255,255,0.9);font-size:0.85rem;">${v ? v : '-'}</div></div>`).join('') +
        '</div>';
}

async function updateBuyerStatus(id, status) {
    const b = currentItems.find(x => x.id === id);
    const name = b ? (b.contact_name || b.name || '#' + id) : '#' + id;
    confirmDialog(`${status} Buyer`, `Are you sure you want to mark buyer "${escapeHtml(name)}" as ${status}?`, async () => {
        const result = await apiPost('/admin/buyers/update_status.php', { id, status });
        if (result.success) {
            showAlert(`✅ Buyer ${status.toLowerCase()} successfully!`, 'success');
            loadBuyers(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Failed'), 'error');
        }
    });
}

function deleteBuyer(id) {
    const b = currentItems.find(x => x.id === id);
    const name = b ? (b.contact_name || b.name || '#' + id) : '#' + id;
    confirmDialog('Delete Buyer', `⚠️ PERMANENTLY delete buyer "${escapeHtml(name)}"? This cannot be undone.`, async () => {
        const result = await apiPost('/admin/buyers/delete.php', { id });
        if (result.success) {
            showAlert('🗑️ Buyer deleted successfully!', 'success');
            loadBuyers(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Delete failed'), 'error');
        }
    });
}

loadBuyers(1);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
