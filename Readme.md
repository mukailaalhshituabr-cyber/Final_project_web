# Global Clothing Marketplace

A comprehensive PHP-based marketplace platform connecting independent tailors with customers seeking unique traditional or modern outfits.

## 🚀 Features

- **User Authentication** - Secure registration and login system with role-based access (Customer, Tailor, Admin)
- **Product Management** - Tailors can showcase and manage their products
- **Shopping Cart** - Full-featured cart with checkout functionality
- **Order Tracking** - Real-time order status updates
- **Messaging System** - Direct communication between customers and tailors
- **Review & Rating** - Customer feedback system
- **Payment Integration** - Stripe/PayPal support
- **Admin Panel** - Complete platform management dashboard
- **Responsive Design** - Mobile-friendly interface

## 📋 Prerequisites

- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser (Chrome, Firefox, Safari, Edge)
- Basic knowledge of PHP and MySQL

## 🛠️ Installation Steps

### 1. Setup XAMPP

1. Install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Start Apache and MySQL services from XAMPP Control Panel

### 2. Create Project Directory

```bash
# Navigate to XAMPP htdocs folder
cd C:/xampp/htdocs/

# Create project folder
mkdir clothing-marketplace
cd clothing-marketplace
```

### 3. Create Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `clothing_marketplace`
3. Click on the database
4. Go to SQL tab and run the SQL script from `sql/database.sql`

### 4. Configure Database Connection

Edit `config.php` and update these lines if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'clothing_marketplace');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
```

### 5. Set Up Directory Structure

Create the following folders in your project root:

```
clothing-marketplace/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/
│       ├── products/
│       └── profile/
├── includes/
│   ├── config/
│   ├── classes/
│   ├── components/
│   └── functions/
├── src/
│   ├── controllers/
│   ├── models/
│   └── views/
├── pages/
│   ├── auth/
│   ├── customer/
│   ├── tailor/
│   ├── admin/
│   ├── products/
│   └── cart/
├── api/
└── sql/
```

### 6. Set Permissions

Make sure the `assets/uploads/` directory is writable:

```bash
chmod -R 755 assets/uploads/
```

### 7. Access the Application

Open your browser and navigate to:
```
http://localhost/clothing-marketplace/
```

## 👤 Default Test Accounts

After setting up, you can create test accounts:

**Customer Account:**
- Register at: `http://localhost/clothing-marketplace/pages/auth/register.php`
- Select "Customer" as user type

**Tailor Account:**
- Register at: `http://localhost/clothing-marketplace/pages/auth/register.php`
- Select "Tailor" as user type

**Admin Account:**
- Create manually in database or via SQL:
```sql
INSERT INTO users (username, email, password, user_type, full_name) 
VALUES ('admin', 'admin@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5OfmZTMnJWbxm', 'admin', 'Admin User');
-- Password: Admin123
```

## 🔧 Configuration

### Payment Gateway Setup

Edit `config.php` to add your Stripe/PayPal credentials:

```php
define('STRIPE_PUBLIC_KEY', 'pk_test_your_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_key_here');
```

### Email Configuration

For email functionality, update PHPMailer settings in your controller files.

## 📁 File Structure Overview

- **config.php** - Main configuration file
- **index.php** - Homepage
- **includes/** - Core PHP classes and functions
- **assets/** - CSS, JavaScript, images
- **pages/** - User interface pages
- **api/** - API endpoints for AJAX requests
- **sql/** - Database schema and sample data

## 🔐 Security Features

- Password hashing using bcrypt
- CSRF token protection
- SQL injection prevention via prepared statements
- Input validation and sanitization
- Secure file upload handling
- Session timeout management

## 📝 Key Files Created

1. **config.php** - Application configuration
2. **Database.php** - Database connection and query handling
3. **User.php** - User management class
4. **helpers.php** - Utility functions
5. **validation.php** - Input validation functions
6. **auth_functions.php** - Authentication helpers
7. **login.php** - Login page
8. **register.php** - Registration page
9. **main.css** - Main stylesheet
10. **auth.css** - Authentication page styles
11. **auth.js** - Form validation JavaScript
12. **.htaccess** - Security and URL rewriting rules

## 🚧 Next Steps

### Implement Product Management
1. Create Product class (`includes/classes/Product.php`)
2. Build product listing page
3. Add product detail page
4. Implement product search and filtering

### Build Shopping Cart
1. Create Cart class
2. Add to cart functionality
3. Cart page with item management
4. Checkout process

### Add Order System
1. Create Order class
2. Order placement
3. Order tracking
4. Order history

### Implement Messaging
1. Create Chat class
2. Real-time messaging interface
3. Message notifications

### Payment Integration
1. Integrate Stripe API
2. Payment processing
3. Payment confirmation

### Admin Dashboard
1. User management
2. Product moderation
3. Order management
4. Analytics and reports

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running in XAMPP
- Check database credentials in `config.php`
- Ensure database exists

### Permission Denied on File Upload
- Check folder permissions: `chmod -R 755 assets/uploads/`
- Ensure Apache has write access

### Session Issues
- Verify session.save_path in php.ini is writable
- Check session configuration in php.ini

### .htaccess Not Working
- Enable mod_rewrite in Apache configuration
- Restart Apache after changes

## 📚 Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Stripe API Docs](https://stripe.com/docs/api)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)

## 🤝 Contributing

This is an academic project. Feel free to:
- Report bugs
- Suggest features
- Submit improvements

## 📄 License

Educational project for web technology coursework.

## 👨‍💻 Development Tips

1. **Test frequently** - Test each feature as you build it
2. **Use error logging** - Check `php_error.log` for issues
3. **Validate inputs** - Always validate user input on both client and server side
4. **Secure uploads** - Never trust user-uploaded files
5. **Use prepared statements** - Prevent SQL injection
6. **Keep backups** - Regularly backup your database

## 🎯 Project Milestones

- [x] Setup project structure
- [x] Database schema
- [x] User authentication
- [ ] Product management
- [ ] Shopping cart
- [ ] Payment integration
- [ ] Order system
- [ ] Messaging
- [ ] Reviews and ratings
- [ ] Admin panel

---

Good luck with your project! 🎉