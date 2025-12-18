
Global Clothing Marketplace
A comprehensive web-based platform that connects independent tailors with customers seeking unique traditional or modern outfits. This marketplace enables tailors to showcase their craftsmanship while providing customers access to custom-made clothing from around the world.

🌟 Features
Core Functionality
Multi-Role Authentication System - Customer, Tailor, and Admin roles
Product Management - Comprehensive product catalog with customization options
Shopping Cart & Checkout - Seamless shopping experience with multiple payment methods
Order Tracking System - Real-time order status updates
Real-time Messaging - Direct communication between customers and tailors
Review & Rating System - Customer feedback and product ratings
Payment Integration - Secure payments via Stripe/PayPal
Admin Panel - Complete system management and moderation tools
Advanced Features
Product Search & Filtering - By category, price, location, and more
Wishlist Management - Save favorite products for later
Address Management - Multiple shipping addresses per user
Coupon System - Discount codes and promotional offers
Notification System - Real-time updates and alerts
Analytics Dashboard - Sales and performance metrics for tailors
Email Integration - Automated email notifications
File Upload System - Product images and profile pictures
🛠️ Technology Stack
Backend
PHP 7.4+ - Server-side scripting
MySQL - Database management
PHPMailer - Email functionality
Composer - Dependency management
Frontend
HTML5 & CSS3 - Structure and styling
JavaScript/jQuery - Interactive functionality
Bootstrap 5 - Responsive design framework
AJAX - Dynamic content loading
Payment & APIs
Stripe API - Payment processing
PayPal API - Alternative payment method
Development Tools
XAMPP - Local development environment
Git - Version control
📁 Project Structure
clothing-marketplace/
│
├── assets/                    # Static assets
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   ├── images/               # Static images
│   └── uploads/              # User uploaded files
│
├── includes/                 # Core PHP includes
│   ├── config/              # Configuration files
│   ├── classes/             # PHP classes
│   ├── components/          # Reusable components
│   └── functions/           # Helper functions
│
├── pages/                   # Application pages
│   ├── auth/               # Authentication pages
│   ├── customer/           # Customer dashboard
│   ├── tailor/             # Tailor dashboard
│   ├── admin/              # Admin panel
│   ├── products/           # Product pages
│   └── cart/               # Shopping cart
│
├── api/                    # API endpoints
├── sql/                    # Database scripts
├── vendor/                 # Composer dependencies
└── Configuration files
🚀 Installation & Setup
Prerequisites
XAMPP (Apache, MySQL, PHP 7.4+)
Composer
Modern web browser
Installation Steps
Clone the Repository

bash


Copy code
git clone https://github.com/mukailaalhshituabr-cyber/Final_project_web.git
cd clothing-marketplace
Install Dependencies

bash


Copy code
composer install
Database Setup

Start XAMPP (Apache & MySQL)
Access phpMyAdmin (http://169.239.251.102:341/~mukaila.shittu/Final_project_web//pages/admin/dashboard.php)
Create a new database named clothing_marketplace
Import the SQL file: sql/database.sql
Configuration

Copy config.php.example to config.php
Update database credentials in includes/config/database.php
Configure Stripe API keys in includes/config/stripe-config.php
Set Permissions

bash


Copy code
chmod 755 assets/uploads/
chmod 755 assets/uploads/products/
chmod 755 assets/uploads/profile/
Access the Application

Navigate to http://169.239.251.102:341/~mukaila.shittu/Final_project_web//index.php
🔧 Configuration
Database Configuration
php


Copy code
// includes/config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clothing_marketplace');
Payment Configuration
php


Copy code
// includes/config/stripe-config.php
define('STRIPE_PUBLISHABLE_KEY', 'your_stripe_publishable_key');
define('STRIPE_SECRET_KEY', 'your_stripe_secret_key');
👥 User Roles & Permissions
Customer
Browse and search products
Add items to cart and wishlist
Place orders and track status
Communicate with tailors
Leave reviews and ratings
Manage profile and addresses
Tailor
Create and manage product listings
Process customer orders
Upload product images
Communicate with customers
View sales analytics
Manage inventory
Admin
Manage all users and products
Monitor orders and transactions
Handle disputes and reviews
Configure system settings
View comprehensive analytics
Moderate content
🗄️ Database Schema
The application uses 15+ interconnected tables including:

users - User authentication and profiles
products - Product catalog
orders & order_items - Order management
cart & wishlist - Shopping features
messages & conversations - Communication system
reviews - Product reviews and ratings
addresses - Shipping addresses
coupons - Discount system
notifications - System alerts
🔒 Security Features
Authentication & Authorization
Secure password hashing with password_hash()
Role-based access control
Session management
CSRF protection
Data Protection
SQL injection prevention with prepared statements
Input validation and sanitization
File upload security
XSS protection
Production Security
HTTPS enforcement
Secure headers
Rate limiting
Error logging
🎨 Frontend Features
Responsive Design
Mobile-first approach
Bootstrap 5 integration
Cross-browser compatibility
Touch-friendly interface
User Experience
Intuitive navigation
Real-time updates
Loading indicators
Error handling
Search autocomplete
📱 API Endpoints
Authentication
POST /api/auth.php - User login/logout
POST /api/register.php - User registration
Products
GET /api/products.php - Fetch products
POST /api/products.php - Create product (tailor)
PUT /api/products.php - Update product
Orders
GET /api/orders.php - Fetch orders
POST /api/orders.php - Create order
PUT /api/orders.php - Update order status
Chat
GET /api/chat.php - Fetch messages
POST /api/chat.php - Send message
🧪 Testing
Manual Testing Checklist
 User registration and login
 Product creation and management
 Shopping cart functionality
 Order placement and tracking
 Payment processing
 Messaging system
 Admin panel features
📈 Performance Optimization
Database Optimization
Indexed frequently queried columns
Optimized SQL queries
Connection pooling
Query caching
Frontend Optimization
Minified CSS/JS files
Image optimization
Lazy loading
CDN integration (optional)
🚀 Deployment
Production Checklist
 Update database credentials
 Configure SSL certificates
 Set up error logging
 Configure backup system
 Update payment API keys
 Set proper file permissions
 Enable production mode
Recommended Hosting
Shared Hosting: cPanel with PHP 7.4+ and MySQL
VPS: Ubuntu/CentOS with LAMP stack
Cloud: AWS, DigitalOcean, or Google Cloud
🤝 Contributing
Fork the repository
Create a feature branch (git checkout -b feature/AmazingFeature)
Commit changes (git commit -m 'Add AmazingFeature')
Push to branch (git push origin feature/AmazingFeature)
Open a Pull Request
📝 License
This project is licensed under the MIT License - see the LICENSE file for details.

🆘 Support
For support and questions:

Create an issue on GitHub
Email: support@clothingmarketplace.com
Documentation: Wiki
🎯 Future Enhancements
Mobile App - React Native/Flutter app
AI Recommendations - Machine learning product suggestions
Video Chat - Real-time video consultations
Multi-language - Internationalization support
Social Features - User profiles and social sharing
Advanced Analytics - Business intelligence dashboard