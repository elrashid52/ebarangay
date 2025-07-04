-- Update the requests table to include missing status values
-- Run this SQL command in phpMyAdmin or MySQL

USE ebarangay_portal;

-- Update the status ENUM to include 'completed' and 'pending_resubmission'
ALTER TABLE requests 
MODIFY COLUMN status ENUM(
    'pending', 
    'approved', 
    'completed', 
    'rejected', 
    'pending_resubmission', 
    'ready_for_pickup'
) DEFAULT 'pending';

-- Verify the change
DESCRIBE requests;

-- Optional: Update any existing 'approved' records to 'completed' for consistency
UPDATE requests SET status = 'completed' WHERE status = 'approved';

SELECT 'Database status ENUM updated successfully!' as message;