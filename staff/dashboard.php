<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('staff');

$database = new Database();
$db = $database->getConnection();

// Get assigned orders
$assigned_orders_query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone
                         FROM orders o 
                         JOIN users u ON o.customer_id = u.id 
                         WHERE o.staff_id = ? OR o.staff_id IS NULL
                         ORDER BY o.order_date DESC";
$assigned_orders_stmt = $db->prepare($assigned_orders_query);
$assigned_orders_stmt->execute([getUserId()]);
$assigned_orders = $assigned_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE orders SET status = ?, staff_id = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$new_status, getUserId(), $order_id]);
    
    header("Location: dashboard.php?updated=1");
    exit();
}

// Get statistics
$stats = [];

// Today's orders
$today_orders_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE() AND (staff_id = ? OR staff_id IS NULL)";
$today_orders_stmt = $db->prepare($today_orders_query);
$today_orders_stmt->execute([getUserId()]);
$stats['today_orders'] = $today_orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Preparing orders
$preparing_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'preparing' AND staff_id = ?";
$preparing_stmt = $db->prepare($preparing_query);
$preparing_stmt->execute([getUserId()]);
$stats['preparing_orders'] = $preparing_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Completed today
$completed_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'ready' AND staff_id = ? AND DATE(order_date) = CURDATE()";
$completed_stmt = $db->prepare($completed_query);
$completed_stmt->execute([getUserId()]);
$stats['completed_today'] = $completed_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Food Delivery</title>
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
        
        .sidebar-header p {
            color: #666;
            font-size: 0.9rem;
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
        
        .welcome h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .welcome p {
            color: #666;
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
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #ff884d;
        }
        
        .stat-card i {
            font-size: 3rem;
            color: #ff884d;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .stat-card p {
            color: #666;
            font-weight: 500;
        }
        
        .orders-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .orders-section h2 {
            margin-bottom: 1.5rem;
            color: #333;
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
        
        .order-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Staff Panel</h2>
                <p>Kitchen Management</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="uil uil-dashboard"></i> Dashboard</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> All Orders</a></li>
                <li><a href="menu.php"><i class="uil uil-restaurant"></i> Menu Items</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div class="welcome">
                    <h1>Staff Dashboard</h1>
                    <p>Welcome back, <?php echo getUserName(); ?>! Ready to prepare some delicious orders?</p>
                </div>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="success-message">
                    Order status updated successfully!
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="uil uil-calendar-alt"></i>
                    <h3><?php echo $stats['today_orders']; ?></h3>
                    <p>Today's Orders</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-fire"></i>
                    <h3><?php echo $stats['preparing_orders']; ?></h3>
                    <p>Currently Preparing</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-check-circle"></i>
                    <h3><?php echo $stats['completed_today']; ?></h3>
                    <p>Completed Today</p>
                </div>
            </div>
            
            <div class="orders-section">
                <h2>Orders to Process</h2>
                <?php if (empty($assigned_orders)): ?>
                    <p>No orders to process at the moment.</p>
                <?php else: ?>
                    <?php foreach ($assigned_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <p>Customer: <?php echo $order['customer_name']; ?></p>
                                    <p>Phone: <?php echo $order['customer_phone']; ?></p>
                                    <p>Amount: Rs. <?php echo number_format($order['total_amount'], 0); ?></p>
                                    <p>Order Time: <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                                </div>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="order-actions">
                                <?php if ($order['status'] == 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" name="update_status" class="btn btn-primary">
                                            <i class="uil uil-check"></i> Confirm Order
                                        </button>
                                    </form>
                                <?php elseif ($order['status'] == 'confirmed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="status" value="preparing">
                                        <button type="submit" name="update_status" class="btn btn-info">
                                            <i class="uil uil-fire"></i> Start Preparing
                                        </button>
                                    </form>
                                <?php elseif ($order['status'] == 'preparing'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="status" value="ready">
                                        <button type="submit" name="update_status" class="btn btn-success">
                                            <i class="uil uil-check-circle"></i> Mark Ready
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                    <i class="uil uil-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
