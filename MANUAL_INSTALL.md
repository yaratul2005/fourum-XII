# Manual Installation Guide for Furom

## ⚠️ Current Issue
The automated installer is incorrectly setting the database host to "greatxyz" instead of "localhost".

## 🛠 Manual Installation Steps

### Step 1: Create Database via cPanel

1. Login to your cPanel at `https://great10.xyz/cpanel`
2. Find and click on **"MySQL Databases"**
3. **Create Database:**
   - Database Name: `furom_db` (or any name you prefer)
   - Note the **full database name** (it will show as `yourcpaaneluser_furom_db`)

4. **Create Database User:**
   - Username: `furom_user` (or any name)
   - Password: Generate a strong password
   - Note both username and password

5. **Add User to Database:**
   - Select your database and user
   - Grant **ALL PRIVILEGES**
   - Click **Add**

### Step 2: Create Configuration File Manually

Create a file named `config.php` in your main directory with this content:

```php
<?php
// Database Configuration - UPDATE THESE VALUES
define('DB_HOST', 'localhost');  // Keep this as localhost
define('DB_USER', 'YOUR_CPANEL_USERNAME_furom_user');  // Replace with actual username
define('DB_PASS', 'YOUR_STRONG_PASSWORD');  // Replace with actual password
define('DB_NAME', 'YOUR_CPANEL_USERNAME_furom_db');  // Replace with full database name

// Site Configuration
define('SITE_URL', 'https://great10.xyz');
define('SITE_NAME', 'Furom - Futuristic Forum');
define('ADMIN_EMAIL', 'admin@great10.xyz');

// Security Settings
define('SECRET_KEY', 'your_very_secure_secret_key_here_' . bin2hex(random_bytes(16)));
define('SESSION_TIMEOUT', 3600);

// Experience Points Configuration
define('EXP_POST', 10);
define('EXP_COMMENT', 5);
define('EXP_UPVOTE', 2);
define('EXP_DOWNVOTE', -1);

// Email Configuration
define('SMTP_HOST', 'mail.great10.xyz');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@great10.xyz');
define('SMTP_PASSWORD', 'your_email_password');

// Initialize session
session_start();

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Autoload functions
require_once 'includes/functions.php';
?>
```

### Step 3: Import Database Structure

1. In cPanel, go to **phpMyAdmin**
2. Select your database from the left panel
3. Click **Import** tab
4. Choose the file `database/schema.sql` from this package
5. Click **Go** to import

### Step 4: Create Admin User

Run this SQL in phpMyAdmin:

```sql
INSERT INTO users (username, email, password, verification_token, verified, exp, created_at) 
VALUES (
    'admin', 
    'admin@great10.xyz', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'verified_admin_token', 
    1, 
    1000, 
    NOW()
);
```

(Default password is "password" - change this immediately!)

### Step 5: Test Installation

Visit `https://great10.xyz/index.php` to see if everything works.

## 🔧 Finding Your Exact Database Details

In cPanel:
1. **MySQL Databases** section shows:
   - Database names (with prefixes)
   - Usernames (with prefixes)
   - Current connections

2. **Look for:**
   - Full database name (like `john123_furom_db`)
   - Full username (like `john123_furom_user`)

## 🆘 If Still Having Issues

1. **Check cPanel database section** for exact names
2. **Try phpMyAdmin first** to verify database exists
3. **Contact your hosting provider** for database connection details
4. **Use the db-test.php file** to test different connection parameters

## ✅ Verification Checklist

- [ ] Database created in cPanel
- [ ] Database user created and assigned
- [ ] config.php file uploaded with correct credentials
- [ ] Database schema imported successfully
- [ ] Admin user created
- [ ] Website loads without database errors

The key is getting the **exact database name and username** from your cPanel, as they include your cPanel account prefix.