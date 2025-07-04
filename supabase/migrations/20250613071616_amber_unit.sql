-- Fix Valid ID Type and other missing profile fields
-- This migration ensures all profile fields exist in the residents table

USE ebarangay_portal;

-- Add missing columns if they don't exist
ALTER TABLE residents 
ADD COLUMN IF NOT EXISTS valid_id_type VARCHAR(100),
ADD COLUMN IF NOT EXISTS valid_id_number VARCHAR(100),
ADD COLUMN IF NOT EXISTS sex ENUM('Male', 'Female') DEFAULT 'Male',
ADD COLUMN IF NOT EXISTS age INT,
ADD COLUMN IF NOT EXISTS citizenship VARCHAR(100) DEFAULT 'Filipino',
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255),
ADD COLUMN IF NOT EXISTS house_no VARCHAR(50),
ADD COLUMN IF NOT EXISTS lot VARCHAR(50),
ADD COLUMN IF NOT EXISTS street VARCHAR(100),
ADD COLUMN IF NOT EXISTS purok VARCHAR(50),
ADD COLUMN IF NOT EXISTS barangay VARCHAR(100) DEFAULT 'Sample Barangay',
ADD COLUMN IF NOT EXISTS city VARCHAR(100),
ADD COLUMN IF NOT EXISTS province VARCHAR(100),
ADD COLUMN IF NOT EXISTS zip_code VARCHAR(10),
ADD COLUMN IF NOT EXISTS years_of_residency INT,
ADD COLUMN IF NOT EXISTS landline_number VARCHAR(20),
ADD COLUMN IF NOT EXISTS voter_status ENUM('Registered', 'Not Registered') DEFAULT 'Not Registered',
ADD COLUMN IF NOT EXISTS voter_id VARCHAR(50),
ADD COLUMN IF NOT EXISTS barangay_id_number VARCHAR(50),
ADD COLUMN IF NOT EXISTS cedula_number VARCHAR(50),
ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(200),
ADD COLUMN IF NOT EXISTS emergency_contact_relationship VARCHAR(100),
ADD COLUMN IF NOT EXISTS emergency_contact_number VARCHAR(20),
ADD COLUMN IF NOT EXISTS emergency_contact_address TEXT,
ADD COLUMN IF NOT EXISTS employment_status ENUM('Employed', 'Unemployed', 'Student', 'Self-employed', 'Retired') DEFAULT 'Unemployed',
ADD COLUMN IF NOT EXISTS occupation VARCHAR(100),
ADD COLUMN IF NOT EXISTS place_of_work VARCHAR(200),
ADD COLUMN IF NOT EXISTS monthly_income_range VARCHAR(50),
ADD COLUMN IF NOT EXISTS role ENUM('Resident', 'Admin', 'Barangay Official') DEFAULT 'Resident',
ADD COLUMN IF NOT EXISTS status ENUM('Active', 'Deactivated', 'Pending Approval') DEFAULT 'Active',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update civil_status enum to include new values if needed
ALTER TABLE residents MODIFY COLUMN civil_status ENUM('Single', 'Married', 'Widowed', 'Separated', 'Divorced') DEFAULT 'Single';

-- Rename phone to mobile_number if it exists
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ebarangay_portal' 
    AND TABLE_NAME = 'residents' 
    AND COLUMN_NAME = 'phone');

SET @mobile_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ebarangay_portal' 
    AND TABLE_NAME = 'residents' 
    AND COLUMN_NAME = 'mobile_number');

-- If phone exists but mobile_number doesn't, rename it
SET @sql = IF(@column_exists > 0 AND @mobile_exists = 0, 
    'ALTER TABLE residents CHANGE COLUMN phone mobile_number VARCHAR(20)', 
    'SELECT "mobile_number already exists or phone column not found"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- If mobile_number doesn't exist, add it
SET @mobile_exists_after = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ebarangay_portal' 
    AND TABLE_NAME = 'residents' 
    AND COLUMN_NAME = 'mobile_number');

SET @sql2 = IF(@mobile_exists_after = 0, 
    'ALTER TABLE residents ADD COLUMN mobile_number VARCHAR(20)', 
    'SELECT "mobile_number column already exists"');

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Remove old address column if it exists and is not needed
SET @address_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ebarangay_portal' 
    AND TABLE_NAME = 'residents' 
    AND COLUMN_NAME = 'address');

SET @sql3 = IF(@address_exists > 0, 
    'ALTER TABLE residents DROP COLUMN address', 
    'SELECT "address column does not exist"');

PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- Create document_uploads table if it doesn't exist
CREATE TABLE IF NOT EXISTS document_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    document_type ENUM('Valid ID', 'Proof of Billing', 'Cedula', 'Profile Picture') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- Add indexes for new columns
CREATE INDEX IF NOT EXISTS idx_residents_voter_status ON residents(voter_status);
CREATE INDEX IF NOT EXISTS idx_residents_valid_id_type ON residents(valid_id_type);
CREATE INDEX IF NOT EXISTS idx_document_uploads_resident_id ON document_uploads(resident_id);

SELECT 'Database schema updated successfully! All profile fields are now available.' as message;