-- Fix requests table to properly handle document uploads
-- This migration adds necessary columns for document management

USE ebarangay_portal;

-- Add columns to requests table if they don't exist
ALTER TABLE requests 
ADD COLUMN IF NOT EXISTS processing_fee DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS document_path VARCHAR(500),
ADD COLUMN IF NOT EXISTS can_download TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS can_reupload TINYINT(1) DEFAULT 0;

-- Update status enum to include ready_for_pickup
ALTER TABLE requests 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'ready_for_pickup') DEFAULT 'pending';

SELECT 'Requests table updated successfully!' as message;