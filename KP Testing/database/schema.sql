-- KP Fitness Database Schema - Combined & Optimized
-- Version 3.0 (Final)
--
-- To use this file:
-- 1. Open phpMyAdmin in your XAMPP control panel.
-- 2. Create a new database named `kp_fitness_db`.
-- 3. Select the `kp_fitness_db` database.
-- 4. Go to the "Import" tab.
-- 5. Choose this `schema.sql` file and click "Go".

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `kp_fitness_db`
--
CREATE DATABASE IF NOT EXISTS `kp_fitness_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `kp_fitness_db`;

-- --------------------------------------------------------

--
-- Table structure for table `class_categories`
--

CREATE TABLE `class_categories` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `CategoryName` (`CategoryName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_categories`
--

INSERT INTO `class_categories` (`CategoryID`, `CategoryName`) VALUES
(1, 'Cardio'),
(5, 'Combat'),
(4, 'HIIT_Circuit'),
(3, 'MindAndBody'),
(2, 'Strength');

-- --------------------------------------------------------

--
-- Table structure for table `activities`
-- (Previously called `classes` in old schema)
--

CREATE TABLE `activities` (
  `ClassID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryID` int(11) NOT NULL,
  `ClassName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `MaxCapacity` int(11) NOT NULL DEFAULT 20,
  `Price` decimal(10,2) NOT NULL DEFAULT 25.00,
  `Specialist` varchar(255) DEFAULT NULL,
  `DifficultyLevel` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ClassID`),
  KEY `fk_category` (`CategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping sample data for table `activities`
--

