<?php
require_once 'includes/session.php';
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'customer';
    
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$email]);
        
        if ($check_stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (name, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$name, $email, $phone, $address, $hashed_password, $role])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Food Delivery</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: #666;
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

        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
            background: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: #ff884d;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #ff884d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
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
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-link a {
            color: #ff884d;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-home a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Join us for delicious food delivery</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required placeholder="Enter your full address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="role">Select Role</label>
                <select id="role" name="role" required>
                    <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                    <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="delivery" <?php echo (isset($_POST['role']) && $_POST['role'] == 'delivery') ? 'selected' : ''; ?>>Delivery Personnel</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
        
        <div class="back-home">
            <a href="index.html">← Back to Home</a>
        </div>
    </div>
</body>
</html>
