# Furom Deployment Guide

## 🚀 Quick Deployment Checklist

### 1. Upload Files
Upload all files to your web hosting account via FTP/SFTP or file manager.

### 2. Run Installation
Navigate to `https://yourdomain.com/install.php` and follow the setup wizard:
- Check system requirements
- Configure database connection
- Import database schema
- Create admin user

### 3. Security Cleanup
After installation, delete the `install.php` file:
```bash
rm install.php
```

### 4. Configure Settings
Edit `config.php` to customize:
- Site name and URL
- Email settings
- EXP values
- Security keys

### 5. Test Everything
- Register a test account
- Create a post
- Leave a comment
- Test voting system

## 📁 Project Structure

```
FUROM/
├── assets/
│   ├── css/style.css          # Main stylesheet
│   └── js/main.js             # Client-side functionality
├── ajax/                      # AJAX handlers
│   ├── vote.php              # Voting system
│   ├── create-comment.php    # Comment creation
│   └── update-profile.php    # Profile updates
├── database/
│   └── schema.sql            # Database structure
├── includes/
│   └── functions.php         # Utility functions
├── config.php                # Configuration file
├── index.php                 # Homepage
├── register.php              # User registration
├── login.php                 # User login
├── logout.php                # Logout handler
├── verify.php                # Email verification
├── create-post.php           # Post creation
├── post.php                  # Individual post view
├── profile.php               # User profile
├── install.php               # Installation wizard
├── error.php                 # Error pages
├── .htaccess                 # Server configuration
├── README.md                 # Documentation
└── DEPLOYMENT.md             # This file
```

## ⚙️ Essential Configuration

### Database Settings
Update in `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'furom_db');
```

### Site Configuration
```php
define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'Your Forum Name');
define('ADMIN_EMAIL', 'admin@yourdomain.com');
```

### Email Settings
```php
define('SMTP_HOST', 'mail.yourdomain.com');
define('SMTP_USERNAME', 'noreply@yourdomain.com');
define('SMTP_PASSWORD', 'your_email_password');
```

## 🔒 Security Recommendations

1. **Delete install.php** after setup
2. **Use strong passwords** for admin accounts
3. **Enable HTTPS** with SSL certificate
4. **Regular backups** of database and files
5. **Keep PHP updated** to latest stable version
6. **Monitor error logs** regularly

## 🎨 Customization Options

### Colors
Modify CSS variables in `assets/css/style.css`:
```css
:root {
    --primary: #00f5ff;
    --secondary: #ff00ff;
    --dark-bg: #0a0a1a;
}
```

### Categories
Add/remove categories in database:
```sql
INSERT INTO categories (name, description, color, icon) 
VALUES ('new-category', 'Description', '#color', 'icon-name');
```

### EXP Values
Adjust in `config.php`:
```php
define('EXP_POST', 10);
define('EXP_COMMENT', 5);
define('EXP_UPVOTE', 2);
```

## 🆘 Troubleshooting

### Common Issues

**White Screen/500 Error:**
- Check PHP error logs
- Verify database connection
- Ensure file permissions are correct

**Database Connection Failed:**
- Verify database credentials
- Check if database exists
- Confirm MySQL service is running

**Email Not Sending:**
- Verify SMTP settings
- Check spam folder
- Test with different email provider

**CSS/JS Not Loading:**
- Check file paths
- Verify file permissions
- Clear browser cache

## 📊 Maintenance

### Regular Tasks
- Backup database weekly
- Update software versions
- Monitor disk space
- Review security logs
- Check user reports

### Performance Optimization
- Enable caching
- Optimize database queries
- Compress images
- Minify CSS/JS files
- Use CDN for static assets

## 🔄 Updates

To update your forum:
1. Backup current installation
2. Download latest version
3. Upload new files (keep config.php)
4. Run database migrations if needed
5. Test all functionality

## 💬 Support

For issues and questions:
- Check documentation first
- Review error logs
- Search community forums
- Contact support team

---

**Ready to launch your futuristic forum!** 🚀