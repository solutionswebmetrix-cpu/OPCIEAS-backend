<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Order Management</h2>
        <div style="display:flex;gap:0.5rem;">
            <span class="btn-secondary btn-sm" onclick="loadOrders()">🔄 Refresh</span>
        </div>
    </div>

    <div class="filters-bar">
        <div class="search-input">
            <input type="text" id="searchInput" class="form-input" placeholder="Search orders..." onkeypress="if(event.key==='Enter')loadOrders(1)">
        </div>
        <select id="statusFilter" class="form-select" style="max-width:200px;" onchange="loadOrders(1)">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Processing">Processing</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
        </select>
        <button class="btn-secondary btn-sm" onclick="loadOrders(1)">🔍 Apply</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="width:280px;">Actions</th>
                </tr>
            </thead>
            <tbody id="ordersTbody">
                <tr><td colspan="11" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading orders...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>

<div class="modal-overlay" id="viewOrderModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">📋 Order Details</h3>
            <button class="modal-close" onclick="closeModal('viewOrderModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewOrderBody">
            <p style="text-align:center;color:rgba(255,255,255,0.5);">Loading...</p>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentItems = [];

async function loadOrders(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('ordersTbody');
    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading orders...</td></tr>';
    const result = await apiGet('/admin/orders/list.php', { page, limit: 10, status, search });
    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:2rem;color:#fca5a5;">❌ ${escapeHtml(result.message || 'Failed to load')}</td></tr>`;
        return;
    }
    const data = result.data;
    currentItems = data.items || [];
    if (!currentItems.length) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No orders found.</td></tr>';
    } else {
        tbody.innerHTML = currentItems.map(o => {
            const actions = renderOrderActions(o);
            return `<tr>
                <td><strong>#${o.id}</strong></td>
                <td>${escapeHtml(o.order_number || '-')}</td>
                <td>${escapeHtml(o.product_name || ('#' + (o.product_id || '-')))}</td>
                <td>${o.quantity || '-'}</td>
                <td><strong>${o.currency || 'INR'} ${o.total_amount || '-'}</strong></td>
                <td>${escapeHtml(o.buyer_name || o.buyer_email || ('#' + (o.buyer_id || '-')))}</td>
                <td>${escapeHtml(o.seller_name || o.seller_company || ('#' + (o.seller_id || '-')))}</td>
                <td>${statusBadge(o.payment_status || '-')}</td>
                <td>${statusBadge(o.status)}</td>
                <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">${formatDate(o.created_at)}</td>
                <td>${actions}</td>
            </tr>`;
        }).join('');
    }
    renderPagination('pagination', data.pagination, loadOrders);
}

function renderOrderActions(o) {
    let html = '<div style="display:flex;gap:0.3rem;flex-wrap:wrap;">';
    html += `<button class="btn-secondary btn-sm" onclick="viewOrder(${o.id})">👁️</button>`;
    if (o.status === 'Pending') {
        html += `<button class="btn-primary btn-sm" onclick="updateOrderStatus(${o.id},'Processing')">▶️ Process</button>`;
        html += `<button class="btn-danger btn-sm" onclick="updateOrderStatus(${o.id},'Cancelled')">❌ Cancel</button>`;
    } else if (o.status === 'Processing') {
        html += `<button class="btn-primary btn-sm" onclick="updateOrderStatus(${o.id},'Completed')">✅ Complete</button>`;
    }
    html += '</div>';
    return html;
}

function viewOrder(id) {
    const o = currentItems.find(x => x.id === id);
    if (!o) return;
    openModal('viewOrderModal');
    const body = document.getElementById('viewOrderBody');
    const details = [
        ['ID', o.id],
        ['Order Number', o.order_number],
        ['Invoice', o.invoice_number || '-'],
        ['Product', o.product_name || ('#' + (o.product_id || '-'))],
        ['Quantity', o.quantity],
        ['Unit Price', (o.currency || 'INR') + ' ' + (o.unit_price || '-')],
        ['Subtotal', (o.currency || 'INR') + ' ' + (o.subtotal || '-')],
        ['Tax', (o.currency || 'INR') + ' ' + (o.tax_amount || '0.00')],
        ['Shipping', (o.currency || 'INR') + ' ' + (o.shipping_amount || '0.00')],
        ['Discount', (o.currency || 'INR') + ' ' + (o.discount_amount || '0.00')],
        ['Total Amount', '<strong>' + (o.currency || 'INR') + ' ' + (o.total_amount || '-') + '</strong>'],
        ['Payment Method', o.payment_method || '-'],
        ['Payment Status', statusBadge(o.payment_status || '-')],
        ['Order Status', statusBadge(o.status)],
        ['Estimated Delivery', formatDate(o.estimated_delivery_date)],
        ['Actual Delivery', formatDate(o.actual_delivery_date)],
        ['Tracking Number', o.tracking_number ? escapeHtml(o.tracking_number) : '-'],
        ['Buyer Notes', o.buyer_notes ? `<div style="white-space:pre-wrap;">${escapeHtml(o.buyer_notes)}</div>` : '-'],
        ['Admin Notes', o.admin_notes ? `<div style="white-space:pre-wrap;">${escapeHtml(o.admin_notes)}</div>` : '-'],
        ['Created At', formatDate(o.created_at)],
    ];
    body.innerHTML = '<div style="display:grid;grid-template-columns:1fr;gap:0.625rem;">' +
        details.map(([k, v]) => `<div style="display:flex;border-bottom:1px solid rgba(255,255,255,0.06);padding:0.5rem 0;"><div style="width:160px;flex-shrink:0;font-weight:600;color:#D4AF37;font-size:0.85rem;">${k}</div><div style="flex:1;color:rgba(255,255,255,0.9);font-size:0.85rem;">${v ? v : '-'}</div></div>`).join('') +
        '</div>';
}

async function updateOrderStatus(id, status) {
    confirmDialog('Update Order', `Set order #${id} status to "${status}"?`, async () => {
        const result = await apiPost('/admin/orders/update_status.php', { id, status });
        if (result.success) {
            showAlert(`✅ Order status updated to ${status}!`, 'success');
            loadOrders(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Failed'), 'error');
        }
    });
}

loadOrders(1);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
