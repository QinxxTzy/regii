-- ============================================
-- GameVault Database Setup
-- ============================================

CREATE DATABASE IF NOT EXISTS game_topup CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE game_topup;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(32)  NOT NULL COMMENT 'MD5 hash',
    full_name   VARCHAR(100),
    phone       VARCHAR(20),
    balance     DECIMAL(15,2) DEFAULT 0.00,
    avatar      VARCHAR(255) DEFAULT NULL,
    role        ENUM('user','admin') DEFAULT 'user',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert sample users (password = MD5 dari "password123")
INSERT INTO users (username, email, password, full_name, balance) VALUES
('admin',      'admin@gamevault.id',   MD5('admin123'),    'Administrator',    1000000.00),
('gamer01',    'gamer01@gmail.com',    MD5('password123'), 'Budi Santoso',     250000.00),
('player99',   'player99@gmail.com',   MD5('password123'), 'Rina Kusuma',      150000.00);

-- Tabel Transaksi Top Up
CREATE TABLE IF NOT EXISTS transactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    game_name       VARCHAR(100) NOT NULL,
    item_name       VARCHAR(100) NOT NULL,
    amount          DECIMAL(15,2) NOT NULL,
    status          ENUM('pending','success','failed') DEFAULT 'pending',
    payment_method  VARCHAR(50),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- CARA PENGGUNAAN:
-- 1. Import file ini via phpMyAdmin atau MySQL CLI:
--    mysql -u root -p < database.sql
--
-- 2. Kredensial login contoh:
--    Username: admin      | Password: admin123
--    Username: gamer01    | Password: password123
--    Username: player99   | Password: password123
--
-- 3. Pastikan konfigurasi di login.php sesuai:
--    $host = "localhost";
--    $dbname = "game_topup";
--    $username = "root";
--    $password = "";
-- ============================================
