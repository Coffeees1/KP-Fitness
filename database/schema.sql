-- KP Fitness Database Schema
CREATE DATABASE IF NOT EXISTS kp_f;
USE kp_f;

-- Membership table
CREATE TABLE membership (
    MembershipID INT PRIMARY KEY AUTO_INCREMENT,
    Type ENUM('monthly', 'yearly', 'onetime') NOT NULL,
    Cost DECIMAL(10,2) NOT NULL,
    Duration INT NOT NULL, -- in days
    Benefits TEXT,
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    ClassID INT PRIMARY KEY AUTO_INCREMENT,
    ClassName VARCHAR(100) NOT NULL,
    Description TEXT,
    Duration INT NOT NULL, -- in minutes
    MaxCapacity INT NOT NULL DEFAULT 20,
    DifficultyLevel ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table (Admin, Client, Trainer accounts)
CREATE TABLE users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    FullName VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(20),
    Password VARCHAR(255) NOT NULL,
    Role ENUM('admin', 'trainer', 'client') NOT NULL DEFAULT 'client',
    DateOfBirth DATE,
    Height INT, -- in cm
    Weight INT, -- in kg
    ProfilePicture VARCHAR(255),
    MembershipID INT,
    TrainerID INT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    IsActive BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (MembershipID) REFERENCES membership(MembershipID) ON DELETE SET NULL,
    FOREIGN KEY (TrainerID) REFERENCES users(UserID) ON DELETE SET NULL
);

-- Sessions table (scheduled classes)
CREATE TABLE sessions (
    SessionID INT PRIMARY KEY AUTO_INCREMENT,
    SessionDate DATE NOT NULL,
    Time TIME NOT NULL,
    Room VARCHAR(50),
    ClassID INT NOT NULL,
    TrainerID INT NOT NULL,
    CurrentBookings INT DEFAULT 0,
    Status ENUM('scheduled', 'cancelled', 'completed') DEFAULT 'scheduled',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ClassID) REFERENCES classes(ClassID) ON DELETE CASCADE,
    FOREIGN KEY (TrainerID) REFERENCES users(UserID) ON DELETE CASCADE
);

-- Reservations table
CREATE TABLE reservations (
    ReservationID INT PRIMARY KEY AUTO_INCREMENT,
    BookingDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('booked', 'cancelled', 'attended', 'no_show') DEFAULT 'booked',
    UserID INT NOT NULL,
    SessionID INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (SessionID) REFERENCES sessions(SessionID) ON DELETE CASCADE,
    UNIQUE KEY unique_booking (UserID, SessionID)
);

-- Payments table
CREATE TABLE payments (
    PaymentID INT PRIMARY KEY AUTO_INCREMENT,
    PaymentDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Amount DECIMAL(10,2) NOT NULL,
    PaymentMethod ENUM('credit_card', 'debit_card', 'touch_n_go', 'cash', 'bank_transfer') DEFAULT 'credit_card',
    Status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    UserID INT NOT NULL,
    MembershipID INT NOT NULL,
    TransactionID VARCHAR(100),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (MembershipID) REFERENCES membership(MembershipID) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE attendance (
    AttendanceID INT PRIMARY KEY AUTO_INCREMENT,
    SessionID INT NOT NULL,
    UserID INT NOT NULL,
    AttendanceDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('present', 'absent', 'late') DEFAULT 'present',
    Notes TEXT,
    FOREIGN KEY (SessionID) REFERENCES sessions(SessionID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
);

-- Workout Plans table (for AI workout planner)
CREATE TABLE workout_plans (
    PlanID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    PlanName VARCHAR(100) NOT NULL,
    Age INT,
    Height INT,
    Weight INT,
    Goal ENUM('bulking', 'cutting', 'endurance', 'strength', 'general_fitness') NOT NULL,
    FitnessLevel ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
    PlanDetails JSON NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IsActive BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    NotificationID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    Title VARCHAR(100) NOT NULL,
    Message TEXT NOT NULL,
    Type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    IsRead BOOLEAN DEFAULT FALSE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (FullName, Email, Phone, Password, Role) VALUES 
('System Administrator', 'admin@kpfitness.com', '012-3456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default membership plans
INSERT INTO membership (Type, Cost, Duration, Benefits) VALUES 
('monthly', 118.00, 30, 'Unlimited classes, Access to all trainers, Priority booking'),
('yearly', 1183.00, 365, 'All monthly benefits, 2 months free, Guest passes (up to 2), Exclusive events'),
('onetime', 35.00, 1, 'Single class access, No commitment, Pay as you go');

-- Insert sample classes
INSERT INTO classes (ClassName, Description, Duration, MaxCapacity, DifficultyLevel) VALUES 
('HIIT Training', 'High-Intensity Interval Training for maximum calorie burn', 45, 20, 'intermediate'),
('Yoga Flow', 'Mindful movement and breathing exercises', 60, 15, 'beginner'),
('Strength Training', 'Build muscle and increase strength', 50, 12, 'intermediate'),
('Cardio Blast', 'High-energy cardio workout', 40, 25, 'beginner'),
('Pilates Core', 'Core strengthening and flexibility', 55, 18, 'beginner');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(Email);
CREATE INDEX idx_users_role ON users(Role);
CREATE INDEX idx_sessions_date ON sessions(SessionDate);
CREATE INDEX idx_reservations_user ON reservations(UserID);
CREATE INDEX idx_reservations_session ON reservations(SessionID);
CREATE INDEX idx_payments_user ON payments(UserID);