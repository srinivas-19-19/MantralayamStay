-- Create a new database named 'hotel_booking_db'
CREATE DATABASE IF NOT EXISTS hotel_booking_db;

-- Use the newly created database
USE hotel_booking_db;

-- =================================================================
-- GUEST (USER) SCHEMA
-- =================================================================

-- Table for guests who will book rooms.
CREATE TABLE `guests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =================================================================
-- OWNER & PROPERTY SCHEMA
-- =================================================================

-- Table for property owners.
CREATE TABLE `owners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for hotels, each linked to a specific owner.
CREATE TABLE `hotels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `hotel_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `owners`(`id`) ON DELETE CASCADE
);

-- Table for the rooms, linked to a specific hotel.
CREATE TABLE `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
);


-- =================================================================
-- BOOKING SCHEMA
-- =================================================================

-- Table to store booking information, linking a guest to a room.
CREATE TABLE `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `guest_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `checkin_date` DATE NOT NULL,
    `checkout_date` DATE NOT NULL,
    `total_price` DECIMAL(10, 2) NOT NULL,
    `booking_status` ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    `booked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
);

-- Drop the existing admins table to ensure a clean start
DROP TABLE IF EXISTS `admins`;

-- Create a new, simplified admins table
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
);

-- Insert the admin user with a simple, plain text password
-- The password is 'adminpass'
INSERT INTO `admins` (`username`, `password`) VALUES ('admin', 'adminpass');

-- Use your existing database
USE hotel_booking_db;

-- Add columns to the 'guests' table for email verification
ALTER TABLE `guests`
ADD COLUMN `verification_token` VARCHAR(255) NULL AFTER `password`,
ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `verification_token`;


-- Use your existing database
USE hotel_booking_db;

-- Add columns to the 'owners' table for email OTP verification
ALTER TABLE `owners`
ADD COLUMN `otp_code` VARCHAR(10) NULL DEFAULT NULL AFTER `password`,
ADD COLUMN `otp_expires_at` DATETIME NULL DEFAULT NULL AFTER `otp_code`,
ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `otp_expires_at`;


-- Use your existing database
USE hotel_booking_db;

-- Add a 'status' column to the 'rooms' table
ALTER TABLE `rooms`
ADD COLUMN `status` ENUM('available', 'hidden') NOT NULL DEFAULT 'available' AFTER `image`;


-- Use your existing database
USE hotel_booking_db;

-- Add columns to the 'owners' table for personal and payment details
ALTER TABLE `owners`
ADD COLUMN `mobile_number` VARCHAR(15) NULL DEFAULT NULL AFTER `full_name`,
ADD COLUMN `bank_name` VARCHAR(100) NULL DEFAULT NULL AFTER `is_verified`,
ADD COLUMN `account_number` VARCHAR(50) NULL DEFAULT NULL AFTER `bank_name`,
ADD COLUMN `ifsc_code` VARCHAR(20) NULL DEFAULT NULL AFTER `account_number`,
ADD COLUMN `upi_id` VARCHAR(100) NULL DEFAULT NULL AFTER `ifsc_code`;
