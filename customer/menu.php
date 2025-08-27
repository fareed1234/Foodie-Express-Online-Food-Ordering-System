<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get all menu items
$query = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name";
$stmt = $db->prepare($query);
$stmt->execute();
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $menu_item_id = $_POST['menu_item_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    // Check if item already in cart
    $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([getUserId(), $menu_item_id]);
    
    if ($existing = $check_stmt->fetch()) {
        // Update quantity
        $update_query = "UPDATE cart SET quantity = quantity + ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$quantity, $existing['id']]);
    } else {
        // Add new item
        $insert_query = "INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([getUserId(), $menu_item_id, $quantity]);
    }
    
    header("Location: menu.php?added=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Food Delivery</title>
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
        
        .menu-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #ff884d;
            background: transparent;
            color: #ff884d;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.active,
        .filter-btn:hover {
            background: #ff884d;
            color: white;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .menu-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
        }
        
        .menu-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .menu-item-content {
            padding: 1.5rem;
        }
        
        .menu-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .menu-item h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #ff884d;
            font-size: 0.9rem;
        }
        
        .menu-item-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff884d;
            margin-bottom: 1rem;
        }
        
        .add-to-cart-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            text-align: center;
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
            flex: 1;
            justify-content: center;
        }
        
        .btn-primary:hover {
            background: #e6743d;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #ff884d;
            color: white;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .type-veg {
            background: #d4edda;
            color: #155724;
        }
        
        .type-non-veg {
            background: #f8d7da;
            color: #721c24;
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
                <li><a href="menu.php" class="active"><i class="uil uil-restaurant"></i> Browse Menu</a></li>
                <li><a href="cart.php"><i class="uil uil-shopping-cart"></i> My Cart</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="uil uil-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div>
                    <h1>Our Menu</h1>
                    <p>Choose from our delicious selection</p>
                </div>
                <a href="cart.php" class="btn btn-primary">
                    <i class="uil uil-shopping-cart"></i> View Cart
                </a>
            </div>
            
            <?php if (isset($_GET['added'])): ?>
                <div class="success-message">
                    Item added to cart successfully!
                </div>
            <?php endif; ?>
            
            <div class="menu-filters">
                <button class="filter-btn active" onclick="filterMenu('all')">All</button>
                <button class="filter-btn" onclick="filterMenu('breakfast')">Breakfast</button>
                <button class="filter-btn" onclick="filterMenu('lunch')">Lunch</button>
                <button class="filter-btn" onclick="filterMenu('dinner')">Dinner</button>
            </div>
            
            <div class="menu-grid">
                <?php foreach ($menu_items as $item): ?>
                    <div class="menu-item" data-category="<?php echo $item['category']; ?>">
                        <img src="../<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        <div class="menu-item-content">
                            <div class="menu-item-header">
                                <div>
                                    <span class="category-badge"><?php echo ucfirst($item['category']); ?></span>
                                    <span class="type-badge type-<?php echo str_replace('_', '-', $item['type']); ?>">
                                        <?php echo $item['type'] == 'non_veg' ? 'Non Veg' : 'Veg'; ?>
                                    </span>
                                    <h3><?php echo $item['name']; ?></h3>
                                    <?php if ($item['description']): ?>
                                        <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">
                                            <?php echo $item['description']; ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="rating">
                                        <i class="uil uil-star"></i>
                                        <span><?php echo $item['rating']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="menu-item-info">
                                <span><?php echo $item['calories']; ?> calories</span>
                                <span>Serves <?php echo $item['persons']; ?></span>
                            </div>
                            
                            <div class="price">Rs. <?php echo number_format($item['price'], 0); ?></div>
                            
                            <form method="POST" class="add-to-cart-form">
                                <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="10" class="quantity-input">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">
                                    <i class="uil uil-plus"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <script>
        function filterMenu(category) {
            const items = document.querySelectorAll('.menu-item');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter items
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
