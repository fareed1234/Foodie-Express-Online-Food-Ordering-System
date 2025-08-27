<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get user's orders
$orders_query = "SELECT o.*, COUNT(oi.id) as item_count 
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.customer_id = ? 
                GROUP BY o.id 
                ORDER BY o.order_date DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([getUserId()]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart count
$cart_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
$cart_stmt = $db->prepare($cart_query);
$cart_stmt->execute([getUserId()]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Food Delivery</title>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #fefefe;
            color: #333;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding: 2rem 0;
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-header h2 {
            color: #ff884d;
            font-size: 1.5rem;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #ff884d;
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        
        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .orders-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .order-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .order-info h3 {
            margin-bottom: 0.5rem;
        }
        
        .order-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-preparing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-ready {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-out-for-delivery {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-orders {
            text-align: center;
            color: #666;
            padding: 3rem;
        }
        
        .no-orders i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: #ff884d;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e6743d;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>FoodDelivery</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="uil uil-dashboard"></i> Dashboard</a></li>
                <li><a href="menu.php"><i class="uil uil-restaurant"></i> Browse Menu</a></li>
                <li><a href="cart.php"><i class="uil uil-shopping-cart"></i> My Cart (<?php echo $cart_count; ?>)</a></li>
                <li><a href="orders.php" class="active"><i class="uil uil-receipt"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="uil uil-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>My Orders</h1>
                <p>Track your order history and status</p>
            </div>
            
            <div class="orders-container">
                <h2>Order History</h2>
                <?php if (empty($orders)): ?>
                    <div class="no-orders">
                        <i class="uil uil-receipt"></i>
                        <h2>No orders yet</h2>
                        <p>You haven't placed any orders yet. <a href="menu.php">Start ordering now!</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <p><?php echo $order['item_count']; ?> items • Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p>Ordered on: <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                                    <p>Delivery Address: <?php echo $order['delivery_address']; ?></p>
                                    <?php if ($order['notes']): ?>
                                        <p>Notes: <?php echo $order['notes']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge status-<?php echo str_replace('_', '-', $order['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
