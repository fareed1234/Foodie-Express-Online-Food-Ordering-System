# Food Ordering System

A complete PHP-based food ordering system with role-based authentication and dashboards.

## Features

### User Roles
- **Admin**: Manage restaurants, menu items, view all orders and customers
- **Customer**: Browse menu, place orders, view order history
- **Restaurant Staff**: View incoming orders, update order status
- **Delivery Person**: View orders ready for delivery, mark orders as delivered

### Functionalities
1. **Registration & Login**: Role-based authentication system
2. **Admin Dashboard**: Complete management system
3. **Customer Dashboard**: Menu browsing and order management
4. **Staff Dashboard**: Order processing and status updates
5. **Delivery Dashboard**: Delivery management system

## Installation

1. **Database Setup**:
   - Create a MySQL database named `food_ordering_system`
   - Import the SQL schema from `database/schema.sql`

2. **Configuration**:
   - Update database credentials in `config/database.php`
   - Ensure your web server has PHP 7.4+ with PDO MySQL extension

3. **File Structure**:
   \`\`\`
   food-ordering-system/
   ├── config/
   │   └── database.php
   ├── includes/
   │   ├── header.php
   │   ├── footer.php
   │   └── session.php
   ├── admin/
   │   └── dashboard.php
   ├── customer/
   │   ├── dashboard.php
   │   └── browse_menu.php
   ├── staff/
   │   └── dashboard.php
   ├── delivery/
   │   └── dashboard.php
   ├── database/
   │   └── schema.sql
   ├── assets/ (copy from original HTML)
   ├── index.html (original homepage)
   ├── register.php
   ├── login.php
   ├── logout.php
   └── unauthorized.php
   \`\`\`

4. **Assets**:
   - Copy all CSS, JS, and image files from your original HTML template to the `assets/` folder

## Default Login Credentials

- **Admin**: 
  - Email: admin@restaurant.com
  - Password: admin123
  - Role: Admin

## Usage

1. Start with the original `index.html` homepage
2. Users can register with different roles
3. Login redirects to role-specific dashboards
4. Each role has specific functionalities as per requirements

## Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control
- SQL injection prevention using prepared statements
- Input validation and sanitization

## Next Steps

To complete the system, you can add:
- Shopping cart functionality
- Payment integration
- Email notifications
- Order tracking
- Restaurant management features
- Menu item image uploads
- Advanced reporting