INSERT INTO `activities` (`ClassID`, `CategoryID`, `ClassName`, `Description`, `Duration`, `MaxCapacity`, `Price`, `Specialist`, `DifficultyLevel`, `IsActive`, `CreatedAt`) VALUES
(1, 4, 'HIIT Training', 'High-Intensity Interval Training for maximum calorie burn', 45, 20, 35.00, 'Cardio & Strength', 'intermediate', 1, CURRENT_TIMESTAMP),
(2, 3, 'Yoga Flow', 'Mindful movement and breathing exercises', 60, 15, 30.00, 'Flexibility & Mindfulness', 'beginner', 1, CURRENT_TIMESTAMP),
(3, 2, 'Strength Training', 'Build muscle and increase strength', 50, 12, 40.00, 'Weightlifting', 'intermediate', 1, CURRENT_TIMESTAMP),
(4, 1, 'Cardio Blast', 'High-energy cardio workout', 40, 25, 25.00, 'Cardio', 'beginner', 1, CURRENT_TIMESTAMP),
(5, 3, 'Pilates Core', 'Core strengthening and flexibility', 55, 18, 35.00, 'Core & Flexibility', 'beginner', 1, CURRENT_TIMESTAMP);

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE `membership` (
  `MembershipID` int(11) NOT NULL AUTO_INCREMENT,
  `PlanName` varchar(100) NOT NULL,
  `Type` enum('monthly','yearly','onetime') NOT NULL,
  `Cost` decimal(10,2) NOT NULL,
  `Duration` int(11) NOT NULL COMMENT 'Duration in days',
  `Benefits` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`MembershipID`),
  UNIQUE KEY `PlanName` (`PlanName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership`
--

INSERT INTO `membership` (`MembershipID`, `PlanName`, `Type`, `Cost`, `Duration`, `Benefits`, `IsActive`, `CreatedAt`) VALUES
(1, 'Basic Monthly', 'monthly', 118.00, 30, 'Unlimited classes, Access to all trainers, Priority booking', 1, CURRENT_TIMESTAMP),
(2, 'Premium Yearly', 'yearly', 1183.00, 365, 'All monthly benefits, 2 months free, Guest passes (up to 2), Exclusive events', 1, CURRENT_TIMESTAMP),
(3, 'Day Pass', 'onetime', 35.00, 1, 'Single class access, No commitment, Pay as you go', 1, CURRENT_TIMESTAMP);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `FullName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('admin','trainer','client') NOT NULL DEFAULT 'client',
  `DateOfBirth` date DEFAULT NULL,
  `Height` int(11) DEFAULT NULL COMMENT 'Height in cm',
  `Weight` int(11) DEFAULT NULL COMMENT 'Weight in kg',
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `Specialist` varchar(255) DEFAULT NULL COMMENT 'For trainers only',
  `WorkingHours` varchar(255) DEFAULT NULL COMMENT 'For trainers only',
  `JobType` enum('Full-time','Part-time') DEFAULT NULL COMMENT 'For trainers only',
  `ProfilePicture` varchar(255) DEFAULT NULL,
  `MembershipID` int(11) DEFAULT NULL,
  `MembershipStartDate` date DEFAULT NULL,
  `MembershipEndDate` date DEFAULT NULL,
  `TrainerID` int(11) DEFAULT NULL COMMENT 'Assigned trainer for clients',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Email` (`Email`),
  KEY `MembershipID` (`MembershipID`),
  KEY `TrainerID` (`TrainerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users` (Default Admin)
--

INSERT INTO `users` (`UserID`, `FullName`, `Email`, `Phone`, `Password`, `Role`, `DateOfBirth`, `Height`, `Weight`, `Gender`, `Specialist`, `WorkingHours`, `JobType`, `ProfilePicture`, `MembershipID`, `MembershipStartDate`, `MembershipEndDate`, `TrainerID`, `CreatedAt`, `UpdatedAt`, `IsActive`) VALUES
(1, 'System Administrator', 'admin@kpfitness.com', '012-3456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `SessionID` int(11) NOT NULL AUTO_INCREMENT,
  `SessionDate` date NOT NULL,
  `Time` time NOT NULL,
  `Room` varchar(50) DEFAULT NULL,
  `ClassID` int(11) NOT NULL,
  `TrainerID` int(11) NOT NULL,
  `CurrentBookings` int(11) DEFAULT 0,
  `Status` enum('scheduled','cancelled','completed') DEFAULT 'scheduled',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`SessionID`),
  KEY `ClassID` (`ClassID`),
  KEY `TrainerID` (`TrainerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `ReservationID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('booked','cancelled','attended','no_show','Done','Rated') DEFAULT 'booked',
  `PaidAmount` decimal(10,2) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_id` varchar(255) DEFAULT NULL,
  `parent_reservation_id` int(11) DEFAULT NULL,
  `UserID` int(11) NOT NULL,
  `SessionID` int(11) NOT NULL,
  PRIMARY KEY (`ReservationID`),
  UNIQUE KEY `unique_booking` (`UserID`,`SessionID`),
  KEY `SessionID` (`SessionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL AUTO_INCREMENT,
  `SessionID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `AttendanceDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('present','absent','late') DEFAULT 'present',
  `Notes` text DEFAULT NULL,
  PRIMARY KEY (`AttendanceID`),
  KEY `SessionID` (`SessionID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PaymentID` int(11) NOT NULL AUTO_INCREMENT,
  `PaymentDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Amount` decimal(10,2) NOT NULL,
  `PaymentMethod` enum('credit_card','debit_card','touch_n_go','cash','bank_transfer') DEFAULT 'credit_card',
  `Status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `UserID` int(11) NOT NULL,
  `MembershipID` int(11) NOT NULL,
  `TransactionID` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`PaymentID`),
  KEY `UserID` (`UserID`),
  KEY `MembershipID` (`MembershipID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `RatingID` int(11) NOT NULL AUTO_INCREMENT,
  `ReservationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `TrainerID` int(11) NOT NULL,
  `RatingScore` int(11) NOT NULL CHECK (`RatingScore` >= 1 AND `RatingScore` <= 5),
  `Comment` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`RatingID`),
  KEY `ReservationID` (`ReservationID`),
  KEY `UserID` (`UserID`),
  KEY `TrainerID` (`TrainerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Message` text NOT NULL,
  `Type` enum('info','warning','success','error') DEFAULT 'info',
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`NotificationID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workout_plans`
--

CREATE TABLE `workout_plans` (
  `PlanID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `PlanName` varchar(100) NOT NULL,
  `Age` int(11) DEFAULT NULL,
  `Height` int(11) DEFAULT NULL COMMENT 'Height in cm',
  `Weight` int(11) DEFAULT NULL COMMENT 'Weight in kg',
  `Goal` enum('bulking','cutting','endurance','strength','general_fitness') NOT NULL,
  `FitnessLevel` enum('beginner','intermediate','advanced') NOT NULL,
  `PlanDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`PlanDetails`)),
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`PlanID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`CategoryID`) REFERENCES `class_categories` (`CategoryID`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`ClassID`) REFERENCES `activities` (`ClassID`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`TrainerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`SessionID`) REFERENCES `sessions` (`SessionID`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`SessionID`) REFERENCES `sessions` (`SessionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`MembershipID`) REFERENCES `membership` (`MembershipID`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`ReservationID`) REFERENCES `reservations` (`ReservationID`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`TrainerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`MembershipID`) REFERENCES `membership` (`MembershipID`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`TrainerID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD CONSTRAINT `workout_plans_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

COMMIT;
