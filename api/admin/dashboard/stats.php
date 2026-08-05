<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $pdo->beginTransaction();

    $counts = [];

    $stmt = $pdo->query("SELECT COUNT(*) FROM seller_profiles");
    $counts['total_sellers'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM seller_profiles WHERE status = 'Pending'");
    $counts['pending_sellers'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM seller_profiles WHERE status = 'Approved'");
    $counts['approved_sellers'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM buyer_profiles");
    $counts['total_buyers'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM buyer_profiles WHERE status = 'Pending'");
    $counts['pending_buyers'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM buyer_profiles WHERE status = 'Approved'");
    $counts['approved_buyers'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Pending'");
    $counts['pending_products'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $counts['total_products'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $counts['total_orders'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM purchase_requirements WHERE status = 'Pending'");
    $counts['pending_requirements'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM purchase_requirements WHERE status = 'Approved'");
    $counts['approved_requirements'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM rfqs");
    $counts['total_rfqs'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM rfqs WHERE status = 'New'");
    $counts['new_rfqs'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM rfqs WHERE status IN ('New', 'In Review')");
    $counts['pending_rfqs'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM rfqs WHERE status = 'Quoted'");
    $counts['quoted_rfqs'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM rfqs WHERE status = 'Closed'");
    $counts['closed_rfqs'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM rfqs WHERE status = 'Rejected'");
    $counts['rejected_rfqs'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT al.id, al.user_id, al.action, al.module AS entity_type, al.subject_id AS entity_id, al.description AS details, al.created_at, u.name AS user_name
        FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT
            YEAR(created_at) AS year,
            MONTH(created_at) AS month,
            DATE_FORMAT(created_at, '%Y-%m') AS month_label,
            COUNT(*) AS product_count
        FROM products
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at), month_label
        ORDER BY year ASC, month ASC");
    $stmt->execute();
    $chart_raw = $stmt->fetchAll();

    $monthly_chart = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthKey = date('Y-m', strtotime("-$i months"));
        $count = 0;
        foreach ($chart_raw as $row) {
            if ($row['month_label'] === $monthKey) {
                $count = (int)$row['product_count'];
                break;
            }
        }
        $monthly_chart[] = ['month' => $monthKey, 'product_count' => $count];
    }

    $pdo->commit();

    json_response([
        'success' => true,
        'data' => [
            'counts' => $counts,
            'recent_activity' => $recent_activity,
            'monthly_chart' => $monthly_chart
        ]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
