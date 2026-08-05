<?php
require_once __DIR__ . '/header.php';

$dashboardData = [
    'counts' => [
        'pending_sellers' => 0,
        'pending_buyers' => 0,
        'pending_products' => 0,
        'total_products' => 0,
        'total_orders' => 0,
        'pending_requirements' => 0,
        'approved_requirements' => 0,
        'total_rfqs' => 0,
    ],
    'recent_activity' => [],
    'monthly_chart' => []
];

$apiUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/api/admin/dashboard/stats.php';
$sessionCookie = session_name() . '=' . session_id();

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: $sessionCookie\r\nAccept: application/json\r\n",
        'timeout' => 10,
        'ignore_errors' => true,
    ]
];
$context = stream_context_create($opts);
$apiResponse = @file_get_contents($apiUrl, false, $context);

if ($apiResponse) {
    $decoded = json_decode($apiResponse, true);
    if (is_array($decoded) && !empty($decoded['success']) && !empty($decoded['data'])) {
        $dashboardData = array_merge($dashboardData, $decoded['data']);
    }
}

if (empty($dashboardData['monthly_chart'])) {
    for ($i = 5; $i >= 0; $i--) {
        $dashboardData['monthly_chart'][] = ['month' => date('Y-m', strtotime("-$i months")), 'product_count' => 0];
    }
}

$counts = $dashboardData['counts'];
$recentActivity = $dashboardData['recent_activity'];
$monthlyChart = $dashboardData['monthly_chart'];

$statsCards = [
    ['label' => 'Pending Sellers', 'value' => $counts['pending_sellers'] ?? 0, 'icon' => '🏢', 'href' => 'sellers.php?status=Pending', 'color' => '#ca8a04'],
    ['label' => 'Pending Products', 'value' => $counts['pending_products'] ?? 0, 'icon' => '📦', 'href' => 'products.php?status=Pending', 'color' => '#2563eb'],
    ['label' => 'Purchase Requirements', 'value' => ($counts['pending_requirements'] ?? 0) + ($counts['approved_requirements'] ?? 0), 'icon' => '🛒', 'href' => 'requirements.php', 'color' => '#7c3aed'],
    ['label' => 'Total Orders', 'value' => $counts['total_orders'] ?? 0, 'icon' => '📋', 'href' => 'orders.php', 'color' => '#16a34a'],
    ['label' => 'Total Products', 'value' => $counts['total_products'] ?? 0, 'icon' => '🏷️', 'href' => 'products.php', 'color' => '#0891b2'],
    ['label' => 'Total RFQs', 'value' => $counts['total_rfqs'] ?? 0, 'icon' => '📝', 'href' => 'rfqs.php', 'color' => '#dc2626'],
];

$actionIcons = [
    'created' => '➕',
    'updated' => '✏️',
    'deleted' => '🗑️',
    'approved' => '✅',
    'rejected' => '❌',
    'suspended' => '⏸️',
    'login' => '🔐',
    'logout' => '🚪',
    'sent' => '📧',
    'uploaded' => '📤',
    'viewed' => '👁️',
];
?>

<div class="stats-grid">
    <?php foreach ($statsCards as $stat): ?>
    <a href="<?php echo htmlspecialchars($stat['href']); ?>" style="text-decoration: none;">
        <div class="stat-card" style="cursor: pointer;">
            <div class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></div>
            <div class="stat-value"><?php echo number_format((int)$stat['value']); ?></div>
            <div class="stat-icon"><?php echo $stat['icon']; ?></div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📈 Monthly Product Additions</h2>
            <span style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Last 6 months</span>
        </div>
        <div class="chart-container">
            <div class="chart-bars">
                <?php
                $maxVal = 1;
                foreach ($monthlyChart as $row) $maxVal = max($maxVal, (int)$row['product_count']);
                foreach ($monthlyChart as $row):
                    $pct = ((int)$row['product_count'] / $maxVal) * 100;
                    $monthLabel = date('M Y', strtotime($row['month'] . '-01'));
                ?>
                <div class="chart-bar-group">
                    <div class="chart-bar" style="height: <?php echo max(2, $pct); ?>%;">
                        <span class="chart-bar-value"><?php echo (int)$row['product_count']; ?></span>
                    </div>
                    <div class="chart-label"><?php echo htmlspecialchars($monthLabel); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🕐 Recent Activity</h2>
            <a href="logs.php" style="font-size: 0.8rem; color: #D4AF37; text-decoration: none;">View All →</a>
        </div>
        <ul class="activity-list">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach ($recentActivity as $act):
                    $action = $act['action'] ?? 'action';
                    $icon = $actionIcons[$action] ?? '📌';
                    $details = $act['details'];
                    if (is_string($details)) {
                        $dec = json_decode($details, true);
                        if ($dec) $details = $dec;
                    }
                    $detailText = '';
                    if (is_array($details)) {
                        $parts = [];
                        foreach (['name', 'title', 'email', 'company_name'] as $k) {
                            if (!empty($details[$k])) $parts[] = $details[$k];
                        }
                        $detailText = implode(' - ', array_slice($parts, 0, 2));
                    }
                    $entityType = $act['entity_type'] ? ' (' . $act['entity_type'] . ')' : '';
                ?>
                <li class="activity-item">
                    <div class="activity-icon"><?php echo $icon; ?></div>
                    <div class="activity-content">
                        <div class="activity-text">
                            <strong style="color: #fff;"><?php echo htmlspecialchars($act['user_name'] ?? 'System'); ?></strong>
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $action)) . $entityType); ?>
                            <?php if ($detailText): ?>
                                <span style="color: #D4AF37;">«<?php echo htmlspecialchars(mb_strimwidth($detailText, 0, 40, '...')); ?>»</span>
                            <?php endif; ?>
                        </div>
                        <div class="activity-meta">
                            <?php
                            $ts = strtotime($act['created_at'] ?? '');
                            if ($ts) {
                                $diff = time() - $ts;
                                if ($diff < 60) echo $diff . ' seconds ago';
                                elseif ($diff < 3600) echo floor($diff/60) . ' minutes ago';
                                elseif ($diff < 86400) echo floor($diff/3600) . ' hours ago';
                                else echo date('M j, g:i A', $ts);
                            }
                            ?>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="activity-item">
                    <div class="activity-icon">📭</div>
                    <div class="activity-content">
                        <div class="activity-text" style="color: rgba(255,255,255,0.5);">No recent activity recorded yet.</div>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
