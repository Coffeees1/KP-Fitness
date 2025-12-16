-- Migration: Add CheckInTime column to reservations table
-- This column tracks when a client checks in for a session

USE kp_fitness_db;

ALTER TABLE `reservations` 
ADD COLUMN `CheckInTime` time DEFAULT NULL AFTER `Status`;
