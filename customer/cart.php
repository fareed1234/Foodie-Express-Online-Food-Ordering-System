<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = $_POST['quantity'];
        
        if ($quantity > 0) {
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$quantity, $cart_id, getUserId()]);
        } else {
            $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->execute([$cart_id, getUserId()]);
        }
        
        header("Location: cart.php");
        exit();
    }
    
    if (isset($_POST['remove_item'])) {
        $cart_id = $_POST['cart_id'];
        $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$cart_id, getUserId()]);
        
        header("Location: cart.php");
        exit();
    }
}

// Get cart items
$cart_query = "SELECT c.*, m.name, m.price, m.image 
               FROM cart c 
               JOIN menu_items m ON c.menu_item_id = m.id 
               WHERE c.user_id = ?";
$cart_stmt = $db->prepare($cart_query);
$cart_stmt->execute([getUserId()]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Food Delivery</title>
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
        
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .cart-items {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 1.5rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-details h3 {
            margin-bottom: 0.5rem;
        }
        
        .item-price {
            color: #ff884d;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 2px solid #ff884d;
            background: transparent;
            color: #ff884d;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: #ff884d;
            color: white;
        }
        
        .quantity-display {
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }
        
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            background: #c82333;
        }
        
        .cart-summary {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            height: fit-content;
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
        
        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
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
                <li><a href="cart.php" class="active"><i class="uil uil-shopping-cart"></i> My Cart</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="uil uil-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div>
                    <h1>My Cart</h1>
                    <p>Review your items before checkout</p>
                </div>
                <a href="menu.php" class="btn btn-outline">
                    <i class="uil uil-plus"></i> Add More Items
                </a>
            </div>
            
            <?php if (empty($cart_items)): ?>
                <div class="cart-items">
                    <div class="empty-cart">
                        <i class="uil uil-shopping-cart"></i>
                        <h2>Your cart is empty</h2>
                        <p>Add some delicious items to get started!</p>
                        <a href="menu.php" class="btn btn-primary" style="width: auto; margin-top: 1rem;">
                            Browse Menu
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <div class="cart-items">
                        <h2>Cart Items</h2>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="../<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="item-image">
                                <div class="item-details">
                                    <h3><?php echo $item['name']; ?></h3>
                                    <div class="item-price">Rs. <?php echo number_format($item['price'], 0); ?></div>
                                    <div class="quantity-controls">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                            <button type="submit" name="update_cart" class="quantity-btn">-</button>
                                        </form>
                                        <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                            <button type="submit" name="update_cart" class="quantity-btn">+</button>
                                        </form>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="remove-btn">Remove</button>
                                    </form>
                                </div>
                                <div class="item-total">
                                    <strong>Rs. <?php echo number_format($item['price'] * $item['quantity'], 0); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>Rs. <?php echo number_format($total, 0); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee:</span>
                            <span>Rs. 50</span>
                        </div>
                        <div class="summary-row">
                            <span>Total:</span>
                            <span>Rs. <?php echo number_format($total + 50, 0); ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-primary">
                            <i class="uil uil-credit-card"></i> Proceed to Checkout
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
