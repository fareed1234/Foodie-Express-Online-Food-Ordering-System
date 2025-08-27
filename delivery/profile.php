<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('delivery');

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Food Delivery</title>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="../assets/css/delivery-modern.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="uil uil-truck"></i>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="uil uil-dashboard"></i>
                </a>
                <a href="deliveries.php" class="nav-item">
                    <i class="uil uil-package"></i>
                </a>
                <a href="history.php" class="nav-item">
                    <i class="uil uil-history"></i>
                </a>
                <a href="profile.php" class="nav-item active">
                    <i class="uil uil-user"></i>
                </a>
                <a href="../logout.php" class="nav-item logout">
                    <i class="uil uil-signout"></i>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <div class="welcome-section">
                        <h1>My Profile</h1>
                        <p>Manage your account information</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-primary">
                            <i class="uil uil-user"></i>
                            Profile
                        </button>
                        <a href="dashboard.php" class="btn-secondary">Dashboard</a>
                    </div>
                </div>
            </header>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="uil uil-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <i class="uil uil-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <section class="profile-section">
                <div class="profile-grid">
                    <!-- Profile Information -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h3><i class="uil uil-user"></i> Profile Information</h3>
                        </div>
                        <form method="POST" class="profile-form">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($user_info['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" readonly>
                                <small>Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($user_info['phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" required><?php echo htmlspecialchars($user_info['address']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn-action btn-primary-action">
                                <i class="uil uil-check"></i> Update Profile
                            </button>
                        </form>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h3><i class="uil uil-lock"></i> Change Password</h3>
                        </div>
                        <form method="POST" class="profile-form">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <small>Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn-action btn-secondary-action">
                                <i class="uil uil-lock"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
