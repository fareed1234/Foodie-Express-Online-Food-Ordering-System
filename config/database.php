<?php
class Database {
    private $host = "localhost";
    private $db_name = "food_delivery";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }

    public function createDatabase() {
        try {
            // Connect without database name first
            $conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $conn->exec("set names utf8");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            $conn->exec("USE " . $this->db_name);
            
            return $conn;
        } catch(PDOException $exception) {
            echo "Database creation error: " . $exception->getMessage();
            return false;
        }
    }

    public function setupTables() {
        $conn = $this->createDatabase();
        if (!$conn) return false;

        try {
            // Users table for authentication
            $conn->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                address TEXT,
                role ENUM('customer', 'staff', 'admin', 'delivery') DEFAULT 'customer',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");

            // Menu items table
            $conn->exec("CREATE TABLE IF NOT EXISTS menu_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                image VARCHAR(255),
                category ENUM('breakfast', 'lunch', 'dinner') NOT NULL,
                type ENUM('veg', 'non_veg') NOT NULL,
                calories INT,
                persons INT DEFAULT 1,
                rating DECIMAL(2,1) DEFAULT 0,
                is_available BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Cart table
            $conn->exec("CREATE TABLE IF NOT EXISTS cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                menu_item_id INT NOT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
            )");

            // Orders table
            $conn->exec("CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                staff_id INT NULL,
                delivery_id INT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
                delivery_address TEXT NOT NULL,
                phone VARCHAR(20) NOT NULL,
                notes TEXT,
                order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                delivery_date TIMESTAMP NULL,
                FOREIGN KEY (customer_id) REFERENCES users(id),
                FOREIGN KEY (staff_id) REFERENCES users(id),
                FOREIGN KEY (delivery_id) REFERENCES users(id)
            )");

            // Order items table
            $conn->exec("CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                menu_item_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
            )");

            // Admins table
            $conn->exec("CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                employee_id VARCHAR(50) UNIQUE,
                department VARCHAR(100) DEFAULT 'Management',
                access_level ENUM('super_admin', 'admin', 'manager') DEFAULT 'admin',
                last_login TIMESTAMP NULL,
                salary DECIMAL(10,2),
                hire_date DATE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");

            // Staff table
            $conn->exec("CREATE TABLE IF NOT EXISTS staff (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                employee_id VARCHAR(50) UNIQUE,
                department ENUM('kitchen', 'preparation', 'packaging', 'quality_control') DEFAULT 'kitchen',
                shift_type ENUM('morning', 'afternoon', 'evening', 'night') DEFAULT 'morning',
                hourly_rate DECIMAL(8,2),
                hire_date DATE,
                supervisor_id INT NULL,
                performance_rating DECIMAL(3,2) DEFAULT 0.00,
                total_orders_completed INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (supervisor_id) REFERENCES staff(id) ON DELETE SET NULL
            )");

            // Delivery Personnel table
            $conn->exec("CREATE TABLE IF NOT EXISTS delivery_personnel (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                employee_id VARCHAR(50) UNIQUE,
                vehicle_type ENUM('bike', 'scooter', 'car', 'bicycle') DEFAULT 'bike',
                vehicle_number VARCHAR(20),
                license_number VARCHAR(50),
                delivery_zone VARCHAR(100),
                max_delivery_radius INT DEFAULT 10,
                commission_rate DECIMAL(5,2) DEFAULT 50.00,
                total_deliveries INT DEFAULT 0,
                total_earnings DECIMAL(10,2) DEFAULT 0.00,
                average_rating DECIMAL(3,2) DEFAULT 0.00,
                current_status ENUM('available', 'busy', 'offline', 'on_break') DEFAULT 'available',
                current_location_lat DECIMAL(10,8) NULL,
                current_location_lng DECIMAL(11,8) NULL,
                last_location_update TIMESTAMP NULL,
                hire_date DATE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");

            // Delivery Status History table
            $conn->exec("CREATE TABLE IF NOT EXISTS delivery_status_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                delivery_personnel_id INT NOT NULL,
                status ENUM('assigned', 'picked_up', 'in_transit', 'arrived', 'delivered', 'failed', 'returned') NOT NULL,
                location_lat DECIMAL(10,8) NULL,
                location_lng DECIMAL(11,8) NULL,
                notes TEXT,
                estimated_delivery_time TIMESTAMP NULL,
                actual_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (delivery_personnel_id) REFERENCES delivery_personnel(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )");

            // Delivery Routes table
            $conn->exec("CREATE TABLE IF NOT EXISTS delivery_routes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                delivery_personnel_id INT NOT NULL,
                order_id INT NOT NULL,
                pickup_address TEXT NOT NULL,
                delivery_address TEXT NOT NULL,
                pickup_lat DECIMAL(10,8),
                pickup_lng DECIMAL(11,8),
                delivery_lat DECIMAL(10,8),
                delivery_lng DECIMAL(11,8),
                estimated_distance_km DECIMAL(8,2),
                actual_distance_km DECIMAL(8,2) NULL,
                estimated_time_minutes INT,
                actual_time_minutes INT NULL,
                pickup_time TIMESTAMP NULL,
                delivery_time TIMESTAMP NULL,
                delivery_fee DECIMAL(8,2) DEFAULT 0.00,
                status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (delivery_personnel_id) REFERENCES delivery_personnel(id) ON DELETE CASCADE,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            )");

            // Delivery Performance table
            $conn->exec("CREATE TABLE IF NOT EXISTS delivery_performance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                delivery_personnel_id INT NOT NULL,
                date DATE NOT NULL,
                total_deliveries INT DEFAULT 0,
                successful_deliveries INT DEFAULT 0,
                failed_deliveries INT DEFAULT 0,
                total_distance_km DECIMAL(10,2) DEFAULT 0.00,
                total_time_minutes INT DEFAULT 0,
                average_delivery_time DECIMAL(8,2) DEFAULT 0.00,
                earnings DECIMAL(10,2) DEFAULT 0.00,
                customer_rating DECIMAL(3,2) DEFAULT 0.00,
                on_time_deliveries INT DEFAULT 0,
                late_deliveries INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (delivery_personnel_id) REFERENCES delivery_personnel(id) ON DELETE CASCADE,
                UNIQUE KEY unique_personnel_date (delivery_personnel_id, date)
            )");

            // Staff Shifts table
            $conn->exec("CREATE TABLE IF NOT EXISTS staff_shifts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                shift_date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                break_duration INT DEFAULT 30,
                status ENUM('scheduled', 'in_progress', 'completed', 'absent', 'cancelled') DEFAULT 'scheduled',
                orders_handled INT DEFAULT 0,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
                UNIQUE KEY unique_staff_shift (staff_id, shift_date, start_time)
            )");

            // Customer Reviews table
            $conn->exec("CREATE TABLE IF NOT EXISTS customer_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                customer_id INT NOT NULL,
                delivery_personnel_id INT NULL,
                food_rating DECIMAL(2,1) DEFAULT 0.0,
                delivery_rating DECIMAL(2,1) DEFAULT 0.0,
                overall_rating DECIMAL(2,1) DEFAULT 0.0,
                food_review TEXT,
                delivery_review TEXT,
                overall_review TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (delivery_personnel_id) REFERENCES delivery_personnel(id) ON DELETE SET NULL
            )");

            $this->createIndexes($conn);
            $this->createViews($conn);
            
            return true;
        } catch(PDOException $exception) {
            echo "Table creation error: " . $exception->getMessage();
            return false;
        }
    }

    private function createIndexes($conn) {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)",
            "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
            "CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status)",
            "CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_orders_delivery ON orders(delivery_id)",
            "CREATE INDEX IF NOT EXISTS idx_orders_date ON orders(order_date)",
            "CREATE INDEX IF NOT EXISTS idx_menu_items_category ON menu_items(category)",
            "CREATE INDEX IF NOT EXISTS idx_menu_items_available ON menu_items(is_available)",
            "CREATE INDEX IF NOT EXISTS idx_admins_user_id ON admins(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_staff_user_id ON staff(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_staff_department ON staff(department)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_personnel_user_id ON delivery_personnel(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_personnel_status ON delivery_personnel(current_status)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_personnel_zone ON delivery_personnel(delivery_zone)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_status_order ON delivery_status_history(order_id)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_status_personnel ON delivery_status_history(delivery_personnel_id)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_routes_personnel ON delivery_routes(delivery_personnel_id)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_routes_status ON delivery_routes(status)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_performance_date ON delivery_performance(date)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_performance_personnel ON delivery_performance(delivery_personnel_id)",
            "CREATE INDEX IF NOT EXISTS idx_staff_shifts_date ON staff_shifts(shift_date)",
            "CREATE INDEX IF NOT EXISTS idx_customer_reviews_order ON customer_reviews(order_id)"
        ];

        foreach ($indexes as $index) {
            try {
                $conn->exec($index);
            } catch(PDOException $e) {
                // Index might already exist, continue
            }
        }
    }

    private function createViews($conn) {
        try {
            // Admin details view
            $conn->exec("CREATE OR REPLACE VIEW admin_details AS
                SELECT 
                    u.id, u.name, u.email, u.phone, u.address,
                    a.employee_id, a.department, a.access_level, 
                    a.last_login, a.salary, a.hire_date, a.is_active
                FROM users u
                JOIN admins a ON u.id = a.user_id
                WHERE u.role = 'admin'");

            // Staff details view
            $conn->exec("CREATE OR REPLACE VIEW staff_details AS
                SELECT 
                    u.id, u.name, u.email, u.phone, u.address,
                    s.employee_id, s.department, s.shift_type, 
                    s.hourly_rate, s.hire_date, s.performance_rating,
                    s.total_orders_completed, s.is_active
                FROM users u
                JOIN staff s ON u.id = s.user_id
                WHERE u.role = 'staff'");

            // Delivery personnel details view
            $conn->exec("CREATE OR REPLACE VIEW delivery_personnel_details AS
                SELECT 
                    u.id, u.name, u.email, u.phone, u.address,
                    d.employee_id, d.vehicle_type, d.vehicle_number,
                    d.delivery_zone, d.total_deliveries, d.total_earnings,
                    d.average_rating, d.current_status, d.is_active,
                    d.current_location_lat, d.current_location_lng,
                    d.last_location_update
                FROM users u
                JOIN delivery_personnel d ON u.id = d.user_id
                WHERE u.role = 'delivery'");

            // Current delivery status view
            $conn->exec("CREATE OR REPLACE VIEW current_delivery_status AS
                SELECT 
                    o.id as order_id,
                    o.customer_id,
                    u_customer.name as customer_name,
                    u_customer.phone as customer_phone,
                    o.delivery_address,
                    o.total_amount,
                    o.status as order_status,
                    dp.id as delivery_personnel_id,
                    u_delivery.name as delivery_person_name,
                    u_delivery.phone as delivery_person_phone,
                    dp.vehicle_type,
                    dp.vehicle_number,
                    dp.current_status as delivery_status,
                    dr.estimated_time_minutes,
                    dr.actual_time_minutes,
                    dr.pickup_time,
                    dr.delivery_time,
                    dsh.status as latest_delivery_status,
                    dsh.actual_time as status_update_time,
                    dsh.notes as status_notes
                FROM orders o
                LEFT JOIN users u_customer ON o.customer_id = u_customer.id
                LEFT JOIN delivery_personnel dp ON o.delivery_id = dp.user_id
                LEFT JOIN users u_delivery ON dp.user_id = u_delivery.id
                LEFT JOIN delivery_routes dr ON o.id = dr.order_id
                LEFT JOIN delivery_status_history dsh ON o.id = dsh.order_id 
                    AND dsh.id = (SELECT MAX(id) FROM delivery_status_history WHERE order_id = o.id)
                WHERE o.status IN ('out_for_delivery', 'delivered')");

            // Delivery performance summary view
            $conn->exec("CREATE OR REPLACE VIEW delivery_performance_summary AS
                SELECT 
                    dp.id,
                    u.name,
                    dp.employee_id,
                    dp.delivery_zone,
                    dp.total_deliveries,
                    dp.total_earnings,
                    dp.average_rating,
                    dp.current_status,
                    COALESCE(today_perf.total_deliveries, 0) as today_deliveries,
                    COALESCE(today_perf.successful_deliveries, 0) as today_successful,
                    COALESCE(today_perf.earnings, 0) as today_earnings,
                    COALESCE(month_stats.monthly_deliveries, 0) as monthly_deliveries,
                    COALESCE(month_stats.monthly_earnings, 0) as monthly_earnings
                FROM delivery_personnel dp
                JOIN users u ON dp.user_id = u.id
                LEFT JOIN delivery_performance today_perf ON dp.id = today_perf.delivery_personnel_id 
                    AND today_perf.date = CURDATE()
                LEFT JOIN (
                    SELECT 
                        delivery_personnel_id,
                        SUM(total_deliveries) as monthly_deliveries,
                        SUM(earnings) as monthly_earnings
                    FROM delivery_performance 
                    WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
                    GROUP BY delivery_personnel_id
                ) month_stats ON dp.id = month_stats.delivery_personnel_id");

            // Order details view
            $conn->exec("CREATE OR REPLACE VIEW order_details AS
                SELECT 
                    o.id as order_id,
                    o.total_amount,
                    o.status,
                    o.delivery_address,
                    o.phone,
                    o.notes,
                    o.order_date,
                    o.delivery_date,
                    u_customer.name as customer_name,
                    u_customer.email as customer_email,
                    u_staff.name as staff_name,
                    u_delivery.name as delivery_person_name,
                    dp.vehicle_type,
                    dp.vehicle_number,
                    dp.current_status as delivery_status,
                    GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, ')') SEPARATOR ', ') as order_items
                FROM orders o
                JOIN users u_customer ON o.customer_id = u_customer.id
                LEFT JOIN users u_staff ON o.staff_id = u_staff.id
                LEFT JOIN users u_delivery ON o.delivery_id = u_delivery.id
                LEFT JOIN delivery_personnel dp ON o.delivery_id = dp.user_id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                GROUP BY o.id");

            // Daily sales summary view
            $conn->exec("CREATE OR REPLACE VIEW daily_sales_summary AS
                SELECT 
                    DATE(order_date) as sale_date,
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as delivered_revenue,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                    AVG(CASE WHEN status != 'cancelled' THEN total_amount END) as avg_order_value
                FROM orders
                GROUP BY DATE(order_date)
                ORDER BY sale_date DESC");

        } catch(PDOException $e) {
            // Views might already exist, continue
        }
    }

    public function insertSampleData() {
        $conn = $this->getConnection();
        if (!$conn) return false;

        try {
            // Insert sample menu items
            $conn->exec("INSERT IGNORE INTO menu_items (name, description, price, image, category, type, calories, persons, rating) VALUES
                ('Fresh Chicken Veggies', 'Fresh chicken with mixed vegetables', 499.00, 'assets/images/dish/1.png', 'breakfast', 'non_veg', 120, 2, 5.0),
                ('Grilled Chicken', 'Perfectly grilled chicken breast', 359.00, 'assets/images/dish/2.png', 'breakfast', 'non_veg', 80, 1, 4.3),
                ('Chinese Noodles', 'Delicious vegetarian noodles', 149.00, 'assets/images/dish/3.png', 'lunch', 'veg', 100, 2, 4.0),
                ('Chicken Noodles', 'Spicy chicken noodles', 379.00, 'assets/images/dish/4.png', 'lunch', 'non_veg', 120, 2, 4.5),
                ('Bread Boiled Egg', 'Healthy bread with boiled eggs', 99.00, 'assets/images/dish/5.png', 'dinner', 'non_veg', 120, 2, 5.0),
                ('Immunity Dish', 'Healthy vegetarian immunity booster', 159.00, 'assets/images/dish/6.png', 'dinner', 'veg', 120, 2, 5.0),
                ('Paneer Butter Masala', 'Rich and creamy paneer curry', 299.00, 'assets/images/dish/panneer butter masala.jpeg', 'lunch', 'veg', 150, 2, 4.8),
                ('Fish Curry', 'Spicy coastal fish curry', 399.00, 'assets/images/dish/Fish Curry.jpg', 'dinner', 'non_veg', 180, 2, 4.6),
                ('Veg Biryani', 'Aromatic vegetarian biryani', 249.00, 'assets/images/dish/Veg biryani.jpeg', 'lunch', 'veg', 200, 2, 4.4),
                ('Mutton Biryani', 'Traditional mutton biryani', 549.00, 'assets/images/dish/Mutton biryani.jpeg', 'dinner', 'non_veg', 250, 2, 4.9)");

            // Insert sample users
            $conn->exec("INSERT IGNORE INTO users (name, email, password, role, phone, address) VALUES
                ('Admin User', 'admin@fooddelivery.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '1234567890', 'Admin Office'),
                ('Staff Member', 'staff@fooddelivery.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '1234567891', 'Kitchen'),
                ('Delivery Boy', 'delivery@fooddelivery.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery', '1234567892', 'Delivery Hub'),
                ('John Delivery', 'john@fooddelivery.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery', '9876543210', 'North Zone'),
                ('Mike Delivery', 'mike@fooddelivery.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery', '9876543211', 'South Zone'),
                ('Sarah Chef', 'sarah@fooddelivery.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543212', 'Kitchen'),
                ('David Manager', 'david@fooddelivery.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '9876543213', 'Management Office'),
                ('Customer One', 'customer1@example.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '9876543214', '123 Main Street'),
                ('Customer Two', 'customer2@example.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '9876543215', '456 Oak Avenue')");

            // Insert admin data
            $conn->exec("INSERT IGNORE INTO admins (user_id, employee_id, department, access_level, salary, hire_date) VALUES
                ((SELECT id FROM users WHERE email = 'admin@fooddelivery.com'), 'ADM001', 'Management', 'super_admin', 50000.00, '2024-01-01'),
                ((SELECT id FROM users WHERE email = 'david@fooddelivery.com'), 'ADM002', 'Operations', 'admin', 40000.00, '2024-01-10')");

            // Insert staff data
            $conn->exec("INSERT IGNORE INTO staff (user_id, employee_id, department, shift_type, hourly_rate, hire_date) VALUES
                ((SELECT id FROM users WHERE email = 'staff@fooddelivery.com'), 'STF001', 'kitchen', 'morning', 250.00, '2024-01-15'),
                ((SELECT id FROM users WHERE email = 'sarah@fooddelivery.com'), 'STF002', 'preparation', 'afternoon', 280.00, '2024-01-20')");

            // Insert delivery personnel data
            $conn->exec("INSERT IGNORE INTO delivery_personnel (user_id, employee_id, vehicle_type, vehicle_number, delivery_zone, commission_rate, hire_date, current_status, total_deliveries, total_earnings, average_rating) VALUES
                ((SELECT id FROM users WHERE email = 'delivery@fooddelivery.com'), 'DEL001', 'bike', 'MH12AB1234', 'Central Mumbai', 50.00, '2024-01-20', 'available', 150, 7500.00, 4.5),
                ((SELECT id FROM users WHERE email = 'john@fooddelivery.com'), 'DEL002', 'scooter', 'MH12CD5678', 'North Mumbai', 50.00, '2024-01-25', 'available', 120, 6000.00, 4.3),
                ((SELECT id FROM users WHERE email = 'mike@fooddelivery.com'), 'DEL003', 'bike', 'MH12EF9012', 'South Mumbai', 50.00, '2024-02-01', 'busy', 98, 4900.00, 4.7)");

            return true;
        } catch(PDOException $exception) {
            echo "Sample data insertion error: " . $exception->getMessage();
            return false;
        }
    }

    public function initializeDatabase() {
        if ($this->setupTables()) {
            $this->insertSampleData();
            return true;
        }
        return false;
    }

    // Helper methods for common database operations
    public function getDeliveryPersonnelByZone($zone) {
        $conn = $this->getConnection();
        $query = "SELECT * FROM delivery_personnel_details WHERE delivery_zone = ? AND current_status = 'available'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$zone]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateDeliveryStatus($order_id, $delivery_personnel_id, $status, $notes = '', $created_by = 1) {
        $conn = $this->getConnection();
        $query = "INSERT INTO delivery_status_history (order_id, delivery_personnel_id, status, notes, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        return $stmt->execute([$order_id, $delivery_personnel_id, $status, $notes, $created_by]);
    }

    public function getOrdersForDelivery($delivery_personnel_id) {
        $conn = $this->getConnection();
        $query = "SELECT * FROM current_delivery_status WHERE delivery_personnel_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$delivery_personnel_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyPerformance($delivery_personnel_id, $date = null) {
        $conn = $this->getConnection();
        $date = $date ?: date('Y-m-d');
        $query = "SELECT * FROM delivery_performance WHERE delivery_personnel_id = ? AND date = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$delivery_personnel_id, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateDeliveryPersonnelLocation($delivery_personnel_id, $lat, $lng) {
        $conn = $this->getConnection();
        $query = "UPDATE delivery_personnel SET current_location_lat = ?, current_location_lng = ?, last_location_update = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        return $stmt->execute([$lat, $lng, $delivery_personnel_id]);
    }

    public function getAvailableStaff($department = null, $shift_type = null) {
        $conn = $this->getConnection();
        $query = "SELECT * FROM staff_details WHERE is_active = 1";
        $params = [];
        
        if ($department) {
            $query .= " AND department = ?";
            $params[] = $department;
        }
        
        if ($shift_type) {
            $query .= " AND shift_type = ?";
            $params[] = $shift_type;
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createStaffShift($staff_id, $shift_date, $start_time, $end_time, $notes = '') {
        $conn = $this->getConnection();
        $query = "INSERT INTO staff_shifts (staff_id, shift_date, start_time, end_time, notes) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        return $stmt->execute([$staff_id, $shift_date, $start_time, $end_time, $notes]);
    }

    public function getOrderAnalytics($start_date = null, $end_date = null) {
        $conn = $this->getConnection();
        $start_date = $start_date ?: date('Y-m-01');
        $end_date = $end_date ?: date('Y-m-d');
        
        $query = "SELECT * FROM daily_sales_summary WHERE sale_date BETWEEN ? AND ? ORDER BY sale_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Auto-initialize database on first include
$database = new Database();
if (!$database->getConnection()) {
    $database->initializeDatabase();
}
?>