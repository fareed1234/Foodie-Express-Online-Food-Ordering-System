<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('customer');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($name) || empty($phone) || empty($address)) {
        $error = 'Please fill in all fields';
    } else {
        $update_query = "UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$name, $phone, $address, getUserId()])) {
            $_SESSION['name'] = $name;
            $_SESSION['phone'] = $phone;
            $_SESSION['address'] = $address;
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } else {
        // Verify current password
        $verify_query = "SELECT password FROM users WHERE id = ?";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->execute([getUserId()]);
        $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            
            if ($update_stmt->execute([$hashed_password, getUserId()])) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([getUserId()]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get cart count
$cart_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
$cart_stmt = $db->prepare($cart_query);
$cart_stmt->execute([getUserId()]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Food Delivery</title>
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
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .profile-section {
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
        }
        
        .btn-primary {
            background: #ff884d;
            color: white;
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
        
        .success {
            background: #efe;
            color: #3c3;
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
                <li><a href="cart.php"><i class="uil uil-shopping-cart"></i> My Cart (<?php echo $cart_count; ?>)</a></li>
                <li><a href="orders.php"><i class="uil uil-receipt"></i> My Orders</a></li>
                <li><a href="profile.php" class="active"><i class="uil uil-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>My Profile</h1>
                <p>Manage your account information</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="profile-container">
                <div class="profile-section">
                    <h2>Profile Information</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($user_info['name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($user_info['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" required><?php echo htmlspecialchars($user_info['address']); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="uil uil-check"></i> Update Profile
                        </button>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h2>Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="uil uil-lock"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
