-- Migration: 002_create_site_settings_table.sql
-- Description: Create site_settings table for dynamic company configurations and contact details

CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `description`) VALUES
('company_name', 'NetSpace Dev', 'Official company brand name'),
('company_email', 'contact@netspacedev.com', 'Primary contact email'),
('company_phone', '+94 77 123 4567', 'Official support contact number'),
('company_location', 'Colombo, Sri Lanka', 'Headquarters location'),
('github_url', 'https://github.com/kavi199812', 'Company or founder GitHub profile'),
('linkedin_url', 'https://linkedin.com', 'Company LinkedIn page')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;
