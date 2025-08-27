<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Don't allow deleting the current admin
        if ($user_id == getUserId()) {
            $error = 'You cannot delete your own account!';
        } else {
            $delete_query = "DELETE FROM users WHERE id = ?";
            $delete_stmt = $db->prepare($delete_query);
            
            if ($delete_stmt->execute([$user_id])) {
                $success = 'User deleted successfully!';
            } else {
                $error = 'Failed to delete user.';
            }
        }
    }
    
    if (isset($_POST['update_role'])) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['role'];
        
        $update_query = "UPDATE users SET role = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$new_role, $user_id])) {
            $success = 'User role updated successfully!';
        } else {
            $error = 'Failed to update user role.';
        }
    }
}

// Get all users
$users_query = "SELECT * FROM users ORDER BY role, name";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stats = [];
$roles = ['customer', 'staff', 'admin', 'delivery'];
foreach ($roles as $role) {
    $count_query = "SELECT COUNT(*) as count FROM users WHERE role = ?";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([$role]);
    $stats[$role] = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
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
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            color: #ff884d;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
            text-transform: capitalize;
        }
        
        .users-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-staff {
            background: #17a2b8;
            color: white;
        }
        
        .role-delivery {
            background: #ffc107;
            color: #212529;
        }
        
        .role-customer {
            background: #28a745;
            color: white;
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
            margin-right: 0.5rem;
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
            margin: 15% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
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
        
        .form-group {
            margin-bottom: 1rem;
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
                <li><a href="users.php" class="active"><i class="uil uil-users-alt"></i> Manage Users</a></li>
                <li><a href="staff.php"><i class="uil uil-user-check"></i> Staff Management</a></li>
                <li><a href="reports.php"><i class="uil uil-chart"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Manage Users</h1>
                <p>View and manage all system users</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <?php foreach ($stats as $role => $count): ?>
                    <div class="stat-card">
                        <i class="uil uil-<?php echo $role == 'customer' ? 'users-alt' : ($role == 'admin' ? 'shield' : ($role == 'staff' ? 'user-check' : 'truck')); ?>"></i>
                        <h3><?php echo $count; ?></h3>
                        <p><?php echo $role; ?>s</p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="users-section">
                <h2>All Users</h2>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['phone'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button onclick="editUserRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')" class="btn btn-primary">
                                        <i class="uil uil-edit"></i> Edit Role
                                    </button>
                                    
                                    <?php if ($user['id'] != getUserId()): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger">
                                                <i class="uil uil-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Edit Role Modal -->
    <div id="editRoleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit User Role</h2>
            <form method="POST" id="editRoleForm">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-group">
                    <label for="edit_role">Select Role</label>
                    <select id="edit_role" name="role" required>
                        <option value="customer">Customer</option>
                        <option value="staff">Staff</option>
                        <option value="delivery">Delivery</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <button type="submit" name="update_role" class="btn btn-primary">
                    <i class="uil uil-check"></i> Update Role
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('editRoleModal');
        const span = document.getElementsByClassName('close')[0];
        
        function editUserRole(userId, currentRole) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_role').value = currentRole;
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
