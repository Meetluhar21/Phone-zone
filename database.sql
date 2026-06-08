-- ============================================
-- SMARTPHONE STORE DATABASE SCHEMA
-- ============================================

DROP DATABASE IF EXISTS smartphone_store;
CREATE DATABASE smartphone_store;
USE smartphone_store;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    icon VARCHAR(50)
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    stock INT DEFAULT 0,
    description TEXT,
    specs TEXT,
    image_url VARCHAR(500),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    payment_method VARCHAR(50) DEFAULT 'COD',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- SEED DATA
-- ============================================

INSERT INTO categories (name, slug, icon) VALUES
('Flagship', 'flagship', '👑'),
('Budget', 'budget', '💰'),
('Mid-Range', 'mid-range', '⭐'),
('Foldable', 'foldable', '🔄'),
('Gaming', 'gaming', '🎮');

-- Admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@phonestore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO products (category_id, name, brand, model, price, original_price, stock, description, specs, image_url, is_featured) VALUES
(1, 'iPhone 15 Pro Max', 'Apple', 'A17 Pro', 134900, 149900, 25,
 'The most powerful iPhone ever with titanium design and A17 Pro chip.',
 '{"display":"6.7\" Super Retina XDR","camera":"48MP + 12MP + 12MP","battery":"4422mAh","ram":"8GB","storage":"256GB","os":"iOS 17"}',
 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=400', 1),

(1, 'Samsung Galaxy S24 Ultra', 'Samsung', 'S24 Ultra', 129999, 139999, 18,
 'Ultimate Android flagship with built-in S Pen and 200MP camera.',
 '{"display":"6.8\" Dynamic AMOLED 2X","camera":"200MP + 12MP + 10MP + 10MP","battery":"5000mAh","ram":"12GB","storage":"256GB","os":"Android 14"}',
 'https://images.unsplash.com/photo-1708963178695-7d87eeeae7b4?w=400', 1),

(1, 'Google Pixel 8 Pro', 'Google', 'Tensor G3', 89999, 99999, 30,
 'Pure Android experience with Google AI and the best camera system.',
 '{"display":"6.7\" LTPO OLED","camera":"50MP + 48MP + 48MP","battery":"5050mAh","ram":"12GB","storage":"128GB","os":"Android 14"}',
 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=400', 1),

(4, 'Samsung Galaxy Z Fold 5', 'Samsung', 'Z Fold 5', 154999, 164999, 10,
 'The pinnacle of foldable technology with a stunning book-style design.',
 '{"display":"7.6\" Foldable Dynamic AMOLED","camera":"50MP + 10MP + 10MP","battery":"4400mAh","ram":"12GB","storage":"256GB","os":"Android 13"}',
 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=400', 1),

(3, 'OnePlus 12', 'OnePlus', '12', 64999, 69999, 40,
 'Blazing fast performance with Snapdragon 8 Gen 3 and 100W charging.',
 '{"display":"6.82\" LTPO AMOLED","camera":"50MP + 48MP + 64MP","battery":"5400mAh","ram":"12GB","storage":"256GB","os":"Android 14"}',
 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400', 0),

(2, 'Redmi Note 13 Pro', 'Xiaomi', 'Note 13 Pro', 24999, 27999, 80,
 'Best-in-class camera and 200MP photography at an unbeatable price.',
 '{"display":"6.67\" AMOLED","camera":"200MP + 8MP + 2MP","battery":"5100mAh","ram":"8GB","storage":"128GB","os":"Android 13"}',
 'https://images.unsplash.com/photo-1565849904461-04a58ad377e0?w=400', 0),

(5, 'ASUS ROG Phone 8', 'ASUS', 'ROG Phone 8', 79999, 84999, 15,
 'Ultimate gaming smartphone with 165Hz display and AirTriggers.',
 '{"display":"6.78\" AMOLED 165Hz","camera":"50MP + 13MP + 32MP","battery":"5500mAh","ram":"16GB","storage":"256GB","os":"Android 14"}',
 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=400', 1),

(2, 'Motorola Moto G84', 'Motorola', 'Moto G84', 17999, 19999, 60,
 'Crisp pOLED display and clean Android experience at budget price.',
 '{"display":"6.55\" pOLED","camera":"50MP + 8MP","battery":"5000mAh","ram":"12GB","storage":"256GB","os":"Android 13"}',
 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=400', 0),

(3, 'Nothing Phone (2)', 'Nothing', 'Phone 2', 44999, 49999, 35,
 'Transparent design with Glyph Interface and clean NothingOS.',
 '{"display":"6.7\" LTPO OLED","camera":"50MP + 50MP","battery":"4700mAh","ram":"12GB","storage":"256GB","os":"Android 13"}',
 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400', 0),

(1, 'Sony Xperia 1 V', 'Sony', 'Xperia 1 V', 109990, 119990, 12,
 'Pro-grade camera with 4K OLED display for content creators.',
 '{"display":"6.5\" 4K OLED 120Hz","camera":"52MP + 12MP + 12MP","battery":"5000mAh","ram":"12GB","storage":"256GB","os":"Android 13"}',
 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=400', 0);
