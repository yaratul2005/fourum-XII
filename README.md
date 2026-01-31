# Furom - Futuristic Forum Platform

A modern, futuristic forum platform inspired by Reddit and Quora with unique experience points (EXP) system instead of traditional karma.

## Features

### 🚀 Core Features
- **Modern Design**: Cyberpunk/futuristic aesthetic with neon colors and smooth animations
- **Experience System**: Earn EXP for posting, commenting, and receiving upvotes instead of karma
- **User Authentication**: Secure registration, login, and email verification
- **Post Creation**: Rich post creation with categories and preview functionality
- **Voting System**: Upvote/downvote posts and comments with real-time updates
- **Comment System**: Nested comments with voting and user mentions
- **Responsive Design**: Fully responsive layout that works on all devices

### 🎮 Gamification Elements
- **EXP Points**: Earn experience points for various activities:
  - Creating posts: +10 EXP
  - Commenting: +5 EXP  
  - Receiving upvotes: +2 EXP
  - Receiving downvotes: -1 EXP
- **User Levels**: Progress through levels based on EXP:
  - Newbie (0+ EXP)
  - Beginner (100+ EXP)
  - Member (500+ EXP)
  - Active Member (1000+ EXP)
  - Veteran (2500+ EXP)
  - Expert (5000+ EXP)
  - Master (10000+ EXP)
  - Legend (25000+ EXP)

### 🔧 Technical Features
- **AJAX Interactions**: Smooth voting and commenting without page refresh
- **Auto-save**: Drafts automatically saved while typing
- **Real-time Validation**: Client-side form validation with instant feedback
- **Security**: CSRF protection, input sanitization, and secure password hashing
- **Performance**: Optimized database queries and caching considerations

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server

### Setup Instructions

1. **Clone or Download**
   ```bash
   git clone https://github.com/yourusername/furom.git
   ```

2. **Database Setup**
   ```sql
   CREATE DATABASE furom_db;
   USE furom_db;
   SOURCE database/schema.sql;
   ```

3. **Configuration**
   Edit `config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'furom_db');
   
   // Update your domain
   define('SITE_URL', 'https://yourdomain.com');
   ```

4. **File Permissions**
   ```bash
   chmod 755 assets/
   chmod 644 assets/css/style.css
   chmod 644 assets/js/main.js
   ```

5. **Email Configuration**
   Update email settings in `config.php` for verification emails:
   ```php
   define('SMTP_HOST', 'mail.yourdomain.com');
   define('SMTP_USERNAME', 'noreply@yourdomain.com');
   ```

## Project Structure

```
FUROM/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── main.js            # Client-side JavaScript
├── ajax/
│   ├── vote.php               # Voting AJAX handler
│   └── create-comment.php     # Comment creation handler
├── database/
│   └── schema.sql             # Database schema
├── includes/
│   └── functions.php          # Utility functions
├── config.php                 # Configuration file
├── index.php                  # Main page
├── register.php               # User registration
├── login.php                  # User login
├── logout.php                 # Logout handler
├── verify.php                 # Email verification
├── create-post.php            # Post creation
├── post.php                   # Individual post view
└── README.md                  # This file
```

## Customization

### Colors and Themes
Modify the CSS variables in `assets/css/style.css`:
```css
:root {
    --primary: #00f5ff;        /* Main accent color */
    --secondary: #ff00ff;      /* Secondary accent */
    --dark-bg: #0a0a1a;        /* Background color */
    --card-bg: #121225;        /* Card background */
}
```

### Categories
Edit categories in the database or modify `database/schema.sql`:
```sql
INSERT INTO `categories` (`name`, `description`, `color`, `icon`) VALUES
('technology', 'Tech discussions', '#ff6b6b', 'cpu');
```

### EXP Values
Adjust EXP rewards in `config.php`:
```php
define('EXP_POST', 10);
define('EXP_COMMENT', 5);
define('EXP_UPVOTE', 2);
```

## Deployment

### Hosting Requirements
- PHP hosting with MySQL support
- Ability to send emails (for verification)
- SSL certificate recommended

### GitHub Integration
1. Push your customized version to GitHub:
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/yourusername/furom.git
   git push -u origin main
   ```

2. Pull updates to your hosting:
   ```bash
   git pull origin main
   ```

## API Endpoints

### AJAX Handlers
- `ajax/vote.php` - Handle post/comment voting
- `ajax/create-comment.php` - Create new comments

### Response Format
All AJAX responses follow this format:
```json
{
    "success": true,
    "message": "Success message",
    "data": {}
}
```

## Security Features

- Password hashing with bcrypt
- CSRF token protection
- Input sanitization
- SQL injection prevention
- Session management
- Email verification

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support, email admin@yourdomain.com or create an issue on GitHub.

## Roadmap

### Planned Features
- [ ] Private messaging system
- [ ] User profiles with customization
- [ ] Advanced search functionality
- [ ] Mobile app development
- [ ] Moderation tools
- [ ] Analytics dashboard
- [ ] API for third-party integrations
- [ ] Dark/light theme toggle
- [ ] Notification system
- [ ] Bookmark and save posts

---

Built with ❤️ for the community | [Furom](https://great10.xyz)