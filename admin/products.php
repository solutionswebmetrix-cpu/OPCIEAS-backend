<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📦 Product Management</h2>
        <div style="display:flex;gap:0.5rem;">
            <button class="btn-primary btn-sm" onclick="openProductModal('create')">➕ Create Product</button>
            <span class="btn-secondary btn-sm" onclick="loadProducts()">🔄 Refresh</span>
        </div>
    </div>

    <div class="filters-bar">
        <div class="search-input">
            <input type="text" id="searchInput" class="form-input" placeholder="Search products..." onkeypress="if(event.key==='Enter')loadProducts(1)">
        </div>
        <select id="categoryFilter" class="form-select" style="max-width:220px;" onchange="loadProducts(1)">
            <option value="">All Categories</option>
        </select>
        <select id="statusFilter" class="form-select" style="max-width:200px;" onchange="loadProducts(1)">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Published">Published</option>
            <option value="Approved">Approved</option>
            <option value="Hidden">Hidden</option>
            <option value="Rejected">Rejected</option>
            <option value="Draft">Draft</option>
            <option value="Suspended">Suspended</option>
        </select>
        <button class="btn-secondary btn-sm" onclick="loadProducts(1)">🔍 Apply</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price Range</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="width:280px;">Actions</th>
                </tr>
            </thead>
            <tbody id="productsTbody">
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading products...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>

<div class="modal-overlay" id="productModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="productModalTitle">➕ Create Product</h3>
            <button class="modal-close" onclick="closeModal('productModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="productForm" onsubmit="event.preventDefault();">
                <input type="hidden" id="p_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Product Name *</label>
                        <input type="text" id="p_name" class="form-input" required placeholder="e.g. Executive Office Chair">
                    </div>
                    <div class="form-group">
                        <label class="form-label">URL Slug</label>
                        <input type="text" id="p_slug" class="form-input" placeholder="auto-generated if empty">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select id="p_category_id" class="form-select" required></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price Range</label>
                        <input type="text" id="p_price_range" class="form-input" placeholder="e.g. ₹5,000 - ₹15,000">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:0.75rem;padding-top:1.75rem;">
                        <input type="checkbox" id="p_featured" style="accent-color:#D4AF37;width:18px;height:18px;">
                        <label for="p_featured" style="font-weight:600;color:rgba(255,255,255,0.85);cursor:pointer;">⭐ Featured Product</label>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Status *</label>
                        <select id="p_status" class="form-select" required>
                            <option value="Pending">Pending</option>
                            <option value="Published">Published</option>
                            <option value="Approved">Approved</option>
                            <option value="Hidden">Hidden</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Draft">Draft</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Short Description</label>
                        <textarea id="p_short_desc" class="form-textarea" rows="2" placeholder="Brief summary..."></textarea>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Long Description</label>
                        <textarea id="p_long_desc" class="form-textarea" rows="4" placeholder="Full product details..."></textarea>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Specifications (JSON)</label>
                        <textarea id="p_specs" class="form-textarea" rows="3" style="font-family:monospace;font-size:0.8rem;" placeholder='{"Material":"Wood","Color":"Brown","Warranty":"1 Year"}'></textarea>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Features (comma-separated)</label>
                        <input type="text" id="p_features" class="form-input" placeholder="Ergonomic,Adjustable,Durable,...">
                    </div>
                    <div class="form-group md:col-span-2" id="imagesSection">
                        <label class="form-label">Upload Images</label>
                        <input type="file" id="p_images" class="form-input" multiple accept="image/*" style="padding:0.5rem;">
                        <div id="imagesPreview" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;"></div>
                    </div>
                    <div class="form-group md:col-span-2" id="existingImagesSection" style="display:none;">
                        <label class="form-label">Existing Images</label>
                        <div id="existingImages" style="display:flex;gap:0.5rem;flex-wrap:wrap;"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('productModal')">Cancel</button>
            <button class="btn-primary" id="productSubmitBtn" onclick="submitProduct()">💾 Save Product</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="viewProductModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">👁️ Product Details</h3>
            <button class="modal-close" onclick="closeModal('viewProductModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewProductBody" style="max-height:70vh;overflow-y:auto;">
            <p style="text-align:center;color:rgba(255,255,255,0.5);">Loading...</p>
        </div>
    </div>
