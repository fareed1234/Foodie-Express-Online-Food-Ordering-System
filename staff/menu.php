<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('staff');

$database = new Database();
$db = $database->getConnection();

// Get all menu items
$query = "SELECT * FROM menu_items ORDER BY category, name";
$stmt = $db->prepare($query);
$stmt->execute();
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - Staff Dashboard</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #ff884d;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
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
        
        .availability {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .available {
            background: #d4edda;
            color: #155724;
        }
        
        .unavailable {
            background: #f8d7da;
            color: #721c24;
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
                <li><a href="dashboard.php"><i class="uil uil-dashboard"></i> Dashboard</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> All Orders</a></li>
                <li><a href="menu.php" class="active"><i class="uil uil-restaurant"></i> Menu Items</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Menu Items</h1>
                <p>View all available menu items</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($menu_items); ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($menu_items, function($item) { return $item['is_available']; })); ?></div>
                    <div class="stat-label">Available Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($menu_items, function($item) { return $item['type'] == 'veg'; })); ?></div>
                    <div class="stat-label">Vegetarian Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($menu_items, function($item) { return $item['type'] == 'non_veg'; })); ?></div>
                    <div class="stat-label">Non-Veg Items</div>
                </div>
            </div>
            
            <div class="menu-grid">
                <?php foreach ($menu_items as $item): ?>
                    <div class="menu-item">
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
                            
                            <div class="availability <?php echo $item['is_available'] ? 'available' : 'unavailable'; ?>">
                                <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>
