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
    price_bhs DECIMAL(15, 2) NOT NULL,
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

-- Insert sample properties with new Dubai rental properties
INSERT INTO properties (title, description, location, country, property_type, bedrooms, bathrooms, square_feet, price_bhs, listing_type, featured, images) VALUES 
('Modern Waterfront Villa', 'Stunning waterfront villa with pool and modern amenities. This exceptional property features panoramic water views, a private infinity pool, and state-of-the-art smart home technology. The open-concept design seamlessly blends indoor and outdoor living with floor-to-ceiling windows throughout. Premium finishes include marble countertops, hardwood floors, and custom millwork. The gourmet kitchen boasts professional-grade appliances and a large island perfect for entertaining.', 'Austin, Texas', 'US', 'villa', 4, 3, 2500, 835404.00, 'buy', TRUE, '["https://images.unsplash.com/photo-1600596542815-ffad4c1539a9"]'),
('Luxury Mediterranean Villa', 'Elegant Mediterranean-style villa in prime location. This magnificent estate showcases timeless architecture with modern luxury amenities. Features include a grand foyer with soaring ceilings, formal dining room, and spacious family room with fireplace. The master suite offers a private balcony, walk-in closet, and spa-like bathroom. Outdoor amenities include a resort-style pool, outdoor kitchen, and beautifully landscaped gardens.', 'Dubai, UAE', 'Dubai', 'villa', 5, 4, 3200, 1253106.00, 'buy', TRUE, '["https://images.unsplash.com/photo-1600585154340-be6161a56a0c"]'),
('Contemporary Beachfront House', 'Modern beachfront property with stunning ocean views. This architectural masterpiece offers unobstructed ocean views from every room. The contemporary design features clean lines, expansive glass walls, and premium materials throughout. Highlights include a rooftop terrace, private beach access, and a wine cellar. The open-plan living area flows seamlessly to outdoor terraces perfect for al fresco dining and entertaining.', 'Dubai, UAE', 'Dubai', 'house', 4, 3, 2800, 1002485.00, 'buy', FALSE, '["https://images.unsplash.com/photo-1600607687644-c7171b42498b"]'),
('Minimalist Glass Villa', 'Sleek minimalist design with floor-to-ceiling windows. This ultra-modern villa epitomizes contemporary luxury with its clean geometric lines and abundant natural light. The innovative design features disappearing glass walls that create seamless indoor-outdoor living. Premium amenities include a floating staircase, integrated sound system, and automated lighting. The minimalist aesthetic is complemented by high-end finishes and custom-designed furniture.', 'Austin, Texas', 'US', 'villa', 3, 2, 2100, 668323.00, 'buy', FALSE, '["https://images.unsplash.com/photo-1600607687920-4e2a09cf159d"]'),

-- Dubai Rental Properties
('Luxury Dubai Apartment - Living Room', 'Modern luxury apartment with stunning city views and premium furnishings', 'Dubai, UAE', 'Dubai', 'apartment', 2, 2, 1200, 8500.00, 'rent', TRUE, '["IMG-20250615-WA0006.jpg"]'),
('Dubai Luxury Bathroom Suite', 'Premium bathroom with marble finishes and modern fixtures', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 800, 5500.00, 'rent', FALSE, '["IMG-20250615-WA0007.jpg"]'),
('Modern Dubai Kitchen', 'Contemporary kitchen with marble island and premium appliances', 'Dubai, UAE', 'Dubai', 'apartment', 3, 2, 1500, 12000.00, 'rent', TRUE, '["IMG-20250615-WA0008.jpg"]'),
('Dubai Master Bedroom', 'Elegant master bedroom with city views and luxury furnishings', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 600, 4500.00, 'rent', FALSE, '["IMG-20250615-WA0009.jpg"]'),
('Dubai Walk-in Closet', 'Spacious walk-in closet with custom storage solutions', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 400, 3000.00, 'rent', FALSE, '["IMG-20250615-WA0010.jpg"]'),
('Dubai Luxury Bedroom Suite', 'Premium bedroom with panoramic city views', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 700, 5000.00, 'rent', FALSE, '["IMG-20250615-WA0011.jpg"]'),
('Dubai Fitness Center', 'State-of-the-art fitness facility with city views', 'Dubai, UAE', 'Dubai', 'apartment', 0, 0, 2000, 15000.00, 'rent', TRUE, '["IMG-20250615-WA0012.jpg"]'),
('Dubai Guest Bathroom', 'Elegant guest bathroom with premium finishes', 'Dubai, UAE', 'Dubai', 'apartment', 0, 1, 300, 2500.00, 'rent', FALSE, '["IMG-20250615-WA0014.jpg"]'),
('Dubai Penthouse Bedroom', 'Luxurious penthouse bedroom with floor-to-ceiling windows', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 800, 6500.00, 'rent', TRUE, '["IMG-20250615-WA0015.jpg"]'),
('Dubai Dining Area', 'Sophisticated dining area with marble table and city views', 'Dubai, UAE', 'Dubai', 'apartment', 2, 1, 900, 7500.00, 'rent', FALSE, '["IMG-20250615-WA0016.jpg"]'),
('Dubai Living Space', 'Contemporary living space with premium furnishings', 'Dubai, UAE', 'Dubai', 'apartment', 2, 2, 1100, 9000.00, 'rent', TRUE, '["IMG-20250615-WA0017.jpg"]'),
('Dubai Modern Kitchen 2', 'Sleek kitchen design with integrated appliances', 'Dubai, UAE', 'Dubai', 'apartment', 2, 1, 800, 6000.00, 'rent', FALSE, '["IMG-20250615-WA0018.jpg"]'),
('Dubai Luxury Living Room', 'Spacious living room with modern chandelier and city views', 'Dubai, UAE', 'Dubai', 'apartment', 3, 2, 1400, 11000.00, 'rent', TRUE, '["IMG-20250615-WA0019.jpg"]'),
('Dubai Premium Bedroom', 'Premium bedroom with custom built-ins and city views', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 650, 4800.00, 'rent', FALSE, '["IMG-20250615-WA0020.jpg"]'),
('Dubai Master Suite', 'Luxurious master suite with walk-in closet', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 900, 7000.00, 'rent', TRUE, '["IMG-20250615-WA0021.jpg"]'),
('Dubai Compact Kitchen', 'Efficient kitchen design with premium finishes', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 500, 3500.00, 'rent', FALSE, '["IMG-20250615-WA0023.jpg"]'),
('Dubai Executive Bedroom', 'Executive bedroom with built-in storage and city views', 'Dubai, UAE', 'Dubai', 'apartment', 1, 1, 750, 5500.00, 'rent', FALSE, '["IMG-20250615-WA0024.jpg"]');
