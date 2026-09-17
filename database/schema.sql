CREATE TABLE IF NOT EXISTS drinks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS milk_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number INT UNSIGNED NOT NULL UNIQUE,
    status ENUM('ordered','making','done') NOT NULL DEFAULT 'ordered',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(status),
    INDEX(created_at)
);

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    drink_id INT UNSIGNED NULL,
    drink_name VARCHAR(100) NOT NULL,
    milk_type VARCHAR(100) NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO milk_types (name, sort_order)
SELECT 'Whole Milk', 1
WHERE NOT EXISTS (SELECT 1 FROM milk_types WHERE name='Whole Milk');

INSERT INTO milk_types (name, sort_order)
SELECT '2% Milk', 2
WHERE NOT EXISTS (SELECT 1 FROM milk_types WHERE name='2% Milk');

INSERT INTO milk_types (name, sort_order)
SELECT 'Oat Milk', 3
WHERE NOT EXISTS (SELECT 1 FROM milk_types WHERE name='Oat Milk');

INSERT INTO milk_types (name, sort_order)
SELECT 'Almond Milk', 4
WHERE NOT EXISTS (SELECT 1 FROM milk_types WHERE name='Almond Milk');

INSERT INTO drinks (name, description, price, sort_order)
SELECT 'Latte', 'Espresso with steamed milk and a smooth, creamy finish.', 4.50, 1
WHERE NOT EXISTS (SELECT 1 FROM drinks WHERE name='Latte');

INSERT INTO drinks (name, description, price, sort_order)
SELECT 'Cappuccino', 'Rich espresso topped with steamed milk and foam.', 4.50, 2
WHERE NOT EXISTS (SELECT 1 FROM drinks WHERE name='Cappuccino');

INSERT INTO drinks (name, description, price, sort_order)
SELECT 'Mocha', 'Espresso, chocolate and steamed milk for a classic mocha.', 5.00, 3
WHERE NOT EXISTS (SELECT 1 FROM drinks WHERE name='Mocha');

-- Default password: admin123
INSERT INTO admins (username, password_hash)
SELECT 'admin', '$2y$12$ELAyO4ZGqrAbZqZREH9MGOZ3cyg4vmG3jHNKeWLAINLgDz5uAuPe6'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username='admin');
