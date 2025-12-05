# KP Fitness - Class Reservation System

A comprehensive web-based fitness class reservation system built with PHP, MySQL, and modern web technologies. Features three-tier user authentication (Admin, Trainer, Client) with role-based access control.

## Features

### 🔐 Multi-Role Authentication System
- **Admin**: Full system control, user management, reports
- **Trainer**: Schedule management, attendance marking, class oversight
- **Client**: Class booking, membership management, workout planning

### 📱 Modern Responsive Design
- Dark theme with orange accents
- Mobile-friendly interface
- Smooth animations and transitions
- Professional UI/UX design

### 💪 Core Functionality
- **Class Booking System**: Real-time reservation with conflict prevention
- **Membership Management**: Multiple plans (Monthly, Yearly, One-Time)
- **Payment System**: Multiple payment options including Touch & Go QR
- **AI Workout Planner**: Personalized fitness plans based on user data
- **Attendance Tracking**: Digital attendance marking for trainers
- **Analytics & Reports**: Comprehensive business insights

### 🛠️ Technical Features
- Secure password hashing
- Session management
- Input validation and sanitization
- Database optimization with indexes
- Notification system
- File upload support for profile pictures

## Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Styling**: Custom CSS with CSS Grid and Flexbox
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Inter)
- **Charts**: Chart.js (for analytics)
- **Charts**: TCPDF (for PDF generation)

## File Structure

```
/
├── index.php              # Main landing page
├── login.php              # Unified login page
├── register.php           # Client registration
├── dashboard.php          # Role-based dashboard redirect
├── about.php              # About Us page
├── logout.php             # Logout script
├── admin/                 # Admin dashboard and management
│   ├── dashboard.php      # Admin main dashboard
│   ├── users.php          # User management
│   ├── classes.php        # Class management
│   ├── reports.php        # Reports & Analytics
│   └── generate_report.php # PDF report generation
├── client/                # Client dashboard and features
│   ├── dashboard.php      # Client main dashboard
│   ├── membership.php     # Membership management
│   ├── booking.php        # Class booking
│   ├── workout_planner.php # AI workout planner
│   └── payment.php        # Payment processing
├── trainer/               # Trainer dashboard
│   ├── dashboard.php      # Trainer main dashboard
│   ├── schedule.php       # Schedule management
│   ├── attendance.php     # Attendance marking
│   └── classes.php        # Assigned classes
├── includes/              # Core PHP files
│   ├── config.php         # Database configuration
│   ├── auth.php           # Authentication functions
│   ├── functions.php      # General functions
│   └── header.php         # Common header
├── database/              # Database files
│   ├── schema.sql         # Database schema
│   └── sample_data.sql    # Sample data
└── README.md              # This file
```

## Installation Guide

### Prerequisites
- XAMPP/WAMP/LAMP stack
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Step 1: Setup Development Environment

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install with default settings
   - Start Apache and MySQL services

2. **Clone or Download Project**
   - Extract files to `C:/xampp/htdocs/kp-fitness/` (Windows)
   - Or `/opt/lampp/htdocs/kp-fitness/` (Linux)

### Step 2: Database Setup

1. **Create Database**
   ```sql
   CREATE DATABASE kp_f;
   USE kp_f;
   ```

2. **Import Schema**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Select the `kp_f` database
   - Import `database/schema.sql`
   - Optionally import `database/sample_data.sql` for test data

3. **Database Configuration**
   - Update `includes/config.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'kp_f');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Default XAMPP password is empty
   ```

### Step 3: Test the Application

1. **Start XAMPP Services**
   - Apache Web Server
   - MySQL Database

2. **Access the Website**
   - Open browser and navigate to: `http://localhost/kp-fitness/`

3. **Demo Accounts**
   - **Admin**: admin@kpfitness.com / password
   - **Trainer**: Create through admin dashboard
   - **Client**: Register new account

## Database Schema

### Core Tables

#### Users Table
```sql
- UserID (Primary Key)
- FullName, Email, Phone
- Password (hashed), Role (admin/trainer/client)
- DateOfBirth, Height, Weight
- MembershipID, TrainerID (foreign keys)
- ProfilePicture, IsActive
```

#### Membership Table
```sql
- MembershipID (Primary Key)
- Type (monthly/yearly/onetime)
- Cost, Duration, Benefits
- IsActive
```

#### Classes Table
```sql
- ClassID (Primary Key)
- ClassName, Description
- Duration, MaxCapacity, DifficultyLevel
- IsActive
```

#### Sessions Table
```sql
- SessionID (Primary Key)
- SessionDate, Time, Room
- ClassID, TrainerID (foreign keys)
- CurrentBookings, Status
```

#### Reservations Table
```sql
- ReservationID (Primary Key)
- BookingDate, Status
- UserID, SessionID (foreign keys)
```

## Usage Guide

### For Administrators
1. **Dashboard Overview**: View system statistics and recent activity
2. **User Management**: Create/edit trainer accounts, manage client profiles
3. **Class Management**: Add/edit classes, assign trainers
4. **Reports**: Generate revenue, attendance, and performance reports

### For Trainers
1. **Schedule Management**: View assigned classes and schedules
2. **Attendance**: Mark attendance for class participants
3. **Performance**: View booking statistics and client feedback
4. **Profile**: Update personal information and specialties

### For Clients
1. **Dashboard**: View upcoming classes and health stats
2. **Class Booking**: Browse and book available classes
3. **Membership**: Purchase and manage membership plans
4. **AI Workout Planner**: Generate personalized workout plans
5. **Payments**: View payment history and manage billing

## Security Features

- **Password Security**: BCrypt hashing with salt
- **Session Management**: Secure session handling
- **Input Validation**: Server-side validation and sanitization
- **Role-Based Access**: Strict permission controls
- **SQL Injection Prevention**: Prepared statements
- **XSS Prevention**: Output encoding

## Performance Optimizations

- **Database Indexes**: Optimized queries with proper indexing
- **CSS Optimization**: Minimal external dependencies
- **Image Optimization**: Efficient image formats and sizes
- **Caching**: Browser caching for static assets
- **Minified Assets**: Compressed CSS and JavaScript

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL service is running
   - Verify database credentials in `config.php`
   - Ensure database exists and schema is imported

2. **Page Not Loading**
   - Check Apache service is running
   - Verify file permissions (755 for directories, 644 for files)
   - Check PHP error logs

3. **Login Issues**
   - Verify user exists in database
   - Check password hashing compatibility
   - Ensure sessions are properly configured

4. **Styling Not Applied**
   - Clear browser cache
   - Check CSS file paths
   - Verify file permissions

### Getting Help

If you encounter issues:

1. Check the browser console for JavaScript errors
2. Review PHP error logs
3. Verify database connectivity
4. Ensure all files are properly uploaded
5. Check file and directory permissions

## Future Enhancements

- Mobile app development
- Integration with fitness wearables
- Advanced analytics and AI insights
- Social features and challenges
- Nutrition tracking integration
- Video workout library
- Live streaming classes

## Contributing

This project is developed as a comprehensive fitness management solution. For contributions:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is developed for educational and commercial use. All rights reserved by KP Fitness.

## Support

For technical support or questions:
- Email: support@kpfitness.com
- Documentation: Check inline code comments
- Community: Join our developer community

---

**KP Fitness** - Unlock Your Inner Strength 💪

Built with ❤️ using modern web technologies