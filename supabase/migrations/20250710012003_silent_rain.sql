-- Create Barangay Staff System Tables
-- This migration creates the necessary tables for the staff role-based access system

USE ebarangay_portal;

-- Create staff_users table for dedicated staff accounts
CREATE TABLE IF NOT EXISTS staff_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('Staff', 'Senior Staff') DEFAULT 'Staff',
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    department VARCHAR(100),
    position VARCHAR(100),
    hire_date DATE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
);

-- Create staff_activity_log table for tracking staff activities
CREATE TABLE IF NOT EXISTS staff_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50), -- 'request', 'announcement', 'resident', 'system'
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_action (action),
    INDEX idx_target_type (target_type),
    INDEX idx_created_at (created_at)
);

-- Create announcements table for staff to manage announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('general', 'urgent', 'event', 'service') DEFAULT 'general',
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    is_active BOOLEAN DEFAULT TRUE,
    expiry_date DATE NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
);

-- Update residents table to include Staff role
ALTER TABLE residents 
MODIFY COLUMN role ENUM('Resident', 'Admin', 'Super Admin', 'Staff', 'Barangay Official') DEFAULT 'Resident';

-- Insert demo staff user
INSERT INTO staff_users (email, password, first_name, last_name, role, status, department, position) VALUES
('staff@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Staff', 'Staff', 'Active', 'General Services', 'Barangay Staff Member');

-- Insert sample announcements
INSERT INTO announcements (title, content, type, priority, created_by) VALUES
('Welcome to E-Barangay Portal', 'We are pleased to announce the launch of our new digital barangay services portal. Residents can now request certificates and access services online.', 'general', 'normal', 1),
('Holiday Schedule Notice', 'Please be advised that barangay offices will be closed on December 25-26, 2024 for the Christmas holidays. Regular operations will resume on December 27, 2024.', 'urgent', 'high', 1),
('Community Health Program', 'Free health checkup and vaccination program will be conducted on January 15, 2025 at the barangay hall. All residents are encouraged to participate.', 'event', 'normal', 1);

-- Insert sample staff activity logs
INSERT INTO staff_activity_log (staff_id, action, target_type, target_id, details, ip_address) VALUES
(1, 'login', 'system', NULL, 'Staff member logged in', '127.0.0.1'),
(1, 'approve_request', 'request', 1, 'Approved Barangay Clearance request', '127.0.0.1'),
(1, 'create_announcement', 'announcement', 1, 'Created welcome announcement', '127.0.0.1'),
(1, 'view_residents', 'resident', NULL, 'Viewed residents list', '127.0.0.1');

SELECT 'Staff system tables created successfully!' as message;