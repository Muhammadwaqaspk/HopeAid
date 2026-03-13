# Heal2Rise Social Welfare Management System

## Project Overview

**Heal2Rise ** is a web-based platform designed to support individuals facing depression, hopelessness, or family/marital issues. The system ensures complete confidentiality and privacy for every user while connecting them with suitable NGOs for counseling, monitoring, and skill development opportunities.

## Features

### For Users (Individuals Seeking Help)
- **Secure Registration & Privacy Protection** - Anonymous registration with complete data protection
- **NGO Connection & Case Assignment** - Auto-matching with relevant NGOs based on needs
- **Counseling & Continuous Support** - Online video/audio counseling sessions
- **Progress Tracking** - Monitor mental health and skill development progress
- **Skill Development** - Access vocational and life skills training programs
- **Rehabilitation Support** - Residential care for those who need intensive support

### For NGOs
- **Organization Management** - Manage profile, team members, and services
- **User Management** - View and manage assigned users
- **Session Scheduling** - Schedule and manage counseling sessions
- **Progress Reports** - Track and report user progress
- **Skill Programs** - Create and manage skill development programs
- **Donation Management** - Receive and track donations

### For Admins
- **Platform Oversight** - Monitor all users, NGOs, and activities
- **NGO Approval** - Review and approve NGO registrations
- **Reports & Analytics** - Generate platform statistics and reports
- **System Management** - Configure platform settings

## Technology Stack

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Custom styling with CSS variables
- **Bootstrap 5** - Responsive framework
- **Font Awesome** - Icons
- **Chart.js** - Data visualization

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Database
- **PDO** - Database abstraction

### Additional Integrations
- **Zoom/Google Meet API** - Video conferencing for counseling
- **Firebase** - Push notifications
- **Payment Gateways** - Easypaisa, JazzCash, PayPal, Bank Transfer

## Project Structure

```
heal2rise-/
├── index.html                  # Homepage
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   ├── js/                    # JavaScript files
│   └── images/                # Image assets
├── pages/
│   ├── login.html             # Login page
│   ├── register.html          # User registration
│   ├── ngo-register.html      # NGO registration
│   ├── user-dashboard.html    # User dashboard
│   ├── ngo-dashboard.html     # NGO dashboard
│   ├── admin-dashboard.html   # Admin dashboard
│   ├── ngos.html              # NGO listing
│   └── donate.html            # Donation page
├── api/
│   ├── login.php              # Login API
│   ├── register.php           # User registration API
│   ├── ngo-register.php       # NGO registration API
│   └── process-donation.php   # Donation processing API
├── includes/
│   └── config.php             # Configuration and database
├── database/
│   └── heal2rise_schema.sql   # Database schema
└── uploads/                   # File uploads directory
```

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional)

### Setup Steps

1. **Clone or download the project** to your web server directory

2. **Create the database**:
   ```bash
   mysql -u root -p
   CREATE DATABASE heal2rise_ CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import the database schema**:
   ```bash
   mysql -u root -p heal2rise_ < database/heal2rise_schema.sql
   ```

4. **Configure database connection**:
   Edit `includes/config.php` and update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'heal2rise_');
   ```

5. **Set up email configuration**:
   Update email settings in `includes/config.php` for notifications

6. **Configure payment gateways** (optional):
   Add your payment gateway credentials in `includes/config.php`

7. **Set file permissions**:
   ```bash
   chmod 755 uploads/
   chmod 644 includes/config.php
   ```

8. **Access the application**:
   Open `http://localhost/heal2rise-/` in your browser

## Default Credentials

### Admin
- **Username**: admin
- **Password**: password
- **Email**: admin@heal2rise.org

### Demo NGOs
- **Hope Foundation**: contact@hopefoundation.org / password
- **Care & Cure NGO**: info@carecure.org / password

## API Endpoints

### Authentication
- `POST /api/login.php` - User/NGO/Admin login
- `POST /api/register.php` - User registration
- `POST /api/ngo-register.php` - NGO registration

### Donations
- `POST /api/process-donation.php` - Process donations

## Security Features

- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- XSS protection with output encoding
- CSRF token validation (to be implemented)
- Session management
- Activity logging


## License

This project is developed for educational purposes

## Acknowledgments

- All partner NGOs supporting mental health initiatives
