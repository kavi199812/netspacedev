-- NetSpace Dev Database Schema
-- Database: netspace

CREATE DATABASE IF NOT EXISTS `netspace` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `netspace`;

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `pin_code` VARCHAR(10) NOT NULL DEFAULT '1234',
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Projects Table
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

-- 3. Blogs Table
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

-- 4. Messages / Contact Inquiries Table
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(200) DEFAULT 'General Inquiry',
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin User (username: admin | password: password123)
-- Password hash generated with password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`username`, `password_hash`, `email`, `name`) 
VALUES ('admin', '$2y$10$wO0oH6N9Y4h7qV4p1QY4sOcmmYI6R0rTq4uV8M2a2YtM.3.nQd71y', 'admin@netspacedev.com', 'NetSpace Administrator')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Seed Sample Projects
INSERT INTO `projects` (`title`, `slug`, `summary`, `description`, `category`, `technologies`, `client_name`, `image_url`, `live_url`, `github_url`, `is_featured`) VALUES
(
    'Cloud-Native FinTech Platform',
    'cloud-native-fintech-platform',
    'High-throughput distributed banking and payment microservices gateway.',
    'Engineered a scalable transaction processing engine serving 50k+ transactions per minute. Integrated real-time fraud detection and multi-currency ledger management compliant with PCI-DSS standards.',
    'Enterprise Software',
    'Go, Node.js, PostgreSQL, Docker, AWS',
    'Apex Financials',
    'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80',
    'https://example.com/demo/fintech',
    '',
    1
),
(
    'NextGen Logistics & Dispatch System',
    'nextgen-logistics-dispatch-system',
    'Real-time automated freight routing, tracking, and vehicle telemetry suite.',
    'Architected an end-to-end logistics platform optimizing driver delivery routes by 28%. Features live GPS WebSockets tracking, dynamic automated billing, and offline-first mobile companion app.',
    'Web & Mobile App',
    'React, React Native, Node.js, Redis, MongoDB',
    'SwiftHaul Global',
    'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=800&q=80',
    'https://example.com/demo/logistics',
    '',
    1
),
(
    'AI-Powered Medical Diagnostic Portal',
    'ai-medical-diagnostic-portal',
    'Clinical decision support system processing radiographic imaging with computer vision.',
    'Developed a secure HIPAA-compliant portal enabling radiologists to analyze DICOM MRI and CT scans using deep learning inference models, cutting diagnostic turnaround time by 40%.',
    'AI & Healthcare',
    'Python, FastAPI, Astro, PyTorch, MySQL',
    'BioHealth Labs',
    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=80',
    'https://example.com/demo/medical',
    '',
    1
),
(
    'B2B SaaS Inventory & Supply Chain Engine',
    'b2b-saas-inventory-engine',
    'Multi-tenant cloud inventory hub with predictive replenishment forecasting.',
    'A high-performance inventory synchronization portal for multi-warehouse retail distributors, supporting EDI integration, barcode scanner streams, and automated PO generation.',
    'Cloud Software',
    'Vue.js, PHP, MySQL, Redis, TailwindCSS',
    'OmniGoods Retail',
    'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80',
    'https://example.com/demo/inventory',
    '',
    0
);

-- Seed Sample Blogs
INSERT INTO `blogs` (`title`, `slug`, `excerpt`, `content`, `cover_image`, `author`, `category`, `tags`, `status`, `read_time`) VALUES
(
    'Why We Choose Astro for Content-Driven Software Platforms in 2026',
    'why-we-choose-astro-in-2026',
    'Discover how Astro Islands architecture delivers instant page loads, zero client-side JavaScript bloat, and superior SEO benchmarks.',
    'In modern web development, shipping megabytes of unused JavaScript hurts conversion rates and search rankings. Astro revolutionized static and dynamic web rendering with its "Islands Architecture". In this deep dive, our engineering team breaks down why static-first architectures paired with lightweight API microservices outperform traditional heavy Single Page Applications for modern businesses.',
    'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=800&q=80',
    'Kasun Perera',
    'Web Architecture',
    'Astro, Performance, Architecture, Jamstack',
    'published',
    '5 min read'
),
(
    'Building Secure, Scalable REST APIs with Modern PHP 8.4 and MySQL',
    'building-secure-rest-apis-php-mysql',
    'A practical guide to modern PHP practices, PDO prepared statements, JWT/Session authentication, and clean API design.',
    'PHP remains one of the most widely deployed and robust server-side technologies on the web. With PHP 8.4, features like JIT compilation, property hooks, type safety, and efficient memory utilization make it an incredible powerhouse for lightweight RESTful APIs and admin backends. We examine connection pooling, indexing strategies in MySQL, and defense against SQL injection.',
    'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
    'NetSpace Tech Team',
    'Backend Engineering',
    'PHP, MySQL, REST API, Security',
    'published',
    '6 min read'
),
(
    'Zero-Downtime Deployment Strategies on Shared & Cloud Hosting',
    'zero-downtime-deployment-strategies',
    'How we automate Git hooks, environment isolation, and asset caching for smooth production rollouts.',
    'Deploying production code should never cause 502 bad gateway errors or broken user sessions. Learn how to structure release directories, symlink document roots, handle database migrations gracefully, and use GitHub Actions to deploy seamlessly to Hostinger and cloud instances.',
    'https://images.unsplash.com/photo-1618401471353-b98afee0b2eb?auto=format&fit=crop&w=800&q=80',
    'DevOps Team',
    'DevOps & Cloud',
    'DevOps, Hostinger, Git, CI/CD',
    'published',
    '4 min read'
);
