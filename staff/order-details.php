<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('Staff');

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$order_id = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Get staff restaurant ID for security
$staff_query = "SELECT restaurant_id FROM staff WHERE user_id = ? AND is_active = 1";
$staff_stmt = $db->prepare($staff_query);
$staff_stmt->execute([$_SESSION['user_id']]);
$staff_data = $staff_stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff_data) {
    header('Location: dashboard.php');
    exit();
}

$restaurant_id = $staff_data['restaurant_id'];

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ? AND restaurant_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$new_status, $order_id, $restaurant_id]);
    
    header('Location: order-details.php?id=' . $order_id . '&updated=1');
    exit();
}

// Get order details
$query = "SELECT o.*, c.full_name as customer_name, c.phone as customer_phone, c.address as customer_address, 
                 u.email as customer_email, r.restaurant_name 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.customer_id 
          JOIN users u ON c.user_id = u.user_id
          JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
          WHERE o.order_id = ? AND o.restaurant_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $restaurant_id]);

if ($stmt->rowCount() == 0) {
    header('Location: dashboard.php');
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$query = "SELECT oi.*, mi.item_name, mi.price as item_price
          FROM order_items oi 
          JOIN menu_items mi ON oi.item_id = mi.item_id 
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get delivery person if assigned
$delivery_person = null;
if ($order['delivery_person_id']) {
    $query = "SELECT dp.*, u.full_name, u.email, u.phone 
              FROM delivery_persons dp 
              JOIN users u ON dp.user_id = u.user_id 
              WHERE dp.delivery_person_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$order['delivery_person_id']]);
    $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Pakistani Food Delivery</title>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
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
            text-align: center;
        }
        
        .sidebar-header h2 {
            color: #00a86b;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .pakistan-flag {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
        }

        .flag-icon {
            width: 20px;
            height: 13px;
            background: linear-gradient(to right, #00a86b 50%, white 50%);
            border: 1px solid #ddd;
            border-radius: 2px;
            margin-right: 0.5rem;
            position: relative;
        }

        .flag-icon::after {
            content: "☪";
            position: absolute;
            right: 1px;
            top: 50%;
            transform: translateY(-50%);
            color: #00a86b;
            font-size: 8px;
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
            background: #00a86b;
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
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .content-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d1ecf1; color: #0c5460; }
        .status-out-for-delivery { background: #fff3cd; color: #856404; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-item strong {
            color: #00a86b;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .items-table th,
        .items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .items-table tr:hover {
            background: #f8f9fa;
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
            font-weight: 500;
        }
        
        .btn-primary {
            background: #00a86b;
            color: white;
        }
        
        .btn-primary:hover {
            background: #008f5a;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .timeline {
            list-style: none;
            padding: 0;
        }
        
        .timeline li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .timeline li:last-child {
            border-bottom: none;
        }
        
        .timeline-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }
        
        .timeline-badge.completed {
            background: #28a745;
        }
        
        .timeline-badge.pending {
            background: #6c757d;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="pakistan-flag">
                    <div class="flag-icon"></div>
                    <span style="color: #00a86b; font-weight: bold; font-size: 0.9rem;">Pakistani Food</span>
                </div>
                <h2>Staff Panel</h2>
                <p style="font-size: 0.8rem; color: #666;"><?php echo getUserFullName(); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="uil uil-dashboard"></i> Dashboard</a></li>
                <li><a href="menu.php"><i class="uil uil-restaurant"></i> Menu Management</a></li>
                <li><a href="orders.php" class="active"><i class="uil uil-receipt"></i> All Orders</a></li>
                <li><a href="kitchen.php"><i class="uil uil-fire"></i> Kitchen</a></li>
                <li><a href="profile.php"><i class="uil uil-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div>
                    <h1>📋 Order Details</h1>
                    <p>Order #<?php echo $order_id; ?> - <?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                </div>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="uil uil-arrow-left"></i> Back to Orders
                </a>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="success-message">
                    <i class="uil uil-check-circle"></i> Order status updated successfully!
                </div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div>
                    <div class="content-section">
                        <div class="order-header">
                            <h2>Order #<?php echo $order_id; ?></h2>
                            <span class="status-badge status-<?php echo str_replace('_', '-', $order['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="info-grid">
                            <div>
                                <div class="info-item">
                                    <strong>📅 Order Date:</strong><br>
                                    <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
                                </div>
                                <div class="info-item">
                                    <strong>👤 Customer:</strong><br>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </div>
                                <div class="info-item">
                                    <strong>📧 Email:</strong><br>
                                    <?php echo htmlspecialchars($order['customer_email']); ?>
                                </div>
                            </div>
                            <div>
                                <div class="info-item">
                                    <strong>📞 Phone:</strong><br>
                                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                                </div>
                                <div class="info-item">
                                    <strong>📍 Address:</strong><br>
                                    <?php echo htmlspecialchars($order['customer_address']); ?>
                                </div>
                                <div class="info-item">
                                    <strong>💰 Total Amount:</strong><br>
                                    <span style="font-size: 1.2rem; color: #00a86b; font-weight: bold;">
                                        Rs. <?php echo number_format($order['total_amount'], 0); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($delivery_person): ?>
                            <div class="info-item">
                                <strong>🚚 Delivery Person:</strong><br>
                                <?php echo htmlspecialchars($delivery_person['full_name']); ?> 
                                (<?php echo htmlspecialchars($delivery_person['phone']); ?>)
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="content-section">
                        <h3>🍽️ Order Items</h3>
                        <div style="overflow-x: auto;">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td>Rs. <?php echo number_format($item['item_price'], 0); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>Rs. <?php echo number_format($item['item_price'] * $item['quantity'], 0); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="font-weight: bold; background: #f8f9fa;">
                                        <td colspan="3">Total:</td>
                                        <td>Rs. <?php echo number_format($order['total_amount'], 0); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="content-section">
                        <h3>🔄 Update Status</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label for="status">Order Status</label>
                                <select id="status" name="status" required>
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="out_for_delivery" <?php echo $order['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                                <i class="uil uil-check"></i> Update Status
                            </button>
                        </form>
                    </div>
                    
                    <div class="content-section">
                        <h3>📈 Order Timeline</h3>
                        <ul class="timeline">
                            <li>
                                <span>Order Placed</span>
                                <div class="timeline-badge completed">
                                    <i class="uil uil-check"></i>
                                </div>
                            </li>
                            <li>
                                <span>Confirmed</span>
                                <div class="timeline-badge <?php echo in_array($order['status'], ['confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'completed' : 'pending'; ?>">
                                    <?php echo in_array($order['status'], ['confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered']) ? '<i class="uil uil-check"></i>' : ''; ?>
                                </div>
                            </li>
                            <li>
                                <span>Preparing</span>
                                <div class="timeline-badge <?php echo in_array($order['status'], ['preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'completed' : 'pending'; ?>">
                                    <?php echo in_array($order['status'], ['preparing', 'ready', 'out_for_delivery', 'delivered']) ? '<i class="uil uil-check"></i>' : ''; ?>
                                </div>
                            </li>
                            <li>
                                <span>Ready</span>
                                <div class="timeline-badge <?php echo in_array($order['status'], ['ready', 'out_for_delivery', 'delivered']) ? 'completed' : 'pending'; ?>">
                                    <?php echo in_array($order['status'], ['ready', 'out_for_delivery', 'delivered']) ? '<i class="uil uil-check"></i>' : ''; ?>
                                </div>
                            </li>
                            <li>
                                <span>Out for Delivery</span>
                                <div class="timeline-badge <?php echo in_array($order['status'], ['out_for_delivery', 'delivered']) ? 'completed' : 'pending'; ?>">
                                    <?php echo in_array($order['status'], ['out_for_delivery', 'delivered']) ? '<i class="uil uil-check"></i>' : ''; ?>
                                </div>
                            </li>
                            <li>
                                <span>Delivered</span>
                                <div class="timeline-badge <?php echo $order['status'] == 'delivered' ? 'completed' : 'pending'; ?>">
                                    <?php echo $order['status'] == 'delivered' ? '<i class="uil uil-check"></i>' : ''; ?>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <?php if ($order['special_instructions']): ?>
                        <div class="content-section">
                            <h3>📝 Special Instructions</h3>
                            <p style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid #00a86b;">
                                <?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
