<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentScript = basename($_SERVER['PHP_SELF']);
$isLoginPage = ($currentScript === 'login.php');

if (!$isLoginPage) {
    $adminRoles = ['admin', 'super_admin', 'manager', 'editor'];
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $adminRoles, true)) {
        header('Location: login.php');
        exit;
    }
}

$activePage = $currentScript;
$navItems = [
    'index.php' => ['icon' => '📊', 'label' => 'Dashboard'],
    'sellers.php' => ['icon' => '🏢', 'label' => 'Sellers'],
    'buyers.php' => ['icon' => '👥', 'label' => 'Buyers'],
    'products.php' => ['icon' => '📦', 'label' => 'Products'],
    'requirements.php' => ['icon' => '🛒', 'label' => 'Purchase Requirements'],
    'orders.php' => ['icon' => '📋', 'label' => 'Orders'],
    'rfqs.php' => ['icon' => '📝', 'label' => 'RFQs'],
    'contacts.php' => ['icon' => '✉️', 'label' => 'Contacts'],
    'logs.php' => ['icon' => '📜', 'label' => 'Activity Logs'],
    'settings.php' => ['icon' => '⚙️', 'label' => 'Settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - OPCIEAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-dark: #0a0f1c;
            --color-navy: #1a2340;
            --color-navy-light: #2a3760;
            --color-gold: #D4AF37;
            --color-gold-light: #E8C96B;
            --color-gold-dark: #B8962E;
            --color-white: #FFFFFF;
            --color-danger: #dc2626;
            --color-success: #16a34a;
            --color-warning: #ca8a04;
            --color-info: #2563eb;
        }
        body {
            background-color: var(--color-dark);
            color: var(--color-white);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, var(--color-navy) 0%, var(--color-dark) 100%);
            border-right: 1px solid rgba(212, 175, 55, 0.2);
            min-height: 100vh;
            width: 260px;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar-logo {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .sidebar-logo-text {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-gold-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: 0.05em;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.875rem 1.25rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            cursor: pointer;
        }
        .nav-item:hover {
            background-color: rgba(212, 175, 55, 0.1);
            color: var(--color-white);
        }
        .nav-item.active {
            background-color: rgba(212, 175, 55, 0.15);
            color: var(--color-gold);
            border-left-color: var(--color-gold);
        }
        .nav-icon {
            font-size: 1.125rem;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }
        .nav-label {
            font-size: 0.9rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .sidebar-logo-text {
            display: none;
        }
        .topbar {
            background-color: var(--color-navy);
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--color-white);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        .sidebar-toggle:hover {
            background-color: rgba(212, 175, 55, 0.1);
            color: var(--color-gold);
        }
        .page-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-white);
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-gold-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--color-dark);
            font-size: 0.9rem;
        }
        .admin-name {
            font-size: 0.9rem;
            font-weight: 600;
        }
        .admin-role {
            font-size: 0.75rem;
            color: var(--color-gold);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: transparent;
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #fca5a5;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .logout-btn:hover {
            background-color: rgba(220, 38, 38, 0.2);
            border-color: var(--color-danger);
            color: #ef4444;
        }
        .main-content {
            flex: 1;
            padding: 1.5rem;
            overflow-x: hidden;
        }
        .card {
            background-color: var(--color-navy);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }
        .card:hover {
            border-color: rgba(212, 175, 55, 0.3);
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--color-white);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-gold-dark) 100%);
            color: var(--color-dark);
            font-weight: 600;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-danger {
            background-color: var(--color-danger);
            color: var(--color-white);
            font-weight: 600;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-danger:hover {
            background-color: #b91c1c;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }
        .btn-secondary {
            background-color: var(--color-navy-light);
            color: var(--color-white);
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(212, 175, 55, 0.2);
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-secondary:hover {
            background-color: rgba(42, 55, 96, 0.8);
            border-color: rgba(212, 175, 55, 0.4);
        }
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending {
            background-color: rgba(202, 138, 4, 0.15);
            color: #facc15;
            border: 1px solid rgba(202, 138, 4, 0.3);
        }
        .status-approved, .status-published, .status-success {
            background-color: rgba(22, 163, 74, 0.15);
            color: #4ade80;
            border: 1px solid rgba(22, 163, 74, 0.3);
        }
        .status-rejected, .status-deleted {
            background-color: rgba(220, 38, 38, 0.15);
            color: #f87171;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }
        .status-suspended, .status-hidden, .status-draft {
            background-color: rgba(107, 114, 128, 0.15);
            color: #d1d5db;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        .status-fake {
            background-color: rgba(168, 85, 247, 0.15);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }
        .status-closed {
            background-color: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(37, 99, 235, 0.3);
        }
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .data-table thead th {
            background-color: rgba(212, 175, 55, 0.08);
            padding: 0.875rem 1rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-gold);
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }
        .data-table tbody td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .data-table tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.05);
        }
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        .form-input, .form-select, .form-textarea {
            background-color: var(--color-dark);
            border: 1px solid rgba(212, 175, 55, 0.2);
            color: var(--color-white);
            padding: 0.625rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            width: 100%;
            transition: all 0.2s ease;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--color-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.375rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            align-items: center;
        }
        .search-input {
            position: relative;
            flex: 1;
            min-width: 240px;
            max-width: 360px;
        }
        .search-input input {
            padding-left: 2.5rem;
        }
        .search-input::before {
            content: '🔍';
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.875rem;
            opacity: 0.6;
        }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            flex-wrap: wrap;
            gap: 1rem;
        }
        .pagination-info {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }
        .pagination-buttons {
            display: flex;
            gap: 0.375rem;
            flex-wrap: wrap;
        }
        .page-btn {
            padding: 0.5rem 0.875rem;
            background-color: var(--color-navy-light);
            border: 1px solid rgba(212, 175, 55, 0.15);
            color: var(--color-white);
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .page-btn:hover:not(:disabled) {
            background-color: rgba(212, 175, 55, 0.1);
            border-color: var(--color-gold);
        }
        .page-btn.active {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-gold-dark) 100%);
            color: var(--color-dark);
            border-color: var(--color-gold);
        }
        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(10, 15, 28, 0.85);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 1rem;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal {
            background-color: var(--color-navy);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 0.875rem;
            width: 100%;
            max-width: 640px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .modal-lg {
            max-width: 900px;
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--color-gold);
        }
        .modal-close {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            transition: all 0.2s ease;
        }
        .modal-close:hover {
            color: var(--color-gold);
            transform: rotate(90deg);
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: none;
        }
        .alert.show {
            display: block;
        }
        .alert-error {
            background-color: rgba(220, 38, 38, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }
        .alert-success {
            background-color: rgba(22, 163, 74, 0.15);
            color: #86efac;
            border: 1px solid rgba(22, 163, 74, 0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--color-navy) 0%, var(--color-navy-light) 100%);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 0.75rem;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--color-gold) 0%, var(--color-gold-dark) 100%);
        }
        .stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--color-white) 0%, var(--color-gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.15;
        }
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .activity-item {
            display: flex;
            gap: 0.875rem;
            padding: 0.875rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(212, 175, 55, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
        }
        .activity-content {
            flex: 1;
            min-width: 0;
        }
        .activity-text {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .activity-meta {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.25rem;
        }
        .chart-container {
            background-color: var(--color-dark);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-top: 1rem;
        }
        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            height: 200px;
            padding-top: 1rem;
        }
        .chart-bar-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .chart-bar {
            width: 100%;
            max-width: 50px;
            background: linear-gradient(180deg, var(--color-gold) 0%, var(--color-gold-dark) 100%);
            border-radius: 0.375rem 0.375rem 0 0;
            transition: all 0.3s ease;
            position: relative;
            min-height: 4px;
        }
        .chart-bar:hover {
            box-shadow: 0 0 12px rgba(212, 175, 55, 0.5);
        }
        .chart-bar-value {
            position: absolute;
            top: -1.5rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--color-gold);
            white-space: nowrap;
        }
        .chart-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
        }
        .action-dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: var(--color-navy);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 0.5rem;
            min-width: 180px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            z-index: 50;
            display: none;
            overflow: hidden;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.625rem 1rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.85);
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .dropdown-item:hover {
            background-color: rgba(212, 175, 55, 0.1);
            color: var(--color-gold);
        }
        .dropdown-item.danger:hover {
            background-color: rgba(220, 38, 38, 0.1);
            color: #f87171;
        }
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                z-index: 45;
            }
            .sidebar.mobile-open {
                left: 0;
            }
        }
        @media (max-width: 640px) {
            .main-content {
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
<div class="flex min-h-screen">
    <?php if (!$isLoginPage): ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="text-2xl">🏛️</div>
            <span class="sidebar-logo-text">OPCIEAS</span>
        </div>
        <nav class="py-3">
            <?php foreach ($navItems as $href => $item): ?>
            <a href="<?php echo htmlspecialchars($href); ?>"
               class="nav-item <?php echo $activePage === $href ? 'active' : ''; ?>">
                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                <span class="nav-label"><?php echo $item['label']; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <?php endif; ?>

    <div class="flex-1 flex flex-col">
        <?php if (!$isLoginPage): ?>
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggle">☰</button>
                <h1 class="page-title"><?php echo $navItems[$activePage]['label'] ?? 'Admin'; ?></h1>
            </div>
            <div class="topbar-right">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="hidden sm:block">
                        <div class="admin-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                        <div class="admin-role">Administrator</div>
                    </div>
                </div>
                <button class="logout-btn" onclick="handleLogout()">
                    <span>🚪</span>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </div>
        </header>
        <?php endif; ?>
        <main class="<?php echo !$isLoginPage ? 'main-content' : 'flex-1'; ?>">
