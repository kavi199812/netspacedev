-- Migration: 001_initial_schema.sql
-- Description: Create initial core tables (admins, projects, blogs, messages) and seed initial data

CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `pin_code` VARCHAR(10) NOT NULL DEFAULT '1234',
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `summary` VARCHAR(500) NOT NULL,
    `description` LONGTEXT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'Web Development',
    `technologies` VARCHAR(255) NOT NULL DEFAULT 'React, Node.js, MySQL',
    `client_name` VARCHAR(100) DEFAULT 'Proprietary / Client',
    `image_url` VARCHAR(255) DEFAULT '/uploads/projects/default-project.jpg',
    `live_url` VARCHAR(255) DEFAULT '',
    `github_url` VARCHAR(255) DEFAULT '',
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blogs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `excerpt` VARCHAR(500) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `cover_image` VARCHAR(255) DEFAULT '/uploads/blogs/default-blog.jpg',
    `author` VARCHAR(100) DEFAULT 'NetSpace Engineering',
    `category` VARCHAR(100) DEFAULT 'Architecture',
    `tags` VARCHAR(255) DEFAULT 'Software, Cloud, Architecture',
    `status` ENUM('published', 'draft') DEFAULT 'published',
    `read_time` VARCHAR(50) DEFAULT '4 min read',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(200) DEFAULT 'General Inquiry',
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin User (admin / 1234)
INSERT INTO `admins` (`username`, `pin_code`, `password_hash`, `email`, `name`) 
VALUES ('admin', '1234', '$2y$10$wO0oH6N9Y4h7qV4p1QY4sOcmmYI6R0rTq4uV8M2a2YtM.3.nQd71y', 'admin@netspacedev.com', 'NetSpace Administrator')
ON DUPLICATE KEY UPDATE `username`=`username`;
