-- Food Delivery System Database - Complete Enhanced Version
-- Run this in phpMyAdmin or MySQL

CREATE DATABASE IF NOT EXISTS food_delivery;
USE food_delivery;

-- Users table for authentication (existing table)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'staff', 'admin', 'delivery') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Menu items table (existing table)
CREATE TABLE IF NOT EXISTS menu_items (
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
);

-- Cart table (existing table)
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- Orders table (existing table)
CREATE TABLE IF NOT EXISTS orders (
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
);

-- Order items table (existing table)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- NEW: Admins table for additional admin-specific information
CREATE TABLE IF NOT EXISTS admins (
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
);

-- NEW: Staff table for kitchen staff specific information
CREATE TABLE IF NOT EXISTS staff (
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
);

-- NEW: Delivery Personnel table for delivery-specific information
CREATE TABLE IF NOT EXISTS delivery_personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    employee_id VARCHAR(50) UNIQUE,
    vehicle_type ENUM('bike', 'scooter', 'car', 'bicycle') DEFAULT 'bike',
    vehicle_number VARCHAR(20),
    license_number VARCHAR(50),
    delivery_zone VARCHAR(100),
    max_delivery_radius INT DEFAULT 10, -- in kilometers
    commission_rate DECIMAL(5,2) DEFAULT 50.00, -- per delivery
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
);

-- NEW: Delivery Status Tracking table
CREATE TABLE IF NOT EXISTS delivery_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    delivery_personnel_id INT NOT NULL,
    status ENUM('assigned', 'picked_up', 'in_transit', 'arrived', 'delivered', 'failed', 'returned') NOT NULL,
    location_lat DECIMAL(10,8) NULL,
    location_lng DECIMAL(11,8) NULL,
    notes TEXT,
    estimated_delivery_time TIMESTAMP NULL,
    actual_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL, -- who updated the status
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_personnel_id) REFERENCES delivery_personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- NEW: Delivery Routes table for tracking delivery efficiency
CREATE TABLE IF NOT EXISTS delivery_routes (
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
);

-- NEW: Delivery Performance Metrics table
CREATE TABLE IF NOT EXISTS delivery_performance (
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
);

-- NEW: Staff Shifts table for tracking work schedules
CREATE TABLE IF NOT EXISTS staff_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_duration INT DEFAULT 30, -- in minutes
    status ENUM('scheduled', 'in_progress', 'completed', 'absent', 'cancelled') DEFAULT 'scheduled',
    orders_handled INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_shift (staff_id, shift_date, start_time)
);

-- NEW: Customer Reviews table for delivery and food ratings
CREATE TABLE IF NOT EXISTS customer_reviews (
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
);

-- Insert sample menu items (existing data)
INSERT IGNORE INTO menu_items (name, description, price, image, category, type, calories, persons, rating) VALUES
('Fresh Chicken Veggies', 'Fresh chicken with mixed vegetables', 499.00, 'assets/images/dish/1.png', 'breakfast', 'non_veg', 120, 2, 5.0),
('Grilled Chicken', 'Perfectly grilled chicken breast', 359.00, 'assets/images/dish/2.png', 'breakfast', 'non_veg', 80, 1, 4.3),
('Chinese Noodles', 'Delicious vegetarian noodles', 149.00, 'assets/images/dish/3.png', 'lunch', 'veg', 100, 2, 4.0),
('Chicken Noodles', 'Spicy chicken noodles', 379.00, 'assets/images/dish/4.png', 'lunch', 'non_veg', 120, 2, 4.5),
('Bread Boiled Egg', 'Healthy bread with boiled eggs', 99.00, 'assets/images/dish/5.png', 'dinner', 'non_veg', 120, 2, 5.0),
('Immunity Dish', 'Healthy vegetarian immunity booster', 159.00, 'assets/images/dish/6.png', 'dinner', 'veg', 120, 2, 5.0),
('Paneer Butter Masala', 'Rich and creamy paneer curry', 299.00, 'assets/images/dish/7.png', 'lunch', 'veg', 150, 2, 4.8),
('Fish Curry', 'Spicy coastal fish curry', 399.00, 'assets/images/dish/8.png', 'dinner', 'non_veg', 180, 2, 4.6),
('Veg Biryani', 'Aromatic vegetarian biryani', 249.00, 'assets/images/dish/9.png', 'lunch', 'veg', 200, 2, 4.4),
('Mutton Biryani', 'Traditional mutton biryani', 549.00, 'assets/images/dish/10.png', 'dinner', 'non_veg', 250, 2, 4.9);

-- Insert sample users (existing data + additional)
INSERT IGNORE INTO users (name, email, password, role, phone, address) VALUES
('Admin User', 'admin@fooddelivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '1234567890', 'Admin Office'),
('Staff Member', 'staff@fooddelivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '1234567891', 'Kitchen'),
('Delivery Boy', 'delivery@fooddelivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery', '1234567892', 'Delivery Hub'),
('John Delivery', 'john@fooddelivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery', '9876543210', 'North Zone'),
('Mike Delivery', 'mike@fooddelivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery', '9876543211', 'South Zone'),
('Sarah Chef', 'sarah@fooddelivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543212', 'Kitchen'),
('David Manager', 'david@fooddelivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '9876543213', 'Management Office'),
('Customer One', 'customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '9876543214', '123 Main Street'),
('Customer Two', 'customer2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '9876543215', '456 Oak Avenue');

-- Insert sample admin data
INSERT IGNORE INTO admins (user_id, employee_id, department, access_level, salary, hire_date) VALUES
((SELECT id FROM users WHERE email = 'admin@fooddelivery.com'), 'ADM001', 'Management', 'super_admin', 50000.00, '2024-01-01'),
((SELECT id FROM users WHERE email = 'david@fooddelivery.com'), 'ADM002', 'Operations', 'admin', 40000.00, '2024-01-10');

-- Insert sample staff data
INSERT IGNORE INTO staff (user_id, employee_id, department, shift_type, hourly_rate, hire_date) VALUES
((SELECT id FROM users WHERE email = 'staff@fooddelivery.com'), 'STF001', 'kitchen', 'morning', 250.00, '2024-01-15'),
((SELECT id FROM users WHERE email = 'sarah@fooddelivery.com'), 'STF002', 'preparation', 'afternoon', 280.00, '2024-01-20');

-- Insert sample delivery personnel data
INSERT IGNORE INTO delivery_personnel (user_id, employee_id, vehicle_type, vehicle_number, delivery_zone, commission_rate, hire_date, current_status, total_deliveries, total_earnings, average_rating) VALUES
((SELECT id FROM users WHERE email = 'delivery@fooddelivery.com'), 'DEL001', 'bike', 'MH12AB1234', 'Central Mumbai', 50.00, '2024-01-20', 'available', 150, 7500.00, 4.5),
((SELECT id FROM users WHERE email = 'john@fooddelivery.com'), 'DEL002', 'scooter', 'MH12CD5678', 'North Mumbai', 50.00, '2024-01-25', 'available', 120, 6000.00, 4.3),
((SELECT id FROM users WHERE email = 'mike@fooddelivery.com'), 'DEL003', 'bike', 'MH12EF9012', 'South Mumbai', 50.00, '2024-02-01', 'busy', 98, 4900.00, 4.7);

-- Insert sample orders
INSERT IGNORE INTO orders (customer_id, staff_id, delivery_id, total_amount, status, delivery_address, phone, notes, order_date) VALUES
((SELECT id FROM users WHERE email = 'customer1@example.com'), (SELECT id FROM users WHERE email = 'staff@fooddelivery.com'), (SELECT id FROM users WHERE email = 'delivery@fooddelivery.com'), 499.00, 'delivered', '123 Main Street, Mumbai', '9876543214', 'Ring the bell twice', '2024-12-01 12:30:00'),
((SELECT id FROM users WHERE email = 'customer2@example.com'), (SELECT id FROM users WHERE email = 'sarah@fooddelivery.com'), (SELECT id FROM users WHERE email = 'john@fooddelivery.com'), 379.00, 'out_for_delivery', '456 Oak Avenue, Mumbai', '9876543215', 'Leave at door', '2024-12-01 13:45:00'),
((SELECT id FROM users WHERE email = 'customer1@example.com'), (SELECT id FROM users WHERE email = 'staff@fooddelivery.com'), NULL, 249.00, 'preparing', '123 Main Street, Mumbai', '9876543214', 'Extra spicy', '2024-12-01 14:15:00');

-- Insert sample staff shifts
INSERT IGNORE INTO staff_shifts (staff_id, shift_date, start_time, end_time, status, orders_handled) VALUES
((SELECT id FROM staff WHERE employee_id = 'STF001'), '2024-12-01', '08:00:00', '16:00:00', 'completed', 25),
((SELECT id FROM staff WHERE employee_id = 'STF002'), '2024-12-01', '12:00:00', '20:00:00', 'in_progress', 15),
((SELECT id FROM staff WHERE employee_id = 'STF001'), '2024-12-02', '08:00:00', '16:00:00', 'scheduled', 0);

-- Insert sample delivery performance data
INSERT IGNORE INTO delivery_performance (delivery_personnel_id, date, total_deliveries, successful_deliveries, failed_deliveries, total_distance_km, total_time_minutes, earnings, customer_rating, on_time_deliveries, late_deliveries) VALUES
((SELECT id FROM delivery_personnel WHERE employee_id = 'DEL001'), '2024-12-01', 8, 7, 1, 45.5, 480, 400.00, 4.5, 6, 1),
((SELECT id FROM delivery_personnel WHERE employee_id = 'DEL002'), '2024-12-01', 6, 6, 0, 38.2, 360, 300.00, 4.3, 5, 1),
((SELECT id FROM delivery_personnel WHERE employee_id = 'DEL003'), '2024-12-01', 5, 4, 1, 32.8, 300, 250.00, 4.7, 4, 0);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_orders_delivery ON orders(delivery_id);
CREATE INDEX IF NOT EXISTS idx_orders_date ON orders(order_date);
CREATE INDEX IF NOT EXISTS idx_menu_items_category ON menu_items(category);
CREATE INDEX IF NOT EXISTS idx_menu_items_available ON menu_items(is_available);
CREATE INDEX IF NOT EXISTS idx_admins_user_id ON admins(user_id);
CREATE INDEX IF NOT EXISTS idx_staff_user_id ON staff(user_id);
CREATE INDEX IF NOT EXISTS idx_staff_department ON staff(department);
CREATE INDEX IF NOT EXISTS idx_delivery_personnel_user_id ON delivery_personnel(user_id);
CREATE INDEX IF NOT EXISTS idx_delivery_personnel_status ON delivery_personnel(current_status);
CREATE INDEX IF NOT EXISTS idx_delivery_personnel_zone ON delivery_personnel(delivery_zone);
CREATE INDEX IF NOT EXISTS idx_delivery_status_order ON delivery_status_history(order_id);
CREATE INDEX IF NOT EXISTS idx_delivery_status_personnel ON delivery_status_history(delivery_personnel_id);
CREATE INDEX IF NOT EXISTS idx_delivery_routes_personnel ON delivery_routes(delivery_personnel_id);
CREATE INDEX IF NOT EXISTS idx_delivery_routes_status ON delivery_routes(status);
CREATE INDEX IF NOT EXISTS idx_delivery_performance_date ON delivery_performance(date);
CREATE INDEX IF NOT EXISTS idx_delivery_performance_personnel ON delivery_performance(delivery_personnel_id);
CREATE INDEX IF NOT EXISTS idx_staff_shifts_date ON staff_shifts(shift_date);
CREATE INDEX IF NOT EXISTS idx_customer_reviews_order ON customer_reviews(order_id);

-- Create views for easier data access
CREATE OR REPLACE VIEW admin_details AS
SELECT 
    u.id, u.name, u.email, u.phone, u.address,
    a.employee_id, a.department, a.access_level, 
    a.last_login, a.salary, a.hire_date, a.is_active
FROM users u
JOIN admins a ON u.id = a.user_id
WHERE u.role = 'admin';

CREATE OR REPLACE VIEW staff_details AS
SELECT 
    u.id, u.name, u.email, u.phone, u.address,
    s.employee_id, s.department, s.shift_type, 
    s.hourly_rate, s.hire_date, s.performance_rating,
    s.total_orders_completed, s.is_active
FROM users u
JOIN staff s ON u.id = s.user_id
WHERE u.role = 'staff';

CREATE OR REPLACE VIEW delivery_personnel_details AS
SELECT 
    u.id, u.name, u.email, u.phone, u.address,
    d.employee_id, d.vehicle_type, d.vehicle_number,
    d.delivery_zone, d.total_deliveries, d.total_earnings,
    d.average_rating, d.current_status, d.is_active,
    d.current_location_lat, d.current_location_lng,
    d.last_location_update
FROM users u
JOIN delivery_personnel d ON u.id = d.user_id
WHERE u.role = 'delivery';

-- Create view for current delivery status
CREATE OR REPLACE VIEW current_delivery_status AS
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
WHERE o.status IN ('out_for_delivery', 'delivered');

-- Create view for delivery performance summary
CREATE OR REPLACE VIEW delivery_performance_summary AS
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
) month_stats ON dp.id = month_stats.delivery_personnel_id;

-- Create view for order details with all related information
CREATE OR REPLACE VIEW order_details AS
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
GROUP BY o.id;

-- Create view for daily sales summary
CREATE OR REPLACE VIEW daily_sales_summary AS
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
ORDER BY sale_date DESC;