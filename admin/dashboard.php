<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total orders
$orders_query = "SELECT COUNT(*) as count FROM orders";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute();
$stats['total_orders'] = $orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total customers
$customers_query = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
$customers_stmt = $db->prepare($customers_query);
$customers_stmt->execute();
$stats['total_customers'] = $customers_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total revenue
$revenue_query = "SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'cancelled'";
$revenue_stmt = $db->prepare($revenue_query);
$revenue_stmt->execute();
$stats['total_revenue'] = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

// Pending orders
$pending_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$pending_stmt = $db->prepare($pending_query);
$pending_stmt->execute();
$stats['pending_orders'] = $pending_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent orders
$recent_orders_query = "SELECT o.*, u.name as customer_name 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       ORDER BY o.order_date DESC 
                       LIMIT 10";
$recent_orders_stmt = $db->prepare($recent_orders_query);
$recent_orders_stmt->execute();
$recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Delivery</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .recent-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .recent-section h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
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
        
        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
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
        
        .btn-outline {
            background: transparent;
            color: #ff884d;
            border: 2px solid #ff884d;
        }
        
        .btn-outline:hover {
            background: #ff884d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Food Delivery System</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="uil uil-dashboard"></i> Dashboard</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> Manage Orders</a></li>
                <li><a href="menu.php"><i class="uil uil-restaurant"></i> Manage Menu</a></li>
                <li><a href="users.php"><i class="uil uil-users-alt"></i> Manage Users</a></li>
                <li><a href="staff.php"><i class="uil uil-user-check"></i> Staff Management</a></li>
                <li><a href="reports.php"><i class="uil uil-chart"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div class="welcome">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo getUserName(); ?>!</p>
                </div>
                <div>
                    <a href="orders.php" class="btn btn-primary">
                        <i class="uil uil-plus"></i> New Order
                    </a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="uil uil-receipt"></i>
                    <h3><?php echo $stats['total_orders']; ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-users-alt"></i>
                    <h3><?php echo $stats['total_customers']; ?></h3>
                    <p>Total Customers</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-money-bill"></i>
                    <h3>Rs. <?php echo number_format($stats['total_revenue'], 0); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-clock"></i>
                    <h3><?php echo $stats['pending_orders']; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="recent-section">
                <h2>Recent Orders</h2>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['customer_name']; ?></td>
                                <td>Rs. <?php echo number_format($order['total_amount'], 0); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">
                                        <i class="uil uil-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
