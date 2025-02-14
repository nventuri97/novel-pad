CREATE DATABASE IF NOT EXISTS `admin_db`;
CREATE DATABASE IF NOT EXISTS `authentication_db`;
CREATE DATABASE IF NOT EXISTS `novels_db`;
GRANT ALL ON `admin_db`.* TO 'admin'@'%';
GRANT ALL ON `authentication_db`.* TO 'admin'@'%';
GRANT ALL ON `novels_db`.* TO 'admin'@'%';

USE `admin_db`;
-- Tabella per gli admin
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `password_hash` VARCHAR(255) NOT NULL,
    `password_expiry` TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserimento degli admin iniziali
INSERT INTO `admins` (`email`, `password_hash`) VALUES 
    ('francescobeno@gmail.com', '$2y$10$mJE3XpdyWlFg6rwMMcET5OiQu7IKEquQ2UP2U.9W4.GeDiH1wOKhG'),
    ('leomanne2000@gmail.com', '$2y$10$mJE3XpdyWlFg6rwMMcET5OiQu7IKEquQ2UP2U.9W4.GeDiH1wOKhG'),
    ('n.venturi97@gmail.com', '$2y$10$mJE3XpdyWlFg6rwMMcET5OiQu7IKEquQ2UP2U.9W4.GeDiH1wOKhG');

USE `authentication_db`;
-- Tabella utenti (authentication)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `verification_token` VARCHAR(255) UNIQUE,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `reset_token` VARCHAR(255) UNIQUE,
    `reset_token_expiry` TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

USE `novels_db`;

-- Informazioni utente (applicative)
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `user_id` INT PRIMARY KEY,                  -- Link con authentication_db.users
    `nickname` VARCHAR(50) NOT NULL UNIQUE,
    `is_premium` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`user_id`) REFERENCES `authentication_db`.`users`(`id`) ON DELETE CASCADE
);

-- Informazioni sui romanzi
CREATE TABLE IF NOT EXISTS `novels` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `genre` VARCHAR(100),
    `type` ENUM('short_story', 'full_novel') NOT NULL,
    `file_path` VARCHAR(255),
    `is_premium` BOOLEAN DEFAULT FALSE,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `user_id` INT,
    FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`user_id`) ON DELETE CASCADE
);