</div>

<div class="modal-overlay" id="uploadImagesModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">📤 Upload Product Images</h3>
            <button class="modal-close" onclick="closeModal('uploadImagesModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="upload_pid">
            <div class="form-group">
                <label class="form-label">Select Images *</label>
                <input type="file" id="upload_images" class="form-input" multiple accept="image/*" style="padding:0.5rem;" required>
            </div>
            <div id="uploadPreview" style="display:flex;gap:0.5rem;flex-wrap:wrap;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('uploadImagesModal')">Cancel</button>
            <button class="btn-primary" onclick="submitImageUpload()">📤 Upload</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentItems = [];
let currentCategories = [];
let productMode = 'create';

async function loadCategories(selectId = 'categoryFilter') {
    const result = await apiGet('/categories/list.php');
    if (result.success && result.data) {
        const cats = result.data.items || result.data || [];
        currentCategories = cats;
        const opts = cats.map(c => `<option value="${c.id}">${escapeHtml(c.name || c.title || 'Category ' + c.id)}</option>`).join('');
        if (document.getElementById(selectId)) {
            const cur = selectId === 'categoryFilter'
                ? '<option value="">All Categories</option>'
                : '<option value="">-- Select --</option>';
            document.getElementById(selectId).innerHTML = cur + opts;
        }
        const pcat = document.getElementById('p_category_id');
        if (pcat && selectId !== 'p_category_id') {
            pcat.innerHTML = '<option value="">-- Select Category --</option>' + opts;
        }
    }
}

async function loadProducts(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('productsTbody');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading products...</td></tr>';

    const params = { page, limit: 10 };
    if (search) params.search = search;
    if (category) params.category_id = category;
    if (status) params.status = status;

    let result = await apiGet('/admin/products/list.php', params);
    if (!result.success || !result.data) {
        result = await apiGet('/products/list.php', params);
    }
    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;color:#fca5a5;">❌ ${escapeHtml(result.message || 'Failed to load products')}</td></tr>`;
        return;
    }
    const items = (result.data && result.data.items) ? result.data.items : (result.data || []);
    const pagination = (result.data && result.data.pagination) ? result.data.pagination : { page, limit: 10, total: items.length, total_pages: 1 };
    currentItems = items;
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No products found.</td></tr>';
    } else {
        tbody.innerHTML = items.map(p => {
            const catName = (currentCategories.find(c => c.id == p.category_id) || {})['name'] || ('#' + (p.category_id || '-'));
            return `<tr>
                <td><strong>#${p.id}</strong></td>
                <td>${escapeHtml(p.name || p.title || '-')}</td>
                <td>${escapeHtml(catName)}</td>
                <td>${escapeHtml(p.price_range || '-')}</td>
                <td>${(p.featured || p.is_featured ? '<span class="status-badge status-success">⭐ Featured</span>' : '<span style="color:rgba(255,255,255,0.4);">—</span>'}</td>
                <td>${statusBadge(p.status)}</td>
                <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">${formatDate(p.updated_at || p.created_at)}</td>
                <td>${renderProductActions(p)}</td>
            </tr>`;
        }).join('');
    }
    renderPagination('pagination', pagination, loadProducts);
}

function renderProductActions(p) {
    let html = '<div style="display:flex;gap:0.3rem;flex-wrap:wrap;">';
    html += `<button class="btn-secondary btn-sm" onclick="viewProduct(${p.id})">👁️</button>`;
    html += `<button class="btn-secondary btn-sm" onclick="openProductModal('edit', ${p.id})">✏️</button>`;
    html += `<button class="btn-secondary btn-sm" onclick="openUploadImages(${p.id})">📤</button>`;
    const st = (p.status || '').toLowerCase();
    if (st === 'hidden' || st === 'pending' || st === 'draft' || st === 'rejected') {
        html += `<button class="btn-primary btn-sm" onclick="toggleProductStatus(${p.id},'Published')">✅ Publish</button>`;
    } else if (st === 'published' || st === 'approved') {
        html += `<button class="btn-secondary btn-sm" onclick="toggleProductStatus(${p.id},'Hidden')">🙈 Hide</button>`;
    }
    html += `<button class="btn-danger btn-sm" onclick="deleteProduct(${p.id})">🗑️</button>`;
    html += '</div>';
    return html;
}

function viewProduct(id) {
    const p = currentItems.find(x => x.id === id);
    if (!p) return;
    openModal('viewProductModal');
    const body = document.getElementById('viewProductBody');
    const catName = (currentCategories.find(c => c.id == p.category_id) || {})['name'] || ('#' + (p.category_id || '-'));
    let specsHtml = '';
    try {
        const s = p.specs_json || p.specs;
        if (s) {
            const obj = typeof s === 'string' ? JSON.parse(s) : s;
            if (obj && typeof obj === 'object') {
                specsHtml = Object.entries(obj).map(([k,v]) => `<div style="padding:0.25rem 0;"><strong style="color:#D4AF37;">${escapeHtml(k)}:</strong> ${escapeHtml(String(v))}</div>`).join('');
            }
        }
    } catch(e) {}
    let featuresHtml = '';
    try {
        const f = p.features_json || p.features;
        if (f) {
            const arr = typeof f === 'string' ? (f.startsWith('[') ? JSON.parse(f) : f.split(',').map(s => s.trim()).filter(Boolean) : (Array.isArray(f) ? f : []);
            if (arr.length) featuresHtml = arr.map(f => `<span style="display:inline-block;padding:0.2rem 0.6rem;background:rgba(212,175,55,0.12);color:#E8C96B;border-radius:4px;font-size:0.75rem;margin:0.15rem;">${escapeHtml(f)}</span>`).join(' ');
        }
    } catch(e) {}
    body.innerHTML = `
        <div style="display:grid;gap:1rem;">
            <div>
                <div style="font-size:1.25rem;font-weight:700;color:#fff;margin-bottom:0.25rem;">${escapeHtml(p.name || p.title || '')}</div>
                <div style="display:flex;gap:0.5rem;align-items:center;">${statusBadge(p.status)} ${(p.featured || p.is_featured) ? '<span class="status-badge status-success">⭐ Featured</span>' : ''}</div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
                <div><span style="color:#D4AF37;font-weight:600;">Category:</span> ${escapeHtml(catName)}</div>
                <div><span style="color:#D4AF37;font-weight:600;">Price:</span> ${escapeHtml(p.price_range || '-')}</div>
                <div><span style="color:#D4AF37;font-weight:600;">Slug:</span> ${escapeHtml(p.slug || '-')}</div>
                <div><span style="color:#D4AF37;font-weight:600;">ID:</span> #${p.id}</div>
            </div>
            ${p.short_desc ? `<div><div style="color:#D4AF37;font-weight:600;margin-bottom:0.25rem;">Short Description</div><div style="color:rgba(255,255,255,0.85);">${escapeHtml(p.short_desc)}</div></div>` : ''}
            ${p.long_desc ? `<div><div style="color:#D4AF37;font-weight:600;margin-bottom:0.25rem;">Description</div><div style="color:rgba(255,255,255,0.85);white-space:pre-wrap;">${escapeHtml(p.long_desc)}</div></div>` : ''}
            ${specsHtml ? `<div><div style="color:#D4AF37;font-weight:600;margin-bottom:0.25rem;">Specifications</div>${specsHtml}</div>` : ''}
            ${featuresHtml ? `<div><div style="color:#D4AF37;font-weight:600;margin-bottom:0.5rem;">Features</div>${featuresHtml}</div>` : ''}
            <div style="font-size:0.75rem;color:rgba(255,255,255,0.5);">Created: ${formatDate(p.created_at)} | Updated: ${formatDate(p.updated_at)}</div>
        </div>
    `;
}

function openProductModal(mode, id = null) {
    productMode = mode;
    document.getElementById('productModalTitle').textContent = mode === 'create' ? '➕ Create Product' : `✏️ Edit Product #' + id;
    document.getElementById('p_id').value = '';
    document.getElementById('p_name').value = '';
    document.getElementById('p_slug').value = '';
    document.getElementById('p_category_id').value = '';
    document.getElementById('p_price_range').value = '';
    document.getElementById('p_featured').checked = false;
    document.getElementById('p_status').value = 'Pending';
    document.getElementById('p_short_desc').value = '';
    document.getElementById('p_long_desc').value = '';
    document.getElementById('p_specs').value = '';
    document.getElementById('p_features').value = '';
    document.getElementById('p_images').value = '';
    document.getElementById('imagesPreview').innerHTML = '';
    document.getElementById('existingImagesSection').style.display = 'none';
    document.getElementById('existingImages').innerHTML = '';
    if (mode === 'edit' && id) {
        const p = currentItems.find(x => x.id === id);
        if (p) {
            document.getElementById('p_id').value = p.id;
            document.getElementById('p_name').value = p.name || p.title || '';
            document.getElementById('p_slug').value = p.slug || '';
            document.getElementById('p_category_id').value = p.category_id || '';
            document.getElementById('p_price_range').value = p.price_range || '';
            document.getElementById('p_featured').checked = !!(p.featured || p.is_featured);
            document.getElementById('p_status').value = p.status || 'Pending';
            document.getElementById('p_short_desc').value = p.short_desc || '';
            document.getElementById('p_long_desc').value = p.long_desc || '';
            let specVal = p.specs_json || p.specs || '';
            if (specVal && typeof specVal === 'object') specVal = JSON.stringify(specVal, null, 2);
            document.getElementById('p_specs').value = specVal || '';
            let featVal = p.features_json || p.features || '';
            if (featVal) {
                if (typeof featVal === 'object' || (typeof featVal === 'string' && featVal.startsWith('['))) {
                    try { const arr = JSON.parse(featVal); featVal = Array.isArray(arr) ? arr.join(',') : featVal; } catch(e){}
                }
            }
            document.getElementById('p_features').value = featVal || '';
            if (p.images && p.images.length) {
                document.getElementById('existingImagesSection').style.display = 'block';
                document.getElementById('existingImages').innerHTML = p.images.map(img => {
                    const url = typeof img === 'string' ? img : (img.image_url || img.url || '');
                    return `<div style="position:relative;"><img src="${escapeHtml(url)}" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid rgba(212,175,55,0.2);"></div>`;
                }).join('');
            }
        }
    }
    openModal('productModal');
}

document.addEventListener('DOMContentLoaded', () => {
    const imgInput = document.getElementById('p_images');
    if (imgInput) imgInput.addEventListener('change', e => previewImages(e.target.files, 'imagesPreview'));
    const upInput = document.getElementById('upload_images');
    if (upInput) upInput.addEventListener('change', e => previewImages(e.target.files, 'uploadPreview'));
});

function previewImages(files, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    [...files].forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid rgba(212,175,55,0.2);';
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

async function submitProduct() {
    const btn = document.getElementById('productSubmitBtn');
    btn.disabled = true;

    const name = document.getElementById('p_name').value.trim();
    const category_id = document.getElementById('p_category_id').value;
    if (!name || !category_id) {
        showAlert('Name and category are required.', 'error');
        btn.disabled = false;
        return;
    }
    const featuresRaw = document.getElementById('p_features').value.trim();
    let features_json = null;
    if (featuresRaw) {
        if (features_json = featuresRaw.split(',').map(s => s.trim()).filter(Boolean));
    }
    const specsRaw = document.getElementById('p_specs').value.trim();
    let specs_json = null;
    if (specsRaw) {
        try { specs_json = specsRaw.startsWith('{') ? JSON.parse(specsRaw) : specsRaw; }
        catch(e) { specs_json = specsRaw; }
    }

    const fd = new FormData();
    fd.append('name', name);
    const slug = document.getElementById('p_slug').value.trim();
    if (slug) fd.append('slug', slug);
    fd.append('category_id', category_id);
    fd.append('short_desc', document.getElementById('p_short_desc').value);
    fd.append('long_desc', document.getElementById('p_long_desc').value);
    if (specs_json) fd.append('specs_json', typeof specs_json === 'string' ? specs_json : JSON.stringify(specs_json));
    if (features_json) fd.append('features_json', JSON.stringify(features_json));
    fd.append('price_range', document.getElementById('p_price_range').value.trim());
    fd.append('featured', document.getElementById('p_featured').checked ? 1 : 0);
    fd.append('status', document.getElementById('p_status').value);

    const imgFiles = document.getElementById('p_images').files;
    if (imgFiles && imgFiles.length) {
        for (let i = 0; i < imgFiles.length; i++) fd.append('image_uploads[]', imgFiles[i]);
    }

    let endpoint = '/admin/products/create.php';
    if (productMode === 'edit') {
        endpoint = '/admin/products/update.php';
        fd.append('id', document.getElementById('p_id').value);
    }
    const result = await apiFetch(endpoint, { method: 'POST', body: fd });
    btn.disabled = false;
    if (result.success) {
        showAlert(productMode === 'create' ? '✅ Product created!' : '✅ Product updated!', 'success');
        closeModal('productModal');
        loadProducts(currentPage);
    } else {
        showAlert('❌ ' + (result.message || 'Failed to save product'), 'error');
    }
}

async function toggleProductStatus(id, status) {
    const p = currentItems.find(x => x.id === id);
    const name = p ? (p.name || p.title || '#' + id) : '#' + id;
    confirmDialog(`${status} Product`, `Mark "${escapeHtml(name)}" as ${status}?`, async () => {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('status', status);
        const result = await apiFetch('/admin/products/update.php', { method: 'POST', body: fd });
        if (result.success) {
            showAlert(`✅ Product status updated to ${status}!', 'success');
            loadProducts(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Failed'), 'error');
        }
    });
}

function deleteProduct(id) {
    const p = currentItems.find(x => x.id === id);
    const name = p ? (p.name || p.title || '#' + id) : '#' + id;
    confirmDialog('Delete Product', `⚠️ PERMANENTLY delete "${escapeHtml(name)}"? This cannot be undone.`, async () => {
        const fd = new FormData();
        fd.append('id', id);
        const result = await apiFetch('/admin/products/delete.php', { method: 'POST', body: fd });
        if (result.success) {
            showAlert('🗑️ Product deleted!', 'success');
            loadProducts(currentPage);
        } else {
            showAlert('❌ ' + (result.message || 'Delete failed'), 'error');
        }
    });
}

function openUploadImages(id) {
    document.getElementById('upload_pid').value = id;
    document.getElementById('upload_images').value = '';
    document.getElementById('uploadPreview').innerHTML = '';
    openModal('uploadImagesModal');
}

async function submitImageUpload() {
    const id = document.getElementById('upload_pid').value;
    const files = document.getElementById('upload_images').files;
    if (!files || !files.length) { showAlert('Please select at least one image.', 'error'); return; }
    const fd = new FormData();
    fd.append('product_id', id);
    for (let i = 0; i < files.length; i++) fd.append('image_uploads[]', files[i]);
    const result = await apiFetch('/admin/products/images.php', { method: 'POST', body: fd });
    if (result.success) {
        showAlert('📤 Images uploaded successfully!', 'success');
        closeModal('uploadImagesModal');
        loadProducts(currentPage);
    } else {
        showAlert('❌ ' + (result.message || 'Upload failed'), 'error');
    }
}

Promise.all([loadCategories('categoryFilter'), loadCategories('p_category_id')]).then(() => loadProducts(1));
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
