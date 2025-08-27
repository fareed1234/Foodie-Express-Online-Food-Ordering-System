<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all staff and delivery personnel
$staff_query = "SELECT * FROM users WHERE role IN ('staff', 'delivery') ORDER BY role, name";
$staff_stmt = $db->prepare($staff_query);
$staff_stmt->execute();
$staff_members = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get staff statistics
$staff_stats_query = "SELECT 
    COUNT(CASE WHEN role = 'staff' THEN 1 END) as staff_count,
    COUNT(CASE WHEN role = 'delivery' THEN 1 END) as delivery_count
    FROM users WHERE role IN ('staff', 'delivery')";
$staff_stats_stmt = $db->prepare($staff_stats_query);
$staff_stats_stmt->execute();
$staff_stats = $staff_stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get today's performance
$today_performance_query = "SELECT 
    u.name, u.role,
    COUNT(CASE WHEN o.status = 'ready' AND u.role = 'staff' THEN 1 END) as orders_completed,
    COUNT(CASE WHEN o.status = 'delivered' AND u.role = 'delivery' THEN 1 END) as deliveries_completed
    FROM users u
    LEFT JOIN orders o ON (u.id = o.staff_id OR u.id = o.delivery_id) AND DATE(o.order_date) = CURDATE()
    WHERE u.role IN ('staff', 'delivery')
    GROUP BY u.id, u.name, u.role
    ORDER BY u.role, u.name";
$today_performance_stmt = $db->prepare($today_performance_query);
$today_performance_stmt->execute();
$today_performance = $today_performance_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Admin Dashboard</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 3rem;
            color: #ff884d;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .staff-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .staff-member {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .staff-member:last-child {
            border-bottom: none;
        }
        
        .staff-info h4 {
            margin-bottom: 0.5rem;
        }
        
        .staff-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .role-staff {
            background: #17a2b8;
            color: white;
        }
        
        .role-delivery {
            background: #ffc107;
            color: #212529;
        }
        
        .performance-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .performance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .performance-item:last-child {
            border-bottom: none;
        }
        
        .performance-stats {
            display: flex;
            gap: 1rem;
        }
        
        .performance-stat {
            text-align: center;
        }
        
        .performance-stat h4 {
            color: #ff884d;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .performance-stat p {
            color: #666;
            font-size: 0.8rem;
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
        }
        
        .btn-primary {
            background: #ff884d;
            color: white;
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
                <li><a href="users.php"><i class="uil uil-users-alt"></i> Manage Users</a></li>
                <li><a href="staff.php" class="active"><i class="uil uil-user-check"></i> Staff Management</a></li>
                <li><a href="reports.php"><i class="uil uil-chart"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="uil uil-signout"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Staff Management</h1>
                <p>Manage kitchen staff and delivery personnel</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="uil uil-user-check"></i>
                    <h3><?php echo $staff_stats['staff_count']; ?></h3>
                    <p>Kitchen Staff</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-truck"></i>
                    <h3><?php echo $staff_stats['delivery_count']; ?></h3>
                    <p>Delivery Personnel</p>
                </div>
                <div class="stat-card">
                    <i class="uil uil-users-alt"></i>
                    <h3><?php echo $staff_stats['staff_count'] + $staff_stats['delivery_count']; ?></h3>
                    <p>Total Staff</p>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="staff-section">
                    <h2>Staff Members</h2>
                    <?php foreach ($staff_members as $member): ?>
                        <div class="staff-member">
                            <div class="staff-info">
                                <h4><?php echo $member['name']; ?></h4>
                                <p><?php echo $member['email']; ?></p>
                                <p><?php echo $member['phone'] ?? 'No phone'; ?></p>
                                <p>Joined: <?php echo date('M d, Y', strtotime($member['created_at'])); ?></p>
                            </div>
                            <div>
                                <span class="role-badge role-<?php echo $member['role']; ?>">
                                    <?php echo ucfirst($member['role']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="performance-section">
                    <h2>Today's Performance</h2>
                    <?php foreach ($today_performance as $performance): ?>
                        <div class="performance-item">
                            <div class="staff-info">
                                <h4><?php echo $performance['name']; ?></h4>
                                <span class="role-badge role-<?php echo $performance['role']; ?>">
                                    <?php echo ucfirst($performance['role']); ?>
                                </span>
                            </div>
                            <div class="performance-stats">
                                <?php if ($performance['role'] == 'staff'): ?>
                                    <div class="performance-stat">
                                        <h4><?php echo $performance['orders_completed']; ?></h4>
                                        <p>Orders Completed</p>
                                    </div>
                                <?php else: ?>
                                    <div class="performance-stat">
                                        <h4><?php echo $performance['deliveries_completed']; ?></h4>
                                        <p>Deliveries Made</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
