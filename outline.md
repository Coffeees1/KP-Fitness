# KP Fitness Website Project Outline

## Project Overview
A comprehensive fitness class reservation system with three-tier user authentication (Admin, Trainer, Client) built with PHP, MySQL, and responsive design.

## File Structure
```
/mnt/okcomputer/output/
├── index.php                 # Main landing page
├── about.php                 # About Us page
├── login.php                 # Unified login page
├── register.php              # Client registration page
├── dashboard.php             # Main dashboard (redirects based on role)
├── admin/                    # Admin dashboard and management
│   ├── dashboard.php         # Admin main dashboard
│   ├── users.php            # User management
│   ├── classes.php          # Class management
│   ├── reports.php          # Reports & Analytics
│   └── generate_report.php  # PDF report generation
├── client/                   # Client dashboard and features
│   ├── dashboard.php         # Client main dashboard
│   ├── membership.php       # Membership management
│   ├── booking.php          # Class booking
│   ├── workout_planner.php  # AI workout planner
│   └── payment.php          # Payment processing
├── trainer/                  # Trainer dashboard
│   ├── dashboard.php         # Trainer main dashboard
│   ├── schedule.php         # Schedule management
│   ├── attendance.php       # Attendance marking
│   └── classes.php          # Assigned classes
├── includes/                 # Core PHP files
│   ├── config.php           # Database configuration
│   ├── auth.php             # Authentication functions
│   ├── functions.php        # General functions
│   └── header.php           # Common header
├── assets/                   # Static assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Images and media
├── database/                 # Database files
│   ├── schema.sql           # Database schema
│   └── sample_data.sql      # Sample data
└── README.md                # Project documentation
```

## Key Features

### 1. User Authentication System
- Unified login page for all user types
- Role-based access control (Admin, Trainer, Client)
- Secure password hashing
- Session management

### 2. Registration System
- Client registration with comprehensive form
- Profile picture upload
- Email validation
- Terms and conditions agreement

### 3. Admin Dashboard
- User management (CRUD for trainers)
- Class management (CRUD for classes)
- Membership pricing management
- Reports & Analytics with filters
- PDF report generation
- Revenue and member growth tracking

### 4. Client Dashboard
- Membership management (purchase, renew, change)
- Payment history and Touch & Go QR
- Class booking with filters
- AI workout planner
- Profile management

### 5. Trainer Dashboard
- Schedule management
- Attendance marking
- Class assignments
- Performance metrics
- Recent bookings

### 6. Database Schema
- User management tables
- Class and session management
- Payment tracking
- Reservation system
- Membership management

### 7. Design Features
- Dark theme with orange accents
- Responsive design
- Background images
- Modern UI/UX
- Interactive elements

## Technical Stack
- Backend: PHP 8.x
- Database: MySQL 8.x
- Frontend: HTML5, CSS3, JavaScript
- Styling: Custom CSS with dark theme
- Server: XAMPP/Apache
- Charts: Chart.js for analytics
- PDF: TCPDF for report generation

## Database Tables
1. **User** - User accounts and profiles
2. **Trainer** - Trainer information and specialties
3. **Membership** - Membership types and pricing
4. **Payment** - Payment tracking and history
5. **Session** - Class sessions and scheduling
6. **Reservation** - Class bookings
7. **Class** - Class types and descriptions