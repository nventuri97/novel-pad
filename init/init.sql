CREATE DATABASE IF NOT EXISTS `authentication_db`;
CREATE DATABASE IF NOT EXISTS `novels_db`;
GRANT ALL ON `authentication_db`.* TO 'admin'@'%';
GRANT ALL ON `novels_db`.* TO 'admin'@'%';

USE `authentication_db`;
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `verification_token` VARCHAR(255) UNIQUE,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

USE `novels_db`;

-- Application related users information
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `user_id` INT PRIMARY KEY,                  -- Link with `authentication_db.users`
    `is_premium` BOOLEAN DEFAULT FALSE,
    `email` VARCHAR(100),
    `full_name` VARCHAR(100),
    `logged_in` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`user_id`) REFERENCES `authentication_db`.`users`(`id`) ON DELETE CASCADE
);

-- Novels information
CREATE TABLE IF NOT EXISTS `novels` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `genre` VARCHAR(100),
    `type` ENUM('short_story', 'full_novel') NOT NULL,
    `file_path` VARCHAR(255),
    `is_premium` BOOLEAN DEFAULT FALSE,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `user_id` INT,
    FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`user_id`) ON DELETE CASCADE
);
