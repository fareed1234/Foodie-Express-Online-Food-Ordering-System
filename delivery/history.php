<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('delivery');

$database = new Database();
$db = $database->getConnection();

// Get delivery history for this delivery person
$history_query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone
                 FROM orders o 
                 JOIN users u ON o.customer_id = u.id 
                 WHERE o.delivery_id = ? AND o.status = 'delivered'
                 ORDER BY o.delivery_date DESC";
$history_stmt = $db->prepare($history_query);
$history_stmt->execute([getUserId()]);
$delivery_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];

// Total deliveries
$total_deliveries_query = "SELECT COUNT(*) as count FROM orders WHERE delivery_id = ? AND status = 'delivered'";
$total_deliveries_stmt = $db->prepare($total_deliveries_query);
$total_deliveries_stmt->execute([getUserId()]);
$stats['total_deliveries'] = $total_deliveries_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// This month's deliveries
$month_deliveries_query = "SELECT COUNT(*) as count FROM orders WHERE delivery_id = ? AND status = 'delivered' AND MONTH(delivery_date) = MONTH(CURDATE()) AND YEAR(delivery_date) = YEAR(CURDATE())";
$month_deliveries_stmt = $db->prepare($month_deliveries_query);
$month_deliveries_stmt->execute([getUserId()]);
$stats['month_deliveries'] = $month_deliveries_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total earnings (assuming Rs. 50 per delivery)
$stats['total_earnings'] = $stats['total_deliveries'] * 50;

// Filter by date range
$date_filter = $_GET['date'] ?? '';
$filtered_history = $delivery_history;

if ($date_filter == 'today') {
    $filtered_history = array_filter($delivery_history, function($order) {
        return date('Y-m-d', strtotime($order['delivery_date'])) == date('Y-m-d');
    });
} elseif ($date_filter == 'week') {
    $filtered_history = array_filter($delivery_history, function($order) {
        return strtotime($order['delivery_date']) >= strtotime('-7 days');
    });
} elseif ($date_filter == 'month') {
    $filtered_history = array_filter($delivery_history, function($order) {
        return date('Y-m', strtotime($order['delivery_date'])) == date('Y-m');
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery History - Food Delivery</title>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="../assets/css/delivery-modern.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="uil uil-truck"></i>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="uil uil-dashboard"></i>
                </a>
                <a href="deliveries.php" class="nav-item">
                    <i class="uil uil-package"></i>
                </a>
                <a href="history.php" class="nav-item active">
                    <i class="uil uil-history"></i>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="uil uil-user"></i>
                </a>
                <a href="../logout.php" class="nav-item logout">
                    <i class="uil uil-signout"></i>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <div class="welcome-section">
                        <h1>Delivery History</h1>
                        <p>Your completed deliveries and earnings</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-primary">
                            <i class="uil uil-history"></i>
                            History
                        </button>
                        <a href="dashboard.php" class="btn-secondary">Dashboard</a>
                    </div>
                </div>
            </header>

            <!-- Stats Section -->
            <section class="stats-section">
                <h2>Performance Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card earnings">
                        <div class="stat-icon">
                            <i class="uil uil-money-bill"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Rs. <?php echo number_format($stats['total_earnings'], 0); ?></h3>
                            <p>Total Earnings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_deliveries']; ?></h3>
                            <p>Total Deliveries</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['month_deliveries']; ?></h3>
                            <p>This Month</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filter Section -->
            <section class="filter-section">
                <div class="filter-tabs">
                    <a href="history.php" class="filter-tab <?php echo $date_filter == '' ? 'active' : ''; ?>">
                        All Time
                    </a>
                    <a href="history.php?date=today" class="filter-tab <?php echo $date_filter == 'today' ? 'active' : ''; ?>">
                        Today
                    </a>
                    <a href="history.php?date=week" class="filter-tab <?php echo $date_filter == 'week' ? 'active' : ''; ?>">
                        This Week
                    </a>
                    <a href="history.php?date=month" class="filter-tab <?php echo $date_filter == 'month' ? 'active' : ''; ?>">
                        This Month
                    </a>
                </div>
            </section>

            <!-- History Section -->
            <section class="deliveries-section">
                <div class="section-header">
                    <h2>Delivery History (<?php echo count($filtered_history); ?>)</h2>
                </div>
                
                <?php if (empty($filtered_history)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="uil uil-history"></i>
                        </div>
                        <h3>No delivery history</h3>
                        <p>No completed deliveries found for the selected period.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php foreach ($filtered_history as $order): ?>
                            <div class="order-card completed">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h3>Order #<?php echo $order['id']; ?></h3>
                                        <span class="status-badge status-delivered">
                                            Delivered
                                        </span>
                                    </div>
                                    <div class="order-amount">
                                        <span class="amount">Rs. <?php echo number_format($order['total_amount'], 0); ?></span>
                                        <span class="earnings">+Rs. 50</span>
                                    </div>
                                </div>
                                
                                <div class="customer-info">
                                    <div class="customer-details">
                                        <h4><?php echo $order['customer_name']; ?></h4>
                                        <p><i class="uil uil-phone"></i> <?php echo $order['customer_phone']; ?></p>
                                        <p><i class="uil uil-clock"></i> Ordered: <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                                        <p><i class="uil uil-check"></i> Delivered: <?php echo date('M d, Y H:i', strtotime($order['delivery_date'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="delivery-address">
                                    <h5><i class="uil uil-location-point"></i> Delivery Address</h5>
                                    <p><?php echo $order['delivery_address']; ?></p>
                                    <?php if ($order['notes']): ?>
                                        <p class="notes"><strong>Notes:</strong> <?php echo $order['notes']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
