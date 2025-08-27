<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('delivery');

$database = new Database();
$db = $database->getConnection();

// Get all delivery orders (including completed ones)
$all_orders_query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone
                    FROM orders o 
                    JOIN users u ON o.customer_id = u.id 
                    WHERE o.status IN ('ready', 'out_for_delivery', 'delivered') 
                    ORDER BY o.order_date DESC";
$all_orders_stmt = $db->prepare($all_orders_query);
$all_orders_stmt->execute();
$all_orders = $all_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delivery status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_delivery'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE orders SET status = ?, delivery_id = ?, delivery_date = ? WHERE id = ?";
    $delivery_date = ($new_status == 'delivered') ? date('Y-m-d H:i:s') : null;
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$new_status, getUserId(), $delivery_date, $order_id]);
    
    header("Location: deliveries.php?updated=1");
    exit();
}

// Filter orders
$filter = $_GET['filter'] ?? 'all';
$filtered_orders = $all_orders;

if ($filter == 'ready') {
    $filtered_orders = array_filter($all_orders, function($order) {
        return $order['status'] == 'ready';
    });
} elseif ($filter == 'out_for_delivery') {
    $filtered_orders = array_filter($all_orders, function($order) {
        return $order['status'] == 'out_for_delivery';
    });
} elseif ($filter == 'delivered') {
    $filtered_orders = array_filter($all_orders, function($order) {
        return $order['status'] == 'delivered';
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Deliveries - Food Delivery</title>
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
                <a href="deliveries.php" class="nav-item active">
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
                        <h1>All Deliveries</h1>
                        <p>Manage all your delivery orders</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-primary">
                            <i class="uil uil-package"></i>
                            All Orders
                        </button>
                        <a href="dashboard.php" class="btn-secondary">Dashboard</a>
                    </div>
                </div>
            </header>

            <?php if (isset($_GET['updated'])): ?>
                <div class="success-message">
                    <i class="uil uil-check-circle"></i>
                    Delivery status updated successfully!
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <section class="filter-section">
                <div class="filter-tabs">
                    <a href="deliveries.php?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                        All Orders
                    </a>
                    <a href="deliveries.php?filter=ready" class="filter-tab <?php echo $filter == 'ready' ? 'active' : ''; ?>">
                        Ready
                    </a>
                    <a href="deliveries.php?filter=out_for_delivery" class="filter-tab <?php echo $filter == 'out_for_delivery' ? 'active' : ''; ?>">
                        Out for Delivery
                    </a>
                    <a href="deliveries.php?filter=delivered" class="filter-tab <?php echo $filter == 'delivered' ? 'active' : ''; ?>">
                        Delivered
                    </a>
                </div>
            </section>

            <!-- Orders Section -->
            <section class="deliveries-section">
                <div class="section-header">
                    <h2>Delivery Orders (<?php echo count($filtered_orders); ?>)</h2>
                </div>
                
                <?php if (empty($filtered_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="uil uil-package"></i>
                        </div>
                        <h3>No orders found</h3>
                        <p>No delivery orders match the selected filter.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php foreach ($filtered_orders as $order): ?>
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
                                        <p><i class="uil uil-clock"></i> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                                        <?php if ($order['delivery_date']): ?>
                                            <p><i class="uil uil-check"></i> Delivered: <?php echo date('M d, Y H:i', strtotime($order['delivery_date'])); ?></p>
                                        <?php endif; ?>
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
    </div>
</body>
</html>
