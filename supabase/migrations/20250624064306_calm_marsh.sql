-- Create Activity Log System for E-Barangay Portal
-- This migration creates tables for tracking admin and user activities

USE ebarangay_portal;

-- Create admin_activity_log table
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50), -- 'resident', 'request', 'admin', 'system'
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_target_type (target_type),
    INDEX idx_created_at (created_at)
);

-- Create user_activity_log table for resident activities
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('resident', 'admin') DEFAULT 'resident',
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50), -- 'profile', 'request', 'login', 'logout'
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_user_type (user_type),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- Insert sample activity data for demonstration
INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address) VALUES
(999, 'login', 'system', NULL, 'Demo admin logged in', '127.0.0.1'),
(999, 'approve_request', 'request', 1, 'Approved Barangay Clearance request', '127.0.0.1'),
(999, 'view_residents', 'resident', NULL, 'Viewed residents list', '127.0.0.1'),
(999, 'reject_request', 'request', 2, 'Rejected Certificate of Indigency request - Missing documents', '127.0.0.1');

INSERT INTO user_activity_log (user_id, user_type, action, target_type, target_id, details, ip_address) VALUES
(1, 'resident', 'login', 'system', NULL, 'User logged in', '127.0.0.1'),
(1, 'resident', 'update_profile', 'profile', 1, 'Updated personal information', '127.0.0.1'),
(1, 'resident', 'submit_request', 'request', 1, 'Submitted Barangay Clearance request', '127.0.0.1'),
(1, 'resident', 'view_requests', 'request', NULL, 'Viewed request history', '127.0.0.1');

SELECT 'Activity logging tables created successfully!' as message;