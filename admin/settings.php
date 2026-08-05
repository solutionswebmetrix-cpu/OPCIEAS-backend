<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">⚙️ Settings</h2>
        <div style="display:flex;gap:0.5rem;">
            <span class="btn-secondary btn-sm" onclick="loadSettings()">🔄 Refresh</span>
            <button class="btn-primary btn-sm" onclick="saveAllSettings()">💾 Save Changes</button>
        </div>
    </div>

    <div class="filters-bar">
        <div class="search-input">
            <input type="text" id="searchInput" class="form-input" placeholder="Search settings..." onkeypress="if(event.key==='Enter')loadSettings()">
        </div>
        <select id="groupFilter" class="form-select" style="max-width:200px;" onchange="loadSettings()">
            <option value="">All Groups</option>
            <option value="general">General</option>
            <option value="site">Site</option>
            <option value="email">Email</option>
            <option value="payment">Payment</option>
            <option value="security">Security</option>
            <option value="uploads">Uploads</option>
        </select>
        <button class="btn-secondary btn-sm" onclick="loadSettings()">🔍 Apply</button>
    </div>

    <div id="settingsContainer" style="display:grid;grid-template-columns:1fr;gap:1rem;">
        <div style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading settings...</div>
    </div>
</div>

<script>
let currentSettings = [];

async function loadSettings() {
    const search = document.getElementById('searchInput').value.trim();
    const group = document.getElementById('groupFilter').value;
    const container = document.getElementById('settingsContainer');
    container.innerHTML = '<div style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">⏳ Loading settings...</div>';
    const result = await apiGet('/admin/settings/list.php', { search, group });
    if (!result.success) {
        container.innerHTML = `<div style="padding:2rem;color:#fca5a5;text-align:center;">❌ ${escapeHtml(result.message || 'Failed to load settings')}</div>`;
        return;
    }
    const items = (result.data && result.data.items) ? result.data.items : (Array.isArray(result.data) ? result.data : []);
    currentSettings = items;
    if (!items.length) {
        container.innerHTML = '<div style="padding:2rem;text-align:center;color:rgba(255,255,255,0.5);">📭 No settings found.</div>';
        return;
    }
    const groups = {};
    items.forEach(s => {
        const g = s.group || 'general';
        if (!groups[g]) groups[g] = [];
        groups[g].push(s);
    });
    let html = '';
    Object.keys(groups).forEach(g => {
        html += `<div class="card" style="border-left:4px solid #D4AF37;"><div class="card-header"><h3 class="card-title" style="font-size:1rem;text-transform:capitalize;">🗂️ ${escapeHtml(g)}</h3></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">`;
        groups[g].forEach(s => {
            const id = 'setting_' + s.id;
            html += `<div class="form-group" data-setting-id="${s.id}">
                <label class="form-label" for="${id}">${escapeHtml(s.label || s.key)}${s.is_public ? ' <span title="Public setting" style="font-size:0.7rem;color:#4ade80;">🌐</span>' : ''}</label>`;
            if (s.description) html += `<div style="font-size:0.75rem;color:rgba(255,255,255,0.5);margin-bottom:0.375rem;">${escapeHtml(s.description)}</div>`;
            if (s.type === 'text') html += `<textarea id="${id}" class="form-textarea" rows="3" data-key="${escapeHtml(s.key)}">${escapeHtml(s.value ?? '')}</textarea>`;
            else if (s.type === 'boolean') html += `<select id="${id}" class="form-select" data-key="${escapeHtml(s.key)}"><option value="1" ${(s.value == 1) ? 'selected' : ''}>Yes</option><option value="0" ${(s.value == 0) ? 'selected' : ''}>No</option></select>`;
            else if (s.type === 'integer') html += `<input type="number" id="${id}" class="form-input" value="${escapeHtml(s.value ?? 0)}" data-key="${escapeHtml(s.key)}">`;
            else if (s.type === 'json') html += `<textarea id="${id}" class="form-textarea" rows="4" data-key="${escapeHtml(s.key)}" spellcheck="false" style="font-family:monospace;font-size:0.8rem;">${escapeHtml(s.value ?? '')}</textarea>`;
            else html += `<input type="text" id="${id}" class="form-input" value="${escapeHtml(s.value ?? '')}" data-key="${escapeHtml(s.key)}">`;
            html += `<div style="font-size:0.7rem;color:rgba(255,255,255,0.4);margin-top:0.25rem;">Key: <code style="opacity:0.8;">${escapeHtml(s.key)}</code></div></div>`;
        });
        html += '</div></div>';
    });
    container.innerHTML = html;
}

async function saveAllSettings() {
    const updates = [];
    document.querySelectorAll('[data-setting-id]').forEach(div => {
        const id = parseInt(div.dataset.settingId, 10);
        const input = div.querySelector('input, select, textarea');
        if (!input) return;
        const key = input.dataset.key;
        const value = input.value;
        updates.push({ id, key, value });
    });
    if (!updates.length) {
        showAlert('No changes to save.', 'error');
        return;
    }
    for (const u of updates) {
        await apiPost('/admin/settings/update.php', u);
    }
    showAlert('✅ Settings saved successfully!', 'success');
    loadSettings();
}

loadSettings();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
