-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'tailor', 'admin') DEFAULT 'customer',
    full_name VARCHAR(100),
    profile_pic VARCHAR(255),
    address TEXT,
    phone VARCHAR(20),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tailor_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    subcategory VARCHAR(50),
    material VARCHAR(100),
    size ENUM('XS','S','M','L','XL','Custom'),
    color VARCHAR(50),
    images JSON,
    stock INT DEFAULT 1,
    is_customizable BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tailor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders Table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT,
    tailor_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (tailor_id) REFERENCES users(id)
);

-- Order Items Table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    customization_details TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Cart Table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    customization TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Messages Table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    receiver_id INT,
    order_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Reviews Table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    customer_id INT,
    order_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Wishlist Table
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_tailor ON products(tailor_id);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_tailor ON orders(tailor_id);
CREATE INDEX idx_messages_conversation ON messages(sender_id, receiver_id);




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