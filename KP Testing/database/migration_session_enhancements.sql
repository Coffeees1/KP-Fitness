-- Migration: Add SessionCode and CheckInTime columns
-- Date: 2025-12-15
-- Description: Enhance sessions table for live session codes and reservations table for check-in tracking

-- Add SessionCode column to sessions table
ALTER TABLE sessions ADD COLUMN SessionCode VARCHAR(6) DEFAULT NULL;

-- Add CheckInTime column to reservations table
ALTER TABLE reservations ADD COLUMN CheckInTime DATETIME DEFAULT NULL;
