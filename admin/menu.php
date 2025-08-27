<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle menu item updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = $_POST['price'];
        $category = $_POST['category'];
        $type = $_POST['type'];
        $calories = $_POST['calories'];
        $persons = $_POST['persons'];
        $image = $_POST['image'];
        
        if (empty($name) || empty($price) || empty($category) || empty($type)) {
            $error = 'Please fill in all required fields';
        } else {
            $insert_query = "INSERT INTO menu_items (name, description, price, category, type, calories, persons, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            
            if ($insert_stmt->execute([$name, $description, $price, $category, $type, $calories, $persons, $image])) {
                $success = 'Menu item added successfully!';
            } else {
                $error = 'Failed to add menu item. Please try again.';
            }
        }
    }
    
    if (isset($_POST['toggle_availability'])) {
        $item_id = $_POST['item_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $update_query = "UPDATE menu_items SET is_available = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$new_status, $item_id])) {
            $success = 'Menu item availability updated!';
        } else {
            $error = 'Failed to update availability.';
        }
    }
    
    if (isset($_POST['delete_item'])) {
        $item_id = $_POST['item_id'];
        
        $delete_query = "DELETE FROM menu_items WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        
        if ($delete_stmt->execute([$item_id])) {
            $success = 'Menu item deleted successfully!';
        } else {
            $error = 'Failed to delete menu item.';
        }
    }
    
    if (isset($_POST['update_item'])) {
        $item_id = $_POST['item_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = $_POST['price'];
        $category = $_POST['category'];
        $type = $_POST['type'];
        $calories = $_POST['calories'];
        $persons = $_POST['persons'];
        $image = $_POST['image'];
        
        if (empty($name) || empty($price) || empty($category) || empty($type)) {
            $error = 'Please fill in all required fields';
        } else {
            $update_query = "UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, type = ?, calories = ?, persons = ?, image = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            
            if ($update_stmt->execute([$name, $description, $price, $category, $type, $calories, $persons, $image, $item_id])) {
                $success = 'Menu item updated successfully!';
            } else {
                $error = 'Failed to update menu item.';
            }
        }
    }
}

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
    <title>Manage Menu - Admin Dashboard</title>
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
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .add-item-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .menu-items-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ff884d;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: #ff884d;
            color: white;
            width: 100%;
            justify-content: center;
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
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .menu-item-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-info h4 {
            margin-bottom: 0.5rem;
        }
        
        .item-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .item-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #ff884d;
            color: white;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }
        
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .type-veg {
            background: #d4edda;
            color: #155724;
        }
        
        .type-non-veg {
            background: #f8d7da;
            color: #721c24;
        }
        
        .availability-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .available {
            background: #d4edda;
            color: #155724;
        }
        
        .unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
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
                <li><a href="menu.php" class="active"><i class="uil uil-restaurant"></i> Manage Menu</a></li>
                <li><a href="users.php"><i class="uil uil-users-alt"></i> Manage Users</a></li>
                <li><a href="staff.php"><i class="uil uil-user-check"></i> Staff Management</a></li>
                <li><a href="reports.php"><i class="uil uil-chart"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Manage Menu</h1>
                <p>Add, edit, and manage menu items</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div class="add-item-section">
                    <h2>Add New Menu Item</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Item Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Brief description of the item"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (Rs.) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="breakfast">Breakfast</option>
                                <option value="lunch">Lunch</option>
                                <option value="dinner">Dinner</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Type *</label>
                            <select id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="veg">Vegetarian</option>
                                <option value="non_veg">Non-Vegetarian</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="calories">Calories</label>
                            <input type="number" id="calories" name="calories" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="persons">Serves (persons)</label>
                            <input type="number" id="persons" name="persons" min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image Path</label>
                            <input type="text" id="image" name="image" placeholder="assets/images/dish/item.png">
                        </div>
                        
                        <button type="submit" name="add_item" class="btn btn-primary">
                            <i class="uil uil-plus"></i> Add Menu Item
                        </button>
                    </form>
                </div>
                
                <div class="menu-items-section">
                    <h2>Menu Items (<?php echo count($menu_items); ?> items)</h2>
                    <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item-card">
                            <div class="item-info">
                                <h4><?php echo $item['name']; ?></h4>
                                <p>
                                    <span class="category-badge"><?php echo ucfirst($item['category']); ?></span>
                                    <span class="type-badge type-<?php echo str_replace('_', '-', $item['type']); ?>">
                                        <?php echo $item['type'] == 'non_veg' ? 'Non Veg' : 'Veg'; ?>
                                    </span>
                                    <span class="availability-badge <?php echo $item['is_available'] ? 'available' : 'unavailable'; ?>">
                                        <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </p>
                                <p><strong>Price:</strong> Rs. <?php echo number_format($item['price'], 0); ?></p>
                                <?php if ($item['description']): ?>
                                    <p><strong>Description:</strong> <?php echo substr($item['description'], 0, 50) . (strlen($item['description']) > 50 ? '...' : ''); ?></p>
                                <?php endif; ?>
                                <?php if ($item['calories']): ?>
                                    <p><strong>Calories:</strong> <?php echo $item['calories']; ?></p>
                                <?php endif; ?>
                                <p><strong>Serves:</strong> <?php echo $item['persons']; ?> person(s)</p>
                                <p><strong>Rating:</strong> <?php echo $item['rating']; ?>/5</p>
                            </div>
                            <div class="item-actions">
                                <button onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="btn btn-info">
                                    <i class="uil uil-edit"></i> Edit
                                </button>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $item['is_available']; ?>">
                                    <button type="submit" name="toggle_availability" class="btn <?php echo $item['is_available'] ? 'btn-warning' : 'btn-success'; ?>">
                                        <i class="uil uil-<?php echo $item['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        <?php echo $item['is_available'] ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_item" class="btn btn-danger">
                                        <i class="uil uil-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Menu Item</h2>
            <form method="POST" id="editForm">
                <input type="hidden" id="edit_item_id" name="item_id">
                
                <div class="form-group">
                    <label for="edit_name">Item Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Price (Rs.) *</label>
                    <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_category">Category *</label>
                    <select id="edit_category" name="category" required>
                        <option value="breakfast">Breakfast</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_type">Type *</label>
                    <select id="edit_type" name="type" required>
                        <option value="veg">Vegetarian</option>
                        <option value="non_veg">Non-Vegetarian</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_calories">Calories</label>
                    <input type="number" id="edit_calories" name="calories" min="0">
                </div>
                
                <div class="form-group">
                    <label for="edit_persons">Serves (persons)</label>
                    <input type="number" id="edit_persons" name="persons" min="1">
                </div>
                
                <div class="form-group">
                    <label for="edit_image">Image Path</label>
                    <input type="text" id="edit_image" name="image">
                </div>
                
                <button type="submit" name="update_item" class="btn btn-primary">
                    <i class="uil uil-check"></i> Update Item
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('editModal');
        const span = document.getElementsByClassName('close')[0];
        
        function editItem(item) {
            document.getElementById('edit_item_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_description').value = item.description || '';
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_category').value = item.category;
            document.getElementById('edit_type').value = item.type;
            document.getElementById('edit_calories').value = item.calories || '';
            document.getElementById('edit_persons').value = item.persons;
            document.getElementById('edit_image').value = item.image || '';
            
            modal.style.display = 'block';
        }
        
        span.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
