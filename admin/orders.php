<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$new_status, $order_id]);
    
    header("Location: orders.php?updated=1");
    exit();
}

// Get all orders with customer details
$orders_query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone, u.email as customer_email,
                        s.name as staff_name, d.name as delivery_name
                FROM orders o 
                JOIN users u ON o.customer_id = u.id 
                LEFT JOIN users s ON o.staff_id = s.id
                LEFT JOIN users d ON o.delivery_id = d.id
                ORDER BY o.order_date DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Dashboard</title>
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
        
        .orders-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
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
        
        .btn {
            padding: 0.25rem 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.3s;
            font-size: 0.8rem;
            margin-right: 0.25rem;
        }
        
        .btn-primary {
            background: #ff884d;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e6743d;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .table-responsive {
            overflow-x: auto;
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
                <li><a href="orders.php" class="active"><i class="uil uil-receipt"></i> Manage Orders</a></li>
                <li><a href="menu.php"><i class="uil uil-restaurant"></i> Manage Menu</a></li>
                <li><a href="users.php"><i class="uil uil-users-alt"></i> Manage Users</a></li>
                <li><a href="staff.php"><i class="uil uil-user-check"></i> Staff Management</a></li>
                <li><a href="reports.php"><i class="uil uil-chart"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Manage Orders</h1>
                <p>View and manage all customer orders</p>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="success-message">
                    Order status updated successfully!
                </div>
            <?php endif; ?>
            
            <div class="orders-section">
                <h2>All Orders</h2>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Staff</th>
                                <th>Delivery</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['customer_name']; ?></td>
                                    <td><?php echo $order['customer_phone']; ?></td>
                                    <td>Rs. <?php echo number_format($order['total_amount'], 0); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $order['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['staff_name'] ?? 'Not Assigned'; ?></td>
                                    <td><?php echo $order['delivery_name'] ?? 'Not Assigned'; ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php if ($order['status'] != 'delivered' && $order['status'] != 'cancelled'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="btn btn-danger">
                                                    <i class="uil uil-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
