# NetSpace Dev - Software Development Company Platform

Modern multi-page company website built with **Astro**, powered by a **PHP 8.4 REST API & MySQL** backend, and styled after the minimalist, ultra-clean aesthetic of **astro-narrow**. Includes an Admin Console secured by a quick **4-digit PIN**.

---

## 🌟 Architecture & Features

- **Frontend (Astro v7):**
  - **`astro-narrow` Design System:** Centered narrow container (`54rem`), glassmorphism floating header (`nav-shell`), sleek typography, dark/light mode toggle with persistence.
  - **Multi-Page Website:**
    - `Home` (`/`): Company hero, tech stack badges, services preview, featured projects, latest insights, and CTA banner.
    - `About` (`/about`): Engineering philosophy, values, core competencies.
    - `Services` (`/services`): Custom Web, Mobile (iOS/Android), Cloud & DevOps, Database architecture.
    - `Projects` (`/projects`): Filterable portfolio with live search, tags, client info, and demo links.
    - `Blog` (`/blog`): Tech articles with expandable full text, reading time, and categories.
    - `Contact` (`/contact`): Interactive AJAX inquiry form connected to MySQL.

- **Backend & Admin Console (PHP + MySQL):**
  - **4-Digit PIN Authentication:** Instant access with 4-digit PIN (Default: `1234`). Auto-submitting keypad & touch buttons.
  - **Projects Manager (`/admin/projects.php`):** Add, edit, delete projects with image uploads and featured showcase toggle.
  - **Blog Manager (`/admin/blogs.php`):** Write, edit, delete articles with cover image upload, tags, and status (Published/Draft).
  - **Client Inquiries (`/admin/messages.php`):** View inquiries sent from the website contact form, mark read/unread, delete.
  - **PIN Settings (`/admin/settings.php`):** Easily change the 4-digit security PIN anytime.

---

## 🚀 Local Development (XAMPP)

### 1. Database
- **Database:** `netspace` (in XAMPP MySQL).
- Schema and sample data are in `database/schema.sql`.

### 2. URLs
- **Astro Frontend:** [http://localhost:4321/](http://localhost:4321/)
- **PHP Admin Console:** [http://localhost/netspacedev/admin/login.php](http://localhost/netspacedev/admin/login.php)
  - **Default PIN:** `1234`
- **REST APIs:**
  - Projects: `http://localhost/netspacedev/api/projects.php`
  - Blogs: `http://localhost/netspacedev/api/blogs.php`
  - Contact Form: `http://localhost/netspacedev/api/contact.php`

---

## 📦 How to Push to Git (GitHub / GitLab)

1. Create a new repository on GitHub (e.g. `netspacedev`).
2. Run in terminal:
```bash
git remote add origin https://github.com/YOUR_USERNAME/netspacedev.git
git push -u origin main
```

---

## 🌐 How to Deploy to Hostinger

### Step 1: Export & Import Database
1. In your local XAMPP phpMyAdmin, export the `netspace` database (or use `database/schema.sql`).
2. Log in to **Hostinger hPanel** ➔ **Databases** ➔ **MySQL Databases**.
3. Create a new database (e.g., `u123456_netspace`) and user with a strong password.
4. Open **phpMyAdmin** on Hostinger and import `database/schema.sql`.

### Step 2: Build the Astro Static Website
In your local project directory, run:
```bash
npm run build
```
This generates production files in the `dist/` directory.

### Step 3: Upload to Hostinger `public_html`
Upload the following into Hostinger's **`public_html/`**:
- Contents of the `dist/` folder (HTML, CSS, JS)
- `admin/` folder (PHP Admin Console)
- `api/` folder (PHP REST APIs)
- `public/uploads/` folder (Uploaded images)
- `.htaccess` (Apache routing rules)

### Step 4: Configure Database on Hostinger
Edit `api/config/db.php` (or set environment variables in Hostinger hPanel):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_netspace');
define('DB_USER', 'u123456_user');
define('DB_PASS', 'your_password_here');
```

Your website is now live on Hostinger with full dynamic PHP/MySQL Admin management!
