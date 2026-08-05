<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$db = Database::getInstance();
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ? OR company LIKE ?)';
    $s = '%' . $search . '%';
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($type !== '') {
    $where[] = 'type = ?';
    $params[] = $type;
}
$whereSql = implode(' AND ', $where);

$totalStmt = $db->query("SELECT COUNT(*) FROM contacts WHERE $whereSql", $params);
$total = intval($totalStmt->fetchColumn());

$stmt = $db->query("SELECT * FROM contacts WHERE $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset", $params);
$items = $stmt->fetchAll();
$totalPages = max(1, ceil($total / $limit));
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">✉️ Contact Inquiries</h2>
        <div style="display:flex;gap:0.5rem;">
            <a href="contacts.php" class="btn-secondary btn-sm">🔄 Refresh</a>
        </div>
    </div>

    <div class="filters-bar">
        <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;width:100%;">
            <div class="search-input">
                <input type="text" name="search" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search contacts...">
            </div>
            <select name="status" class="form-select" style="max-width:180px;">
                <option value="">All Statuses</option>
                <?php foreach (['new','read','replied','resolved','spam','Closed'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" class="form-select" style="max-width:180px;">
                <option value="">All Types</option>
                <?php foreach (['general','sales','support','partnership','career','tender'] as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn-secondary btn-sm" type="submit">🔍 Apply</button>
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                <tr><td colspan="9" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No contact inquiries found.</td></tr>
                <?php else: foreach ($items as $c): ?>
                <tr>
                    <td><strong>#<?php echo $c['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><a href="mailto:<?php echo htmlspecialchars($c['email']); ?>" style="color:#D4AF37;"><?php echo htmlspecialchars($c['email']); ?></a><?php if (!empty($c['phone'])): ?><br><span style="font-size:0.75rem;opacity:0.7;">📞 <?php echo htmlspecialchars($c['phone']); ?></span><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($c['company'] ?? '-'); ?></td>
                    <td title="<?php echo htmlspecialchars($c['message']); ?>"><?php echo htmlspecialchars(mb_strlen($c['subject']) > 60 ? mb_substr($c['subject'],0,60).'...' : $c['subject']); ?></td>
                    <td><?php echo ucfirst($c['type']); ?></td>
                    <td><?php echo ucfirst($c['priority']); ?></td>
                    <td><?php
                        $cls = 'status-' . strtolower($c['status']);
                        echo '<span class="status-badge '.$cls.'">'.htmlspecialchars($c['status']).'</span>';
                    ?></td>
                    <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <div class="pagination-info">Showing <?php echo $total === 0 ? 0 : $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> of <?php echo $total; ?> entries</div>
        <div class="pagination-buttons">
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="contacts.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
