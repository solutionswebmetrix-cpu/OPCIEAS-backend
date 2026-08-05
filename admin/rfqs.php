<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$db = Database::getInstance();
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(product_name LIKE ? OR rfq_number LIKE ? OR description LIKE ?)';
    $s = '%' . $search . '%';
    $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}
$whereSql = implode(' AND ', $where);

$totalStmt = $db->query("SELECT COUNT(*) FROM rfqs WHERE $whereSql", $params);
$total = intval($totalStmt->fetchColumn());

$stmt = $db->query("SELECT * FROM rfqs WHERE $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset", $params);
$items = $stmt->fetchAll();
$totalPages = max(1, ceil($total / $limit));
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📝 RFQ Management</h2>
        <div style="display:flex;gap:0.5rem;">
            <a href="rfqs.php" class="btn-secondary btn-sm">🔄 Refresh</a>
        </div>
    </div>

    <div class="filters-bar">
        <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;width:100%;">
            <div class="search-input">
                <input type="text" name="search" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search RFQs...">
            </div>
            <select name="status" class="form-select" style="max-width:200px;">
                <option value="">All Statuses</option>
                <?php foreach (['draft','submitted','under_review','open','quotations_received','negotiating','shortlisted','awarded','Closed','Cancelled','expired'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_',' ',$s)); ?></option>
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
                    <th>RFQ #</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Buyer</th>
                    <th>Contact</th>
                    <th>Required Date</th>
                    <th>Quotes</th>
                    <th>Status</th>
                    <th>Posted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                <tr><td colspan="10" style="text-align:center;padding:2rem;color:rgba(255,255,255,0.5);">📭 No RFQs found.</td></tr>
                <?php else: foreach ($items as $r): ?>
                <tr>
                    <td><strong>#<?php echo $r['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($r['rfq_number']); ?></td>
                    <td><?php echo htmlspecialchars($r['product_name']); ?></td>
                    <td><?php echo $r['quantity']; ?> <?php echo htmlspecialchars($r['unit']); ?></td>
                    <td>Buyer #<?php echo $r['buyer_id']; ?></td>
                    <td>
                        <?php if (!empty($r['contact_email'])): ?><a href="mailto:<?php echo htmlspecialchars($r['contact_email']); ?>" style="color:#D4AF37;"><?php echo htmlspecialchars($r['contact_email']); ?></a><?php else: ?>-<?php endif; ?>
                        <?php if (!empty($r['contact_phone'])): ?><br><span style="font-size:0.75rem;opacity:0.7;"><?php echo htmlspecialchars($r['contact_phone']); ?></span><?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;"><?php echo !empty($r['required_date']) ? date('M d, Y', strtotime($r['required_date'])) : '-'; ?></td>
                    <td><?php echo $r['total_quotes']; ?></td>
                    <td><?php
                        $cls = 'status-' . strtolower(str_replace([' ','_'],'-',$r['status']));
                        echo '<span class="status-badge '.$cls.'">'.htmlspecialchars($r['status']).'</span>';
                    ?></td>
                    <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <div class="pagination-info">Showing <?php echo $total === 0 ? 0 : $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> of <?php echo $total; ?> entries</div>
        <div class="pagination-buttons">
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="rfqs.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
