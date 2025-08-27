<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get user's recent orders
$orders_query = "SELECT o.*, COUNT(oi.id) as item_count 
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.customer_id = ? 
                GROUP BY o.id 
                ORDER BY o.order_date DESC 
                LIMIT 5";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([getUserId()]);
$recent_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Customer Dashboard - Food Delivery</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome {
            color: #333;
        }
        
        .welcome h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome p {
            color: #666;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #ff884d;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e6743d;
        }
        
        .btn-outline {
            background: transparent;
            color: #ff884d;
            border: 2px solid #ff884d;
        }
        
        .btn-outline:hover {
            background: #ff884d;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 3rem;
            color: #ff884d;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
        }
        
        .recent-orders {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .recent-orders h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info h4 {
            margin-bottom: 0.5rem;
        }
        
        .order-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-status {
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
        
        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .no-orders {
            text-align: center;
            color: #666;
            padding: 2rem;
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
                <li><a href="dashboard.php" class="active"><i class="uil uil-dashboard"></i> Dashboard</a></li>
                <li><a href="menu.php"><i class="uil uil-restaurant"></i> Browse Menu</a></li>
                <li><a href="cart.php"><i class="uil uil-shopping-cart"></i> My Cart (<?php echo $cart_count; ?>)</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="uil uil-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div class="welcome">
                    <h1>Welcome, <?php echo getUserName(); ?>!</h1>
                    <p>Ready to order some delicious food?</p>
                </div>
                <div class="header-actions">
                    <a href="menu.php" class="btn btn-primary">
                        <i class="uil uil-restaurant"></i> Order Now
                    </a>
                    <a href="cart.php" class="btn btn-outline">
                        <i class="uil uil-shopping-cart"></i> Cart (<?php echo $cart_count; ?>)
                    </a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="uil uil-receipt"></i>
                    <h3><?php echo count($recent_orders); ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-shopping-cart"></i>
                    <h3><?php echo $cart_count; ?></h3>
                    <p>Items in Cart</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-star"></i>
                    <h3>4.8</h3>
                    <p>Your Rating</p>
                </div>
            </div>
            
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <?php if (empty($recent_orders)): ?>
                    <div class="no-orders">
                        <p>No orders yet. <a href="menu.php">Start ordering now!</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h4>Order #<?php echo $order['id']; ?></h4>
                                <p><?php echo $order['item_count']; ?> items • Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                                <p><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                            </div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
