-- Disable foreign key checks to avoid constraint violations during cleanup
SET FOREIGN_KEY_CHECKS = 0;

-- 1. CLEANUP INVALID DATA (ID = 0)
-- Child tables first
DELETE FROM attendance WHERE AttendanceID = 0 OR UserID = 0 OR SessionID = 0;
DELETE FROM reservations WHERE ReservationID = 0 OR UserID = 0 OR SessionID = 0;
DELETE FROM notifications WHERE NotificationID = 0 OR UserID = 0;
DELETE FROM payments WHERE PaymentID = 0 OR UserID = 0;
-- Parent tables
DELETE FROM sessions WHERE SessionID = 0;
DELETE FROM classes WHERE ClassID = 0;
DELETE FROM users WHERE UserID = 0;
DELETE FROM membership WHERE MembershipID = 0;

-- 2. APPLY PRIMARY KEYS AND AUTO_INCREMENT

-- Users
ALTER TABLE users MODIFY UserID int(11) NOT NULL;
ALTER TABLE users ADD PRIMARY KEY (UserID);
ALTER TABLE users MODIFY UserID int(11) NOT NULL AUTO_INCREMENT;

-- Classes
ALTER TABLE classes MODIFY ClassID int(11) NOT NULL;
ALTER TABLE classes ADD PRIMARY KEY (ClassID);
ALTER TABLE classes MODIFY ClassID int(11) NOT NULL AUTO_INCREMENT;

-- Membership
ALTER TABLE membership MODIFY MembershipID int(11) NOT NULL;
ALTER TABLE membership ADD PRIMARY KEY (MembershipID);
ALTER TABLE membership MODIFY MembershipID int(11) NOT NULL AUTO_INCREMENT;

-- Notifications
ALTER TABLE notifications MODIFY NotificationID int(11) NOT NULL;
ALTER TABLE notifications ADD PRIMARY KEY (NotificationID);
ALTER TABLE notifications MODIFY NotificationID int(11) NOT NULL AUTO_INCREMENT;

-- Payments
ALTER TABLE payments MODIFY PaymentID int(11) NOT NULL;
ALTER TABLE payments ADD PRIMARY KEY (PaymentID);
ALTER TABLE payments MODIFY PaymentID int(11) NOT NULL AUTO_INCREMENT;

-- Reservations
ALTER TABLE reservations MODIFY ReservationID int(11) NOT NULL;
ALTER TABLE reservations ADD PRIMARY KEY (ReservationID);
ALTER TABLE reservations MODIFY ReservationID int(11) NOT NULL AUTO_INCREMENT;
-- Add Unique Constraint to prevent duplicate bookings (User can only book Session once)
-- Check if exists first (ignoring warnings)
-- ALTER TABLE reservations ADD UNIQUE KEY unique_booking (UserID, SessionID);

-- Attendance
ALTER TABLE attendance MODIFY AttendanceID int(11) NOT NULL;
ALTER TABLE attendance ADD PRIMARY KEY (AttendanceID);
ALTER TABLE attendance MODIFY AttendanceID int(11) NOT NULL AUTO_INCREMENT;


-- 3. CREATE MISSING TABLES
CREATE TABLE IF NOT EXISTS `workout_plans` (
  `PlanID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `PlanName` varchar(100) NOT NULL,
  `Age` int(11) DEFAULT NULL,
  `Height` int(11) DEFAULT NULL,
  `Weight` int(11) DEFAULT NULL,
  `Goal` enum('bulking','cutting','endurance','strength','general_fitness') NOT NULL,
  `FitnessLevel` enum('beginner','intermediate','advanced') NOT NULL,
  `PlanDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`PlanDetails`)),
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`PlanID`),
  KEY `UserID` (`UserID`),
  CONSTRAINT `workout_plans_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
