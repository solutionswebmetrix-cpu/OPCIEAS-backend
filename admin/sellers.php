<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏢 Seller Management</h2>
        <div style="display:flex;gap:0.5rem;">
            <span id="refreshBtn" class="btn-secondary btn-sm" onclick="loadSellers()">🔄 Refresh</span>
        </div>
    </div>

    <div class="filters-bar">
        <div class="search-input">
            <input type="text" id="searchInput" class="form-input" placeholder="Search sellers..." onkeypress="if(event.key==='Enter')loadSellers(1)">
        </div>
        <select id="statusFilter" class="form-select" style="max-width:200px;" onchange="loadSellers(1)">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Suspended">Suspended</option>
        </select>
        <button class="btn-secondary btn-sm" onclick="loadSellers(1)">🔍 Apply</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th style="width:240px;">Actions</th>
                </tr>
            </thead>
            <tbody id="sellersTbody">
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading sellers...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>

<div class="modal-overlay" id="editSellerModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="editModalTitle">Edit Seller</h3>
            <button class="modal-close" onclick="closeModal('editSellerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editSellerForm">
                <input type="hidden" id="es_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Company Name *</label>
                        <input type="text" id="es_company_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Name *</label>
                        <input type="text" id="es_contact_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" id="es_email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" id="es_phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" id="es_address" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" id="es_city" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" id="es_country" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select id="es_status" class="form-select" required>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">GST / Tax Number</label>
                        <input type="text" id="es_gst_number" class="form-input">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Company Description</label>
                        <textarea id="es_description" class="form-textarea" rows="3"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('editSellerModal')">Cancel</button>
            <button class="btn-primary" onclick="saveSellerEdit()" id="saveSellerEditBtn">💾 Save Changes</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="viewSellerModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">👁️ Seller Details</h3>
            <button class="modal-close" onclick="closeModal('viewSellerModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewSellerBody">
            <p style="text-align:center;color:rgba(255,255,255,0.5);">Loading...</p>
        </div>
    </div>
</div>

<div class="modal-overlay" id="emailModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">📧 Send Details Email</h3>
            <button class="modal-close" onclick="closeModal('emailModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="emailForm">
                <input type="hidden" id="email_seller_id">
                <div class="form-group">
                    <label class="form-label">To</label>
                    <input type="text" id="email_to" class="form-input" readonly style="background-color: rgba(0,0,0,0.3);">
                </div>
                <div class="form-group">
                    <label class="form-label">Recipient Name</label>
                    <input type="text" id="email_name" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Message *</label>
                    <textarea id="email_message" class="form-textarea" rows="5" required placeholder="Describe what additional information is needed..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('emailModal')">Cancel</button>
            <button class="btn-primary" onclick="sendDetailsEmail()">📤 Send Email</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentItems = [];

async function loadSellers(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('sellersTbody');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading sellers...</td></tr>';

    const result = await apiGet('/admin/sellers/list.php', { page, limit: 10, status, search });
    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;color:#fca5a5;">❌ ${escapeHtml(result.message || 'Failed to load')}</td></tr>`;
        return;
    }
    const data = result.data;
    currentItems = data.items || [];
    if (!currentItems.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No sellers found.</td></tr>';
    } else {
        tbody.innerHTML = currentItems.map(s => {
            const actions = renderActions(s);
            return `<tr>
                <td><strong>#${s.id}</strong></td>
                <td>${escapeHtml(s.company_name || '-')}</td>
                <td>${escapeHtml(s.contact_name || '-')}</td>
                <td><a href="mailto:${escapeHtml(s.email || '')}" style="color:#D4AF37;">${escapeHtml(s.email || '-')}</a></td>
                <td>${escapeHtml(s.phone || s.mobile || '-')}</td>
                <td>${statusBadge(s.status)}</td>
                <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">${formatDate(s.created_at)}</td>
                <td>${actions}</td>
            </tr>`;
        }).join('');
    }
    renderPagination('pagination', data.pagination, loadSellers);
}

function renderActions(s) {
    let html = '<div style="display:flex;gap:0.3rem;flex-wrap:wrap;">';
    html += `<button class="btn-secondary btn-sm" onclick="viewSeller(${s.id})">👁️ View</button>`;
    html += `<button class="btn-secondary btn-sm" onclick="editSeller(${s.id})">✏️ Edit</button>`;
    if (s.status === 'Pending') {
        html += `<button class="btn-primary btn-sm" onclick="updateSellerStatus(${s.id},'Approved')">✅ Approve</button>`;
        html += `<button class="btn-danger btn-sm" onclick="updateSellerStatus(${s.id},'Rejected')">❌ Reject</button>`;
    } else if (s.status === 'Approved') {
        html += `<button class="btn-secondary btn-sm" onclick="updateSellerStatus(${s.id},'Suspended')">⏸️ Suspend</button>`;
    } else if (s.status === 'Suspended' || s.status === 'Rejected') {
        html += `<button class="btn-primary btn-sm" onclick="updateSellerStatus(${s.id},'Approved')">✅ Activate</button>`;
    }
    html += `<button class="btn-secondary btn-sm" onclick="openEmailModal(${s.id})">📧 Email</button>`;
    html += `<button class="btn-danger btn-sm" onclick="deleteSeller(${s.id})">🗑️</button>`;
    html += '</div>';
    return html;
}

function viewSeller(id) {
    const s = currentItems.find(x => x.id === id);
    if (!s) return;
    openModal('viewSellerModal');
    const body = document.getElementById('viewSellerBody');
    const details = [
        ['ID', s.id],
        ['Company Name', s.company_name],
        ['Contact Name', s.contact_name],
        ['Designation', s.designation],
        ['Email', s.email ? `<a href="mailto:${escapeHtml(s.email)}" style="color:#D4AF37;">${escapeHtml(s.email)}</a>` : '-'],
        ['Phone', s.phone || s.mobile || '-'],
        ['Address', s.address],
        ['City', s.city],
        ['Country', s.country],
        ['Postal Code', s.postal_code],
        ['GST / Tax No.', s.gst_number || s.tax_number],
        ['Status', statusBadge(s.status)],
        ['Website', s.website ? `<a href="${escapeHtml(s.website)}" target="_blank" style="color:#D4AF37;">${escapeHtml(s.website)}</a>` : '-'],
        ['Registered At', formatDate(s.created_at)],
        ['Description', s.description ? escapeHtml(s.description) : '-'],
    ];
    body.innerHTML = '<div style="display:grid;grid-template-columns:1fr;gap:0.625rem;">' +
        details.map(([k, v]) => `<div style="display:flex;border-bottom:1px solid rgba(255,255,255,0.06);padding:0.5rem 0;"><div style="width:150px;flex-shrink:0;font-weight:600;color:#D4AF37;font-size:0.85rem;">${k}</div><div style="flex:1;color:rgba(255,255,255,0.9);font-size:0.85rem;">${v ? v : '-'}</div></div>`).join('') +
        '</div>';
}

function editSeller(id) {
    const s = currentItems.find(x => x.id === id);
    if (!s) return;
    document.getElementById('editModalTitle').textContent = '✏️ Edit Seller #' + id;
    document.getElementById('es_id').value = s.id;
    document.getElementById('es_company_name').value = s.company_name || '';
    document.getElementById('es_contact_name').value = s.contact_name || '';
    document.getElementById('es_email').value = s.email || '';
    document.getElementById('es_phone').value = s.phone || s.mobile || '';
    document.getElementById('es_address').value = s.address || '';
    document.getElementById('es_city').value = s.city || '';
    document.getElementById('es_country').value = s.country || '';
    document.getElementById('es_status').value = s.status || 'Pending';
    document.getElementById('es_gst_number').value = s.gst_number || s.tax_number || '';
    document.getElementById('es_description').value = s.description || '';
    openModal('editSellerModal');
}

async function saveSellerEdit() {
    const id = document.getElementById('es_id').value;
    const data = {
        id,
        company_name: document.getElementById('es_company_name').value.trim(),
        contact_name: document.getElementById('es_contact_name').value.trim(),
        email: document.getElementById('es_email').value.trim(),
        phone: document.getElementById('es_phone').value.trim(),
        address: document.getElementById('es_address').value.trim(),
        city: document.getElementById('es_city').value.trim(),
        country: document.getElementById('es_country').value.trim(),
        status: document.getElementById('es_status').value,
        gst_number: document.getElementById('es_gst_number').value.trim(),
        description: document.getElementById('es_description').value.trim(),
    };
    const btn = document.getElementById('saveSellerEditBtn');
    btn.disabled = true;
    const result = await apiPost('/admin/sellers/update.php', data);
    btn.disabled = false;
    if (result.success) {
        showAlert('✅ Seller updated successfully!', 'success');
        closeModal('editSellerModal');
        loadSellers(currentPage);
    } else {
        showAlert('❌ ' + (result.message || 'Update failed'), 'error');
    }
}

async function updateSellerStatus(id, status) {
    const s = currentItems.find(x => x.id === id);
    const name = s ? (s.company_name || '#' + id) : '#' + id;
    confirmDialog(`${status} Seller`, `Are you sure you want to mark seller "${escapeHtml(name)}" as ${status}?`, async () => {
        const result = await apiPost('/admin/sellers/update.php', { id, status });
        if (result.success) {
            showAlert(`✅ Seller ${status.toLowerCase()} successfully!`, 'success');
            loadSellers(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Failed'), 'error');
        }
    });
}

function deleteSeller(id) {
    const s = currentItems.find(x => x.id === id);
    const name = s ? (s.company_name || '#' + id) : '#' + id;
    confirmDialog('Delete Seller', `⚠️ PERMANENTLY delete seller "${escapeHtml(name)}"? This cannot be undone.`, async () => {
        const result = await apiPost('/admin/sellers/delete.php', { id });
        if (result.success) {
            showAlert('🗑️ Seller deleted successfully!', 'success');
            loadSellers(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Delete failed'), 'error');
        }
    });
}

function openEmailModal(id) {
    const s = currentItems.find(x => x.id === id);
    if (!s) return;
    document.getElementById('email_seller_id').value = s.id;
    document.getElementById('email_to').value = s.email || '';
    document.getElementById('email_name').value = s.contact_name || '';
    document.getElementById('email_message').value = '';
    openModal('emailModal');
}

async function sendDetailsEmail() {
    const id = document.getElementById('email_seller_id').value;
    const message = document.getElementById('email_message').value.trim();
    if (!message) { showAlert('Please enter a message.', 'error'); return; }
    const result = await apiPost('/admin/sellers/send_details_email.php', { seller_id: id, message });
    if (result.success) {
        showAlert('📧 Email sent successfully!', 'success');
        closeModal('emailModal');
    } else {
        showAlert('❌ ' + (result.message || 'Failed to send email'), 'error');
    }
}

loadSellers(1);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
