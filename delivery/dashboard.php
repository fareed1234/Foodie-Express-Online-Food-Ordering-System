<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('delivery');

$database = new Database();
$db = $database->getConnection();

// Get delivery orders
$delivery_orders_query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone
                         FROM orders o 
                         JOIN users u ON o.customer_id = u.id 
                         WHERE o.status IN ('ready', 'out_for_delivery') AND (o.delivery_id = ? OR o.delivery_id IS NULL)
                         ORDER BY o.order_date DESC";
$delivery_orders_stmt = $db->prepare($delivery_orders_query);
$delivery_orders_stmt->execute([getUserId()]);
$delivery_orders = $delivery_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delivery status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_delivery'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE orders SET status = ?, delivery_id = ?, delivery_date = ? WHERE id = ?";
    $delivery_date = ($new_status == 'delivered') ? date('Y-m-d H:i:s') : null;
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$new_status, getUserId(), $delivery_date, $order_id]);
    
    header("Location: dashboard.php?updated=1");
    exit();
}

// Get statistics
$stats = [];

// Today's deliveries
$today_deliveries_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE() AND delivery_id = ?";
$today_deliveries_stmt = $db->prepare($today_deliveries_query);
$today_deliveries_stmt->execute([getUserId()]);
$stats['today_deliveries'] = $today_deliveries_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Out for delivery
$out_for_delivery_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'out_for_delivery' AND delivery_id = ?";
$out_for_delivery_stmt = $db->prepare($out_for_delivery_query);
$out_for_delivery_stmt->execute([getUserId()]);
$stats['out_for_delivery'] = $out_for_delivery_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Completed today
$completed_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'delivered' AND delivery_id = ? AND DATE(delivery_date) = CURDATE()";
$completed_stmt = $db->prepare($completed_query);
$completed_stmt->execute([getUserId()]);
$stats['completed_today'] = $completed_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Food Delivery</title>
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
                <a href="dashboard.php" class="nav-item active">
                    <i class="uil uil-dashboard"></i>
                </a>
                <a href="deliveries.php" class="nav-item">
                    <i class="uil uil-package"></i>
                </a>
                <a href="history.php" class="nav-item">
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
                        <h1>Welcome, <?php echo getUserName(); ?>!</h1>
                        <p>Don't forget to have breakfast today yeeehaa!</p>
                    </div>
                    <div class="header-actions">
                        <a href="deliveries.php" class="btn-primary">
                            <i class="uil uil-truck"></i>
                            Deliveries
                        </a>
                        <a href="history.php" class="btn-secondary">History</a>
                        <a href="profile.php" class="btn-secondary">Profile</a>
                    </div>
                </div>
            </header>

            <?php if (isset($_GET['updated'])): ?>
                <div class="success-message">
                    <i class="uil uil-check-circle"></i>
                    Delivery status updated successfully!
                </div>
            <?php endif; ?>

            <!-- Stats Section -->
            <section class="stats-section">
                <h2>Today's Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['today_deliveries']; ?></h3>
                            <p>Today's Deliveries</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-truck"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['out_for_delivery']; ?></h3>
                            <p>Out for Delivery</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['completed_today']; ?></h3>
                            <p>Completed Today</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Active Deliveries -->
            <section class="deliveries-section">
                <h2>Active Delivery Orders</h2>
                <?php if (empty($delivery_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="uil uil-truck"></i>
                        </div>
                        <h3>No active deliveries</h3>
                        <p>All caught up! No delivery orders at the moment.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php foreach ($delivery_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h3>Order #<?php echo $order['id']; ?></h3>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $order['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="order-amount">
                                        <span class="amount">Rs. <?php echo number_format($order['total_amount'], 0); ?></span>
                                    </div>
                                </div>
                                
                                <div class="customer-info">
                                    <div class="customer-details">
                                        <h4><?php echo $order['customer_name']; ?></h4>
                                        <p><i class="uil uil-phone"></i> <?php echo $order['customer_phone']; ?></p>
                                        <p><i class="uil uil-clock"></i> <?php echo date('H:i', strtotime($order['order_date'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="delivery-address">
                                    <h5><i class="uil uil-location-point"></i> Delivery Address</h5>
                                    <p><?php echo $order['delivery_address']; ?></p>
                                    <?php if ($order['notes']): ?>
                                        <p class="notes"><strong>Notes:</strong> <?php echo $order['notes']; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-actions">
                                    <?php if ($order['status'] == 'ready'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="out_for_delivery">
                                            <button type="submit" name="update_delivery" class="btn-action btn-pickup">
                                                <i class="uil uil-truck"></i> Pick Up
                                            </button>
                                        </form>
                                    <?php elseif ($order['status'] == 'out_for_delivery'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="delivered">
                                            <button type="submit" name="update_delivery" class="btn-action btn-deliver">
                                                <i class="uil uil-check-circle"></i> Delivered
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="tel:<?php echo $order['customer_phone']; ?>" class="btn-action btn-call">
                                        <i class="uil uil-phone"></i> Call
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <div class="sidebar-content">
                <div class="delivery-plan">
                    <h3>Your delivery plan</h3>
                    <div class="plan-date">
                        <i class="uil uil-calendar-alt"></i>
                        <span>Today, <?php echo date('d M Y'); ?></span>
                    </div>
                </div>
                
                <div class="quick-stats">
                    <div class="quick-stat-item">
                        <span class="stat-label">Earnings Today</span>
                        <span class="stat-value">Rs. <?php echo $stats['completed_today'] * 50; ?></span>
                        <span class="stat-status completed">Completed</span>
                    </div>
                    
                    <div class="quick-stat-item">
                        <span class="stat-label">Active Orders</span>
                        <span class="stat-value"><?php echo $stats['out_for_delivery']; ?></span>
                        <span class="stat-status pending">In Progress</span>
                    </div>
                </div>
                
                <div class="recent-activity">
                    <h4>Recent Activity</h4>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="uil uil-check-circle"></i>
                            </div>
                            <div class="activity-content">
                                <p>Order delivered successfully</p>
                                <span><?php echo date('H:i'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</body>
</html>
