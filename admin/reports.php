<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get date range from query parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Sales Report
$sales_query = "SELECT 
    DATE(order_date) as date,
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE status != 'cancelled' 
    AND DATE(order_date) BETWEEN ? AND ?
    GROUP BY DATE(order_date)
    ORDER BY date DESC";
$sales_stmt = $db->prepare($sales_query);
$sales_stmt->execute([$start_date, $end_date]);
$sales_data = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Popular Items Report
$popular_items_query = "SELECT 
    mi.name,
    mi.category,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    AND DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY mi.id, mi.name, mi.category
    ORDER BY total_quantity DESC
    LIMIT 10";
$popular_items_stmt = $db->prepare($popular_items_query);
$popular_items_stmt->execute([$start_date, $end_date]);
$popular_items = $popular_items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Order Status Report
$status_query = "SELECT 
    status,
    COUNT(*) as count,
    SUM(total_amount) as revenue
    FROM orders 
    WHERE DATE(order_date) BETWEEN ? AND ?
    GROUP BY status
    ORDER BY count DESC";
$status_stmt = $db->prepare($status_query);
$status_stmt->execute([$start_date, $end_date]);
$status_data = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary Statistics
$summary_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as total_revenue,
    AVG(CASE WHEN status != 'cancelled' THEN total_amount ELSE NULL END) as avg_order_value,
    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders 
    WHERE DATE(order_date) BETWEEN ? AND ?";
$summary_stmt = $db->prepare($summary_query);
$summary_stmt->execute([$start_date, $end_date]);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
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
        
        .date-filter {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .date-filter input {
            padding: 0.5rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
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
        
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .report-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .report-section.full-width {
            grid-column: 1 / -1;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .report-table th,
        .report-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .report-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
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
        
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #ff884d;
            color: white;
            border-radius: 15px;
            font-size: 0.8rem;
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
                <li><a href="dashboard.php"><i class="uil uil-dashboard"></i> Dashboard</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> Manage Orders</a></li>
                <li><a href="menu.php"><i class="uil uil-restaurant"></i> Manage Menu</a></li>
                <li><a href="users.php"><i class="uil uil-users-alt"></i> Manage Users</a></li>
                <li><a href="staff.php"><i class="uil uil-user-check"></i> Staff Management</a></li>
                <li><a href="reports.php" class="active"><i class="uil uil-chart"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div>
                    <h1>Reports & Analytics</h1>
                    <p>View detailed reports and analytics</p>
                </div>
                <form method="GET" class="date-filter">
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    <span>to</span>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="uil uil-search"></i> Filter
                    </button>
                </form>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="uil uil-receipt"></i>
                    <h3><?php echo number_format($summary['total_orders']); ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-money-bill"></i>
                    <h3>Rs. <?php echo number_format($summary['total_revenue'], 0); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-chart-line"></i>
                    <h3>Rs. <?php echo number_format($summary['avg_order_value'], 0); ?></h3>
                    <p>Average Order Value</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-check-circle"></i>
                    <h3><?php echo number_format($summary['delivered_orders']); ?></h3>
                    <p>Delivered Orders</p>
                </div>
            </div>
            
            <div class="reports-grid">
                <div class="report-section">
                    <h2>Popular Menu Items</h2>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popular_items as $item): ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td>
                                        <span class="category-badge">
                                            <?php echo ucfirst($item['category']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $item['total_quantity']; ?></td>
                                    <td>Rs. <?php echo number_format($item['total_revenue'], 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-section">
                    <h2>Order Status Breakdown</h2>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($status_data as $status): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $status['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $status['count']; ?></td>
                                    <td>Rs. <?php echo number_format($status['revenue'], 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="report-section full-width">
                <h2>Daily Sales Report</h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Orders</th>
                            <th>Total Revenue</th>
                            <th>Average Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_data as $day): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                                <td><?php echo $day['total_orders']; ?></td>
                                <td>Rs. <?php echo number_format($day['total_revenue'], 0); ?></td>
                                <td>Rs. <?php echo number_format($day['avg_order_value'], 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
