-- BlockHaus Database Setup
-- Create database and tables for the crypto real estate platform

CREATE DATABASE IF NOT EXISTS blockhaus_db;
USE blockhaus_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    interest_type ENUM('buying', 'selling', 'renting', 'investing') NOT NULL,
    role ENUM('user', 'admin', 'consultant') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Properties table
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255) NOT NULL,
    country ENUM('US', 'Dubai') NOT NULL,
    property_type ENUM('house', 'apartment', 'villa', 'condo') NOT NULL,
    bedrooms INT NOT NULL,
    bathrooms INT NOT NULL,
    square_feet INT NOT NULL,
    price_btc DECIMAL(10, 8) NOT NULL,
    price_usd DECIMAL(15, 2) NOT NULL,
    price_bhs DECIMAL(15, 2) DEFAULT NULL,
    listing_type ENUM('buy', 'rent', 'sell', 'invest') NOT NULL,
    status ENUM('available', 'sold', 'rented', 'pending') DEFAULT 'available',
    featured BOOLEAN DEFAULT FALSE,
    images JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Consultations table
CREATE TABLE IF NOT EXISTS consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    consultant_id INT DEFAULT NULL,
    property_id INT DEFAULT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    consultation_type ENUM('general', 'property_specific', 'investment') NOT NULL,
    scheduled_at DATETIME DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (consultant_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
);

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'file', 'system') DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Newsletter subscriptions table
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('active', 'unsubscribed') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Property searches table
CREATE TABLE IF NOT EXISTS property_searches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    search_type ENUM('buy', 'rent', 'sell', 'invest') NOT NULL,
    location VARCHAR(255),
    property_type VARCHAR(100),
    cryptocurrency_type VARCHAR(50),
    min_price DECIMAL(15, 2),
    max_price DECIMAL(15, 2),
    results_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Transactions table (for future crypto integration)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    transaction_type ENUM('purchase', 'rental', 'investment') NOT NULL,
    crypto_currency ENUM('BTC', 'ETH', 'BHS') NOT NULL,
    crypto_amount DECIMAL(18, 8) NOT NULL,
    usd_amount DECIMAL(15, 2) NOT NULL,
    transaction_hash VARCHAR(255),
    status ENUM('pending', 'confirmed', 'failed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (full_name, email, password, interest_type, role) VALUES 
('Admin User', 'admin@blockhaus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buying', 'admin');

-- Insert sample consultant
INSERT INTO users (full_name, email, password, interest_type, role) VALUES 
('John Consultant', 'consultant@blockhaus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buying', 'consultant');

-- Insert sample properties
INSERT INTO properties (title, description, location, country, property_type, bedrooms, bathrooms, square_feet, price_btc, price_usd, price_bhs, listing_type, featured, images) VALUES 
('Modern Waterfront Villa', 'Stunning waterfront villa with pool and modern amenities', 'Austin, Texas', 'US', 'villa', 4, 3, 2500, 10.0, 835404.00, 835404.00, 'buy', TRUE, '["https://images.unsplash.com/photo-1600596542815-ffad4c1539a9"]'),
('Luxury Mediterranean Villa', 'Elegant Mediterranean-style villa in prime location', 'Dubai, UAE', 'Dubai', 'villa', 5, 4, 3200, 15.0, 1253106.00, 1253106.00, 'buy', TRUE, '["https://images.unsplash.com/photo-1600585154340-be6161a56a0c"]'),
('Contemporary Beachfront House', 'Modern beachfront property with stunning ocean views', 'Dubai, UAE', 'Dubai', 'house', 4, 3, 2800, 12.0, 1002485.00, 1002485.00, 'buy', FALSE, '["https://images.unsplash.com/photo-1600607687644-c7171b42498b"]'),
('Minimalist Glass Villa', 'Sleek minimalist design with floor-to-ceiling windows', 'Austin, Texas', 'US', 'villa', 3, 2, 2100, 8.0, 668323.00, 668323.00, 'buy', FALSE, '["https://images.unsplash.com/photo-1600607687920-4e2a09cf159d"]');
