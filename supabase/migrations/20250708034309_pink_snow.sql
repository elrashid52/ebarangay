-- =====================================================
-- E-BARANGAY PORTAL SYSTEM - COMPLETE DATABASE SCHEMA
-- =====================================================
-- This file contains the complete MySQL database schema
-- for the E-Barangay Portal System with all tables,
-- relationships, indexes, and sample data.
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS ebarangay_portal;
USE ebarangay_portal;

-- Set SQL mode and foreign key checks
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABLE: residents
-- Main table for all residents/users in the system
-- =====================================================
CREATE TABLE IF NOT EXISTS residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    
    -- Basic Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    sex ENUM('Male', 'Female') DEFAULT 'Male',
    birth_date DATE,
    age INT,
    civil_status ENUM('Single', 'Married', 'Widowed', 'Separated', 'Divorced') DEFAULT 'Single',
    citizenship VARCHAR(100) DEFAULT 'Filipino',
    profile_picture VARCHAR(255),
    
    -- Address and Residency
    house_no VARCHAR(50),
    lot VARCHAR(50),
    street VARCHAR(100),
    purok VARCHAR(50),
    barangay VARCHAR(100) DEFAULT 'Sample Barangay',
    city VARCHAR(100),
    province VARCHAR(100),
    zip_code VARCHAR(10),
    years_of_residency INT,
    
    -- Contact Information
    mobile_number VARCHAR(20),
    landline_number VARCHAR(20),
    
    -- Voter Information
    voter_status ENUM('Registered', 'Not Registered') DEFAULT 'Not Registered',
    voter_id VARCHAR(50),
    
    -- Government IDs
    valid_id_type VARCHAR(100),
    valid_id_number VARCHAR(100),
    barangay_id_number VARCHAR(50),
    cedula_number VARCHAR(50),
    
    -- Emergency Contact
    emergency_contact_name VARCHAR(200),
    emergency_contact_relationship VARCHAR(100),
    emergency_contact_number VARCHAR(20),
    emergency_contact_address TEXT,
    
    -- Employment Information
    employment_status ENUM('Employed', 'Unemployed', 'Student', 'Self-employed', 'Retired') DEFAULT 'Unemployed',
    occupation VARCHAR(100),
    place_of_work VARCHAR(200),
    monthly_income_range VARCHAR(50),
    
    -- System Information
    role ENUM('Resident', 'Admin', 'Barangay Official', 'Super Admin') DEFAULT 'Resident',
    status ENUM('Active', 'Deactivated', 'Pending Approval') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_voter_status (voter_status),
    INDEX idx_valid_id_type (valid_id_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: admin_users
-- Dedicated table for admin users (optional)
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Super Admin', 'Moderator') DEFAULT 'Admin',
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    
    -- Indexes
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: request_types
-- Defines available certificate/document types
-- =====================================================
CREATE TABLE IF NOT EXISTS request_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    required_documents JSON,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    processing_days INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: requests
-- Certificate and document requests from residents
-- =====================================================
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'ready_for_pickup') DEFAULT 'pending',
    resident_id INT NOT NULL,
    request_details JSON,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    document_path VARCHAR(500),
    can_download TINYINT(1) DEFAULT 0,
    can_reupload TINYINT(1) DEFAULT 0,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    
    -- Foreign Keys
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_resident_id (resident_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_processed_at (processed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: document_uploads
-- File attachments and uploads
-- =====================================================
CREATE TABLE IF NOT EXISTS document_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    document_type ENUM('Valid ID', 'Proof of Billing', 'Cedula', 'Profile Picture', 'Certificate', 'Other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_resident_id (resident_id),
    INDEX idx_document_type (document_type),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: blotter_reports
-- Incident reports and complaints
-- =====================================================
CREATE TABLE IF NOT EXISTS blotter_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_type VARCHAR(100) NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME NOT NULL,
    location TEXT NOT NULL,
    description TEXT NOT NULL,
    complainant_id INT NOT NULL,
    respondent_name VARCHAR(200),
    respondent_address TEXT,
    status ENUM('filed', 'under_investigation', 'resolved', 'dismissed') DEFAULT 'filed',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (complainant_id) REFERENCES residents(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_complainant_id (complainant_id),
    INDEX idx_status (status),
    INDEX idx_incident_date (incident_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: admin_activity_log
-- Tracks all admin activities for auditing
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50), -- 'resident', 'request', 'admin', 'system', 'backup'
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_target_type (target_type),
    INDEX idx_target_id (target_id),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: user_activity_log
-- Tracks all resident/user activities
-- =====================================================
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
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES residents(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_user_type (user_type),
    INDEX idx_action (action),
    INDEX idx_target_type (target_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: announcements
-- Barangay announcements and news
-- =====================================================
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('General', 'Emergency', 'Event', 'Notice', 'News') DEFAULT 'General',
    priority ENUM('Low', 'Normal', 'High', 'Urgent') DEFAULT 'Normal',
    is_active BOOLEAN DEFAULT TRUE,
    date_posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_admin_id (admin_id),
    INDEX idx_announcement_type (announcement_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active),
    INDEX idx_date_posted (date_posted),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: barangay_officials
-- Information about barangay officials
-- =====================================================
CREATE TABLE IF NOT EXISTS barangay_officials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(255),
    term_start DATE,
    term_end DATE,
    is_active BOOLEAN DEFAULT TRUE,
    photo_path VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_position (position),
    INDEX idx_department (department),
    INDEX idx_is_active (is_active),
    INDEX idx_term_start (term_start),
    INDEX idx_term_end (term_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: system_settings
-- System configuration and settings
-- =====================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT REQUEST TYPES
-- =====================================================
INSERT INTO request_types (name, description, required_documents, processing_fee, processing_days) VALUES
('Barangay Clearance', 'Certificate of good moral character and residence', 
 '["Valid Government-issued ID (with address)", "Proof of Billing / Proof of Residency (if not on ID)"]', 
 50.00, 3),

('Certificate of Residency', 'Proof of residence in the barangay', 
 '["Valid Government-issued ID with address", "Proof of Residency (e.g., utility bill, lease contract)"]', 
 30.00, 1),

('Certificate of Indigency', 'Certificate for low-income residents', 
 '["Valid ID", "No income or proof of unemployment"]', 
 0.00, 2),

('Barangay Business Clearance', 'Permit to operate a business in the barangay', 
 '["Business Name Registration (from DTI or SEC)", "Business Permit Application Form", "Valid ID of the business owner", "Location sketch of business"]', 
 200.00, 7),

('Barangay ID', 'Official barangay identification card', 
 '["Proof of Residency (utility bill, lease, or certification)", "Passport-sized photo", "Valid IDs"]', 
 100.00, 7);

-- =====================================================
-- INSERT SAMPLE ADMIN USERS
-- =====================================================
INSERT INTO admin_users (email, password, first_name, last_name, role, status) VALUES
('admin@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Admin', 'Super Admin', 'Active'),
('moderator@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Moderator', 'Admin', 'Active');

-- =====================================================
-- INSERT SAMPLE RESIDENTS
-- =====================================================
INSERT INTO residents (
    email, password, first_name, last_name, middle_name, sex, birth_date, age, civil_status, citizenship,
    house_no, street, purok, barangay, city, province, zip_code, years_of_residency,
    mobile_number, voter_status, voter_id, valid_id_type, valid_id_number,
    emergency_contact_name, emergency_contact_relationship, emergency_contact_number,
    employment_status, occupation, monthly_income_range, role, status
) VALUES
-- Demo Resident Account (password: "password")
('john.doe@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'John', 'Doe', 'Smith', 'Male', '1990-01-15', 34, 'Single', 'Filipino',
 '123', 'Main Street', 'Purok 1', 'Sample Barangay', 'Sample City', 'Sample Province', '1234', 5,
 '09123456789', 'Registered', '1234-5678-9012-3456', 'Driver\'s License', 'N01-12-123456',
 'Jane Doe', 'Sister', '09987654321',
 'Employed', 'Software Developer', '50,000 - 75,000', 'Resident', 'Active'),

-- Sample Resident 2
('maria.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Maria', 'Santos', 'Cruz', 'Female', '1985-05-20', 39, 'Married', 'Filipino',
 '456', 'Oak Avenue', 'Purok 2', 'Sample Barangay', 'Sample City', 'Sample Province', '1234', 8,
 '09234567890', 'Registered', '2345-6789-0123-4567', 'Passport', 'P123456789',
 'Pedro Santos', 'Husband', '09876543210',
 'Self-employed', 'Store Owner', '25,000 - 50,000', 'Resident', 'Active'),

-- Sample Resident 3
('carlos.reyes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Carlos', 'Reyes', 'Garcia', 'Male', '1975-12-10', 49, 'Married', 'Filipino',
 '789', 'Pine Street', 'Purok 3', 'Sample Barangay', 'Sample City', 'Sample Province', '1234', 12,
 '09345678901', 'Not Registered', '', 'SSS ID', 'SS-1234567890',
 'Ana Reyes', 'Wife', '09765432109',
 'Employed', 'Teacher', '30,000 - 50,000', 'Resident', 'Active'),

-- Admin Resident Account
('admin.resident@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Admin', 'User', '', 'Male', '1980-01-01', 44, 'Single', 'Filipino',
 '001', 'Admin Street', 'Purok Admin', 'Sample Barangay', 'Sample City', 'Sample Province', '1234', 10,
 '09111111111', 'Registered', 'ADMIN-123456', 'Government ID', 'GOV-123456',
 'Emergency Admin', 'Contact', '09222222222',
 'Employed', 'Barangay Administrator', '75,000+', 'Admin', 'Active');

-- =====================================================
-- INSERT SAMPLE REQUESTS
-- =====================================================
INSERT INTO requests (type, purpose, resident_id, status, processing_fee, created_at, processed_at) VALUES
('Barangay Clearance', 'Employment requirements', 1, 'approved', 50.00, '2024-01-15 10:00:00', '2024-01-16 14:30:00'),
('Certificate of Indigency', 'Medical assistance application', 1, 'pending', 0.00, '2024-01-20 14:30:00', NULL),
('Certificate of Residency', 'Bank account opening', 2, 'approved', 30.00, '2024-01-18 09:15:00', '2024-01-18 16:45:00'),
('Barangay Business Clearance', 'Sari-sari store permit', 2, 'pending', 200.00, '2024-01-22 11:20:00', NULL),
('Barangay ID', 'Personal identification', 3, 'ready_for_pickup', 100.00, '2024-01-10 08:30:00', '2024-01-17 10:00:00');

-- =====================================================
-- INSERT SAMPLE BLOTTER REPORTS
-- =====================================================
INSERT INTO blotter_reports (incident_type, incident_date, incident_time, location, description, complainant_id, respondent_name, respondent_address, status) VALUES
('Noise Complaint', '2024-01-10', '22:30:00', '456 Oak Street, Barangay Sample', 'Loud music disturbing the peace during late hours', 1, 'Jane Smith', '456 Oak Street, Barangay Sample', 'under_investigation'),
('Property Dispute', '2024-01-12', '14:00:00', '789 Pine Street, Barangay Sample', 'Boundary dispute between neighbors', 2, 'Robert Johnson', '790 Pine Street, Barangay Sample', 'filed'),
('Theft', '2024-01-15', '03:00:00', '123 Main Street, Barangay Sample', 'Bicycle stolen from front yard', 3, 'Unknown', 'Unknown', 'under_investigation');

-- =====================================================
-- INSERT SAMPLE BARANGAY OFFICIALS
-- =====================================================
INSERT INTO barangay_officials (name, position, department, contact_number, email, term_start, term_end, is_active) VALUES
('Captain Juan Dela Cruz', 'Barangay Captain', 'Executive', '09171234567', 'captain@barangay.gov.ph', '2023-01-01', '2026-12-31', TRUE),
('Kagawad Maria Gonzales', 'Kagawad', 'Health & Sanitation', '09181234567', 'maria.gonzales@barangay.gov.ph', '2023-01-01', '2026-12-31', TRUE),
('Kagawad Pedro Martinez', 'Kagawad', 'Peace & Order', '09191234567', 'pedro.martinez@barangay.gov.ph', '2023-01-01', '2026-12-31', TRUE),
('Secretary Ana Lopez', 'Barangay Secretary', 'Administrative', '09201234567', 'secretary@barangay.gov.ph', '2023-01-01', '2026-12-31', TRUE),
('Treasurer Carlos Ramos', 'Barangay Treasurer', 'Finance', '09211234567', 'treasurer@barangay.gov.ph', '2023-01-01', '2026-12-31', TRUE);

-- =====================================================
-- INSERT SAMPLE ANNOUNCEMENTS
-- =====================================================
INSERT INTO announcements (admin_id, title, content, announcement_type, priority, date_posted) VALUES
(1, 'Community Clean-up Drive', 'Join us for our monthly community clean-up drive this Saturday, 8:00 AM at the Barangay Hall. Bring your own cleaning materials.', 'Event', 'Normal', '2024-01-20 08:00:00'),
(1, 'Water Interruption Notice', 'Water supply will be temporarily interrupted on January 25, 2024, from 9:00 AM to 3:00 PM for maintenance work.', 'Notice', 'High', '2024-01-22 10:00:00'),
(1, 'Barangay Assembly Meeting', 'Monthly barangay assembly meeting scheduled for January 30, 2024, at 7:00 PM. All residents are encouraged to attend.', 'General', 'Normal', '2024-01-23 15:00:00');

-- =====================================================
-- INSERT SAMPLE ACTIVITY LOGS
-- =====================================================
INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address) VALUES
(1, 'login', 'system', NULL, 'Admin logged in successfully', '127.0.0.1'),
(1, 'approve_request', 'request', 1, 'Approved Barangay Clearance request for John Doe', '127.0.0.1'),
(1, 'create_announcement', 'announcement', 1, 'Created announcement: Community Clean-up Drive', '127.0.0.1'),
(1, 'view_residents', 'resident', NULL, 'Viewed residents list', '127.0.0.1');

INSERT INTO user_activity_log (user_id, user_type, action, target_type, target_id, details, ip_address) VALUES
(1, 'resident', 'login', 'system', NULL, 'Resident logged in successfully', '127.0.0.1'),
(1, 'resident', 'submit_request', 'request', 1, 'Submitted Barangay Clearance request', '127.0.0.1'),
(1, 'resident', 'update_profile', 'profile', 1, 'Updated personal information', '127.0.0.1'),
(2, 'resident', 'login', 'system', NULL, 'Resident logged in successfully', '127.0.0.1'),
(2, 'resident', 'submit_request', 'request', 3, 'Submitted Certificate of Residency request', '127.0.0.1');

-- =====================================================
-- INSERT SYSTEM SETTINGS
-- =====================================================
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('barangay_name', 'Sample Barangay', 'string', 'Official name of the barangay', TRUE),
('barangay_address', 'Sample City, Sample Province', 'string', 'Official address of the barangay', TRUE),
('contact_number', '(02) 123-4567', 'string', 'Official contact number', TRUE),
('email_address', 'info@barangay.gov.ph', 'string', 'Official email address', TRUE),
('office_hours', 'Monday to Friday, 8:00 AM - 5:00 PM', 'string', 'Official office hours', TRUE),
('system_version', '1.0.0', 'string', 'Current system version', FALSE),
('maintenance_mode', 'false', 'boolean', 'System maintenance mode', FALSE),
('max_file_size', '5242880', 'number', 'Maximum file upload size in bytes (5MB)', FALSE),
('allowed_file_types', '["pdf", "jpg", "jpeg", "png"]', 'json', 'Allowed file types for uploads', FALSE);

-- =====================================================
-- CREATE ADDITIONAL INDEXES FOR PERFORMANCE
-- =====================================================

-- Composite indexes for common queries
CREATE INDEX idx_requests_resident_status ON requests(resident_id, status);
CREATE INDEX idx_requests_type_status ON requests(type, status);
CREATE INDEX idx_requests_created_status ON requests(created_at, status);

-- Full-text search indexes
ALTER TABLE residents ADD FULLTEXT(first_name, last_name, middle_name);
ALTER TABLE announcements ADD FULLTEXT(title, content);

-- =====================================================
-- CREATE VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for active residents with basic info
CREATE OR REPLACE VIEW active_residents AS
SELECT 
    id, email, first_name, last_name, middle_name,
    CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as full_name,
    mobile_number, barangay, city, voter_status, employment_status,
    created_at
FROM residents 
WHERE status = 'Active' AND role = 'Resident';

-- View for pending requests with resident info
CREATE OR REPLACE VIEW pending_requests AS
SELECT 
    r.id, r.type, r.purpose, r.processing_fee, r.created_at,
    CONCAT(res.first_name, ' ', res.last_name) as resident_name,
    res.email as resident_email, res.mobile_number
FROM requests r
JOIN residents res ON r.resident_id = res.id
WHERE r.status = 'pending'
ORDER BY r.created_at ASC;

-- View for recent activities (last 30 days)
CREATE OR REPLACE VIEW recent_activities AS
SELECT 
    'admin' as activity_type,
    al.created_at,
    CONCAT(IFNULL(au.first_name, 'Unknown'), ' ', IFNULL(au.last_name, 'Admin')) as user_name,
    al.action,
    al.details
FROM admin_activity_log al
LEFT JOIN admin_users au ON al.admin_id = au.id
WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)

UNION ALL

SELECT 
    'resident' as activity_type,
    ul.created_at,
    CONCAT(r.first_name, ' ', r.last_name) as user_name,
    ul.action,
    ul.details
FROM user_activity_log ul
JOIN residents r ON ul.user_id = r.id
WHERE ul.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)

ORDER BY created_at DESC;

-- =====================================================
-- CREATE STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedure to get dashboard statistics
CREATE PROCEDURE GetDashboardStats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM residents WHERE status = 'Active' AND role = 'Resident') as total_residents,
        (SELECT COUNT(*) FROM requests WHERE status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM requests WHERE status = 'approved' AND DATE(processed_at) = CURDATE()) as approved_today,
        (SELECT COUNT(*) FROM requests) as total_requests,
        (SELECT COUNT(*) FROM requests WHERE status = 'rejected') as rejected_requests,
        (SELECT COUNT(*) FROM blotter_reports WHERE status IN ('filed', 'under_investigation')) as open_blotters;
END //

-- Procedure to get resident statistics
CREATE PROCEDURE GetResidentStats()
BEGIN
    SELECT 
        COUNT(*) as total_residents,
        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_residents,
        COUNT(CASE WHEN voter_status = 'Registered' THEN 1 END) as registered_voters,
        COUNT(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN 1 END) as new_this_month,
        COUNT(CASE WHEN age BETWEEN 18 AND 30 THEN 1 END) as age_18_30,
        COUNT(CASE WHEN age BETWEEN 31 AND 50 THEN 1 END) as age_31_50,
        COUNT(CASE WHEN age BETWEEN 51 AND 70 THEN 1 END) as age_51_70,
        COUNT(CASE WHEN age > 70 THEN 1 END) as age_over_70
    FROM residents 
    WHERE role = 'Resident';
END //

DELIMITER ;

-- =====================================================
-- CREATE TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger to automatically calculate age when birth_date is updated
CREATE TRIGGER calculate_age_on_insert 
BEFORE INSERT ON residents
FOR EACH ROW
BEGIN
    IF NEW.birth_date IS NOT NULL THEN
        SET NEW.age = TIMESTAMPDIFF(YEAR, NEW.birth_date, CURDATE());
    END IF;
END //

CREATE TRIGGER calculate_age_on_update 
BEFORE UPDATE ON residents
FOR EACH ROW
BEGIN
    IF NEW.birth_date IS NOT NULL AND NEW.birth_date != OLD.birth_date THEN
        SET NEW.age = TIMESTAMPDIFF(YEAR, NEW.birth_date, CURDATE());
    END IF;
END //

-- Trigger to clear voter_id when voter_status is not 'Registered'
CREATE TRIGGER clear_voter_id_on_insert
BEFORE INSERT ON residents
FOR EACH ROW
BEGIN
    IF NEW.voter_status != 'Registered' THEN
        SET NEW.voter_id = NULL;
    END IF;
END //

CREATE TRIGGER clear_voter_id_on_update
BEFORE UPDATE ON residents
FOR EACH ROW
BEGIN
    IF NEW.voter_status != 'Registered' THEN
        SET NEW.voter_id = NULL;
    END IF;
END //

DELIMITER ;

-- =====================================================
-- COMMIT TRANSACTION AND RESTORE SETTINGS
-- =====================================================

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- FINAL SUCCESS MESSAGE
-- =====================================================
SELECT 'E-Barangay Portal database setup completed successfully!' as message,
       'Database created with all tables, indexes, views, procedures, and sample data.' as details,
       NOW() as completed_at;

-- =====================================================
-- USAGE INSTRUCTIONS
-- =====================================================
/*
USAGE INSTRUCTIONS:

1. IMPORT THIS FILE:
   - Open phpMyAdmin
   - Click "Import" tab
   - Choose this SQL file
   - Click "Go"

2. DEFAULT ACCOUNTS:
   
   ADMIN ACCOUNTS:
   - Email: admin@barangay.gov.ph
   - Password: password
   
   RESIDENT ACCOUNTS:
   - Email: john.doe@email.com
   - Password: password
   
   - Email: maria.santos@email.com  
   - Password: password
   
   - Email: carlos.reyes@email.com
   - Password: password

3. FEATURES INCLUDED:
   - Complete user management
   - Certificate request system
   - Document upload system
   - Activity logging
   - Blotter reporting
   - Admin dashboard
   - Announcements system
   - Barangay officials directory
   - System settings
   - Performance indexes
   - Database views
   - Stored procedures
   - Triggers for data integrity

4. DIRECTORY STRUCTURE NEEDED:
   Create these directories in your project:
   - uploads/
   - uploads/requests/
   - uploads/certificates/
   - uploads/profile_pictures/
   - backups/

5. CONFIGURATION:
   Update config/database.php with your database credentials:
   - Host: localhost
   - Database: ebarangay_portal
   - Username: root (or your MySQL username)
   - Password: (your MySQL password)

6. TESTING:
   - Access admin portal: admin.php
   - Access resident portal: index.php
   - Use the demo accounts listed above
   - All passwords are: "password"

This schema supports the complete E-Barangay Portal System
with all features, security, and performance optimizations.
*/