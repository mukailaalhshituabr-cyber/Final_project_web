-- ============================================
-- CLOTHING MARKETPLACE DATABASE
-- WebTech Final Project - Mukaila Shittu
-- ============================================

-- Fresh start: Drop and recreate the database
DROP DATABASE IF EXISTS webtech_2025A_mukaila_shittu;
CREATE DATABASE webtech_2025A_mukaila_shittu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webtech_2025A_mukaila_shittu;

-- 1. USERS TABLE
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'tailor', 'admin') DEFAULT 'customer',
    full_name VARCHAR(100),
    profile_pic VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    bio TEXT,
    experience VARCHAR(50),
    specialization VARCHAR(100),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_expires DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. USER_PROFILES
CREATE TABLE user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    measurements JSON,
    newsletter_subscription BOOLEAN DEFAULT TRUE,
    notification_preferences JSON,
    social_links JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. CATEGORIES
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 4. PRODUCTS
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tailor_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    sku VARCHAR(100),
    category VARCHAR(50),
    subcategory VARCHAR(50),
    material VARCHAR(100),
    size ENUM('XS','S','M','L','XL','XXL','Custom'),
    color VARCHAR(50),
    brand VARCHAR(100),
    tags JSON,
    images JSON,
    specifications JSON,
    is_customizable BOOLEAN DEFAULT FALSE,
    customization_options JSON,
    stock_quantity INT DEFAULT 1,
    low_stock_threshold INT DEFAULT 5,
    weight DECIMAL(8,2),
    dimensions VARCHAR(100),
    rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    wishlist_count INT DEFAULT 0,
    status ENUM('draft', 'active', 'inactive', 'out_of_stock') DEFAULT 'draft',
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tailor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. ORDERS
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    tailor_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    coupon_code VARCHAR(50),
    status ENUM('pending', 'confirmed', 'processing', 'ready', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_id VARCHAR(100),
    shipping_method VARCHAR(100),
    tracking_number VARCHAR(100),
    shipping_address TEXT,
    billing_address TEXT,
    customer_notes TEXT,
    tailor_notes TEXT,
    admin_notes TEXT,
    estimated_delivery DATE,
    delivered_at DATETIME,
    cancelled_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tailor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. ORDER_ITEMS
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    customization_details TEXT,
    measurements JSON,
    status ENUM('pending', 'in_production', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. CART
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    customization TEXT,
    measurements JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- 8. WISHLIST
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id)
);

-- 9. REVIEWS
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    images JSON,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- 10. MESSAGES
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id VARCHAR(100) NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_type ENUM('text', 'image', 'file', 'voice') DEFAULT 'text',
    message TEXT,
    file_url VARCHAR(255),
    file_name VARCHAR(255),
    file_size INT,
    file_type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    delivered_at DATETIME,
    deleted_by_sender BOOLEAN DEFAULT FALSE,
    deleted_by_receiver BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 11. CONVERSATIONS
CREATE TABLE conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_id INT,
    last_message_at DATETIME,
    unread_count_user1 INT DEFAULT 0,
    unread_count_user2 INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_conversation (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (last_message_id) REFERENCES messages(id) ON DELETE SET NULL
);

-- 12. NOTIFICATIONS
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    related_type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 13. ADDRESSES
CREATE TABLE addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    label VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 14. COUPONS
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_order DECIMAL(10,2),
    maximum_discount DECIMAL(10,2),
    usage_limit INT,
    per_user_limit INT DEFAULT 1,
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE coupon_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 15. TAGS & SETTINGS
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_tags (
    product_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (product_id, tag_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'array') DEFAULT 'string',
    category VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- PERFORMANCE INDEXES
-- ============================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_products_tailor ON products(tailor_id);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_messages_unread ON messages(receiver_id, is_read);

-- ============================================
-- VIEWS
-- ============================================
CREATE VIEW vw_active_products AS
SELECT p.*, u.full_name as tailor_name
FROM products p
JOIN users u ON p.tailor_id = u.id
WHERE p.status = 'active';

-- ============================================
-- INITIAL DATA
-- ============================================
INSERT INTO categories (name, slug, display_order) VALUES 
('Traditional Wear', 'traditional-wear', 1),
('Modern Fashion', 'modern-fashion', 2);

INSERT INTO site_settings (setting_key, setting_value) VALUES 
('site_name', 'Global Clothing Marketplace'),
('currency', 'USD');

SELECT 'Full Database Script Executed Successfully!' as Status;



/*
-- ============================================
-- SAMPLE DATA FOR TESTING
-- ============================================

USE webtech_2025A_mukaila_shittu;

-- ============================================
-- SAMPLE USERS
-- ============================================

-- Sample Customers (password: customer123)
INSERT INTO users (username, email, password, user_type, full_name, phone, address, status, email_verified, created_at) VALUES
('john_doe', 'john@example.com', '$2y$10$HashForCustomer1', 'customer', 'John Doe', '+1234567890', '123 Main St, New York, USA', 'active', TRUE, NOW()),
('jane_smith', 'jane@example.com', '$2y$10$HashForCustomer2', 'customer', 'Jane Smith', '+1987654321', '456 Oak Ave, London, UK', 'active', TRUE, NOW()),
('mike_wilson', 'mike@example.com', '$2y$10$HashForCustomer3', 'customer', 'Mike Wilson', '+1122334455', '789 Pine Rd, Sydney, Australia', 'active', TRUE, NOW());

-- Sample Tailors (password: tailor123)
INSERT INTO users (username, email, password, user_type, full_name, phone, address, bio, experience, specialization, status, email_verified, created_at) VALUES
('tailor_ahmed', 'ahmed@example.com', '$2y$10$HashForTailor1', 'tailor', 'Ahmed Hassan', '+20123456789', '15 Nile St, Cairo, Egypt', 'Master tailor with 15 years of experience in traditional Egyptian clothing. Specializes in galabeya and traditional wedding attire.', '15+', 'traditional', 'active', TRUE, NOW()),
('tailor_maria', 'maria@example.com', '$2y$10$HashForTailor2', 'tailor', 'Maria Garcia', '+34911223344', '23 Madrid Ave, Barcelona, Spain', 'Fashion designer specializing in modern European fashion. Creates custom suits and dresses for special occasions.', '8-10', 'modern', 'active', TRUE, NOW()),
('tailor_li', 'li@example.com', '$2y$10$HashForTailor3', 'tailor', 'Li Chen', '+86123456789', '45 Silk Rd, Shanghai, China', 'Expert in traditional Chinese clothing and cheongsam. Also creates modern fusion designs combining Eastern and Western styles.', '12+', 'traditional,custom', 'active', TRUE, NOW());

-- ============================================
-- SAMPLE PRODUCTS
-- ============================================

-- Products by Tailor Ahmed
INSERT INTO products (tailor_id, title, slug, description, price, category, material, size, color, images, is_customizable, stock_quantity, status, featured, created_at) VALUES
(2, 'Traditional Egyptian Galabeya', 'traditional-egyptian-galabeya', 'Handmade Egyptian galabeya with intricate embroidery. Perfect for special occasions and celebrations.', 89.99, 'Traditional Wear', 'Cotton and Silk', 'M', 'White', '["galabeya1.jpg", "galabeya2.jpg"]', TRUE, 5, 'active', TRUE, NOW()),
(2, 'Embroidered Kaftan Dress', 'embroidered-kaftan-dress', 'Beautiful kaftan dress with traditional Egyptian embroidery. Lightweight and comfortable for summer events.', 129.99, 'Traditional Wear', 'Silk', 'L', 'Blue', '["kaftan1.jpg", "kaftan2.jpg"]', TRUE, 3, 'active', FALSE, NOW());

-- Products by Tailor Maria
INSERT INTO products (tailor_id, title, slug, description, price, category, material, size, color, images, is_customizable, stock_quantity, status, featured, created_at) VALUES
(3, 'Custom Tailored Suit', 'custom-tailored-suit', 'Handcrafted suit made to your measurements. Perfect for weddings, business meetings, and special events.', 299.99, 'Modern Fashion', 'Wool', 'Custom', 'Navy Blue', '["suit1.jpg", "suit2.jpg"]', TRUE, 1, 'active', TRUE, NOW()),
(3, 'Evening Gown', 'evening-gown', 'Elegant evening gown for formal events. Available in various colors and fabrics.', 199.99, 'Modern Fashion', 'Satin', 'S', 'Black', '["gown1.jpg", "gown2.jpg"]', TRUE, 2, 'active', FALSE, NOW());

-- Products by Tailor Li
INSERT INTO products (tailor_id, title, slug, description, price, category, material, size, color, images, is_customizable, stock_quantity, status, featured, created_at) VALUES
(4, 'Traditional Cheongsam', 'traditional-cheongsam', 'Authentic Chinese cheongsam with silk embroidery. Made with traditional techniques.', 159.99, 'Traditional Wear', 'Silk', 'M', 'Red', '["cheongsam1.jpg", "cheongsam2.jpg"]', TRUE, 4, 'active', TRUE, NOW()),
(4, 'Modern Qipao Dress', 'modern-qipao-dress', 'Contemporary qipao dress combining traditional elements with modern design.', 139.99, 'Custom Tailoring', 'Silk Blend', 'S', 'Purple', '["qipao1.jpg", "qipao2.jpg"]', TRUE, 2, 'active', FALSE, NOW());

-- ============================================
-- SAMPLE ORDERS
-- ============================================

-- Order 1
INSERT INTO orders (order_number, customer_id, tailor_id, total_amount, subtotal, tax_amount, shipping_amount, status, payment_status, payment_method, shipping_address, created_at) VALUES
('ORD-001', 1, 2, 95.98, 89.99, 5.00, 0.99, 'delivered', 'paid', 'credit_card', '123 Main St, New York, USA', DATE_SUB(NOW(), INTERVAL 30 DAY));

INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price, status) VALUES
(1, 1, 1, 89.99, 89.99, 'completed');

-- Order 2
INSERT INTO orders (order_number, customer_id, tailor_id, total_amount, subtotal, tax_amount, shipping_amount, status, payment_status, payment_method, shipping_address, created_at) VALUES
('ORD-002', 2, 3, 315.98, 299.99, 16.00, 0.99, 'processing', 'paid', 'paypal', '456 Oak Ave, London, UK', DATE_SUB(NOW(), INTERVAL 15 DAY));

INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price, status) VALUES
(2, 3, 1, 299.99, 299.99, 'in_production');

-- ============================================
-- SAMPLE WISHLIST ITEMS
-- ============================================

INSERT INTO wishlist (user_id, product_id, created_at) VALUES
(1, 4, NOW()),
(1, 6, NOW()),
(2, 1, NOW()),
(3, 5, NOW());

-- ============================================
-- SAMPLE CART ITEMS
-- ============================================

INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES
(1, 2, 1, NOW()),
(2, 4, 1, NOW());

-- ============================================
-- SAMPLE REVIEWS
-- ============================================

INSERT INTO reviews (product_id, user_id, order_id, rating, title, comment, is_verified_purchase, status, created_at) VALUES
(1, 1, 1, 5, 'Absolutely Beautiful!', 'The galabeya exceeded my expectations. The embroidery is stunning and the fit is perfect. Highly recommend!', TRUE, 'approved', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(1, 2, NULL, 4, 'Great Quality', 'Beautiful craftsmanship. The only reason for 4 stars is that it took longer than expected to arrive.', FALSE, 'approved', DATE_SUB(NOW(), INTERVAL 20 DAY));

-- ============================================
-- SAMPLE ADDRESSES
-- ============================================

INSERT INTO addresses (user_id, label, full_name, phone, address_line1, city, state, country, postal_code, is_default) VALUES
(1, 'Home', 'John Doe', '+1234567890', '123 Main Street', 'New York', 'NY', 'USA', '10001', TRUE),
(1, 'Work', 'John Doe', '+1234567890', '456 Business Ave', 'New York', 'NY', 'USA', '10002', FALSE),
(2, 'Home', 'Jane Smith', '+1987654321', '456 Oak Avenue', 'London', 'Greater London', 'UK', 'SW1A 1AA', TRUE);

-- ============================================
-- SAMPLE MESSAGES
-- ============================================

-- Conversation between Customer 1 and Tailor 1
INSERT INTO messages (conversation_id, sender_id, receiver_id, message, is_read, created_at) VALUES
('1_2', 1, 2, 'Hello, I''m interested in customizing the galabeya. Can you add gold embroidery?', TRUE, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('1_2', 2, 1, 'Yes, I can add gold embroidery. It will be an additional $20. Is that okay?', TRUE, DATE_SUB(NOW(), INTERVAL 4 DAY)),
('1_2', 1, 2, 'That sounds perfect! Please proceed with the customization.', FALSE, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ============================================
-- SAMPLE NOTIFICATIONS
-- ============================================

INSERT INTO notifications (user_id, type, title, message, related_id, related_type, created_at) VALUES
(1, 'order', 'Order Delivered', 'Your order ORD-001 has been delivered.', 1, 'order', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(2, 'order', 'Order Confirmed', 'Your order ORD-002 has been confirmed and is now being processed.', 2, 'order', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(2, 'message', 'New Message', 'You have a new message from John Doe.', 1, 'user', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- ============================================
-- UPDATE PRODUCT STATISTICS
-- ============================================

-- Update product ratings based on reviews
UPDATE products p
SET rating = (
    SELECT AVG(rating) 
    FROM reviews r 
    WHERE r.product_id = p.id AND r.status = 'approved'
),
review_count = (
    SELECT COUNT(*) 
    FROM reviews r 
    WHERE r.product_id = p.id AND r.status = 'approved'
)
WHERE id IN (1, 2, 3, 4, 5, 6);

-- Update wishlist counts
UPDATE products p
SET wishlist_count = (
    SELECT COUNT(*) 
    FROM wishlist w 
    WHERE w.product_id = p.id
)
WHERE id IN (1, 2, 3, 4, 5, 6);

-- ============================================
-- FINAL MESSAGE
-- ============================================
SELECT 'Sample data inserted successfully!' as message;

*/