<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get cart items
$cart_query = "SELECT c.*, m.name, m.price 
               FROM cart c 
               JOIN menu_items m ON c.menu_item_id = m.id 
               WHERE c.user_id = ?";
$cart_stmt = $db->prepare($cart_query);
$cart_stmt->execute([getUserId()]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$delivery_fee = 50;
$total = $subtotal + $delivery_fee;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address']);
    $phone = trim($_POST['phone']);
    $notes = trim($_POST['notes']);
    
    if (empty($delivery_address) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $db->beginTransaction();
            
            // Create order
            $order_query = "INSERT INTO orders (customer_id, total_amount, delivery_address, phone, notes) VALUES (?, ?, ?, ?, ?)";
            $order_stmt = $db->prepare($order_query);
            $order_stmt->execute([getUserId(), $total, $delivery_address, $phone, $notes]);
            $order_id = $db->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $item_query = "INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = $db->prepare($item_query);
                $item_stmt->execute([$order_id, $item['menu_item_id'], $item['quantity'], $item['price']]);
            }
            
            // Clear cart
            $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_cart_stmt = $db->prepare($clear_cart_query);
            $clear_cart_stmt->execute([getUserId()]);
            
            $db->commit();
            
            header("Location: orders.php?success=1");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Order placement failed. Please try again.';
        }
    }
}

// Get user info for pre-filling
$user_query = "SELECT phone, address FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([getUserId()]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Food Delivery</title>
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
        
        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .checkout-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff884d;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .order-summary {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            height: fit-content;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info h4 {
            margin-bottom: 0.25rem;
        }
        
        .item-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
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
            width: 100%;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .btn-primary {
            background: #ff884d;
            color: white;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            background: #e6743d;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
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
                <li><a href="cart.php"><i class="uil uil-shopping-cart"></i> My Cart</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="uil uil-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Checkout</h1>
                <p>Complete your order details</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="checkout-container">
                <div class="checkout-form">
                    <h2>Delivery Information</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required value="<?php echo $user_info['phone'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_address">Delivery Address *</label>
                            <textarea id="delivery_address" name="delivery_address" required placeholder="Enter your complete delivery address"><?php echo $user_info['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Special Instructions (Optional)</label>
                            <textarea id="notes" name="notes" placeholder="Any special instructions for your order..."></textarea>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn btn-primary">
                            <i class="uil uil-check"></i> Place Order
                        </button>
                    </form>
                </div>
                
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="item-info">
                                <h4><?php echo $item['name']; ?></h4>
                                <p>Qty: <?php echo $item['quantity']; ?> × Rs. <?php echo number_format($item['price'], 0); ?></p>
                            </div>
                            <span>Rs. <?php echo number_format($item['price'] * $item['quantity'], 0); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>Rs. <?php echo number_format($subtotal, 0); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span>Rs. <?php echo number_format($delivery_fee, 0); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Total:</span>
                        <span>Rs. <?php echo number_format($total, 0); ?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
