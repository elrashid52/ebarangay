-- E-Barangay Portal Complete Database Setup
-- Import this file into phpMyAdmin or MySQL to create the complete database structure

-- Create database
CREATE DATABASE IF NOT EXISTS ebarangay_portal;
USE ebarangay_portal;

-- =====================================================
-- RESIDENTS TABLE (Main user table with comprehensive profile)
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
    role ENUM('Resident', 'Admin', 'Super Admin') DEFAULT 'Resident',
    status ENUM('Active', 'Deactivated', 'Pending Approval') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- ADMIN USERS TABLE (Separate admin accounts)
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Super Admin') DEFAULT 'Admin',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT
);

-- =====================================================
-- REQUEST TYPES TABLE (Certificate types and their details)
-- =====================================================
CREATE TABLE IF NOT EXISTS request_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    required_documents JSON,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    processing_days INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- REQUESTS TABLE (Certificate requests from residents)
-- =====================================================
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'ready_for_pickup') DEFAULT 'pending',
    resident_id INT NOT NULL,
    request_details JSON,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    document_path VARCHAR(255),
    can_download BOOLEAN DEFAULT FALSE,
    can_reupload BOOLEAN DEFAULT FALSE,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- =====================================================
-- DOCUMENT UPLOADS TABLE (File attachments)
-- =====================================================
CREATE TABLE IF NOT EXISTS document_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    document_type ENUM('Valid ID', 'Proof of Billing', 'Cedula', 'Profile Picture', 'Passport Photo', 'Proof of Residency', 'Proof of Unemployment') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- =====================================================
-- BLOTTER REPORTS TABLE (Incident reports)
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
    FOREIGN KEY (complainant_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- =====================================================
-- ADMIN ACTIVITY LOG TABLE (Track admin actions)
-- =====================================================
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

-- =====================================================
-- USER ACTIVITY LOG TABLE (Track resident activities)
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
    INDEX idx_user_id (user_id),
    INDEX idx_user_type (user_type),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- =====================================================
-- ANNOUNCEMENTS TABLE (Barangay announcements)
-- =====================================================
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('General', 'Emergency', 'Event', 'Notice') DEFAULT 'General',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    is_active BOOLEAN DEFAULT TRUE,
    date_posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- BARANGAY OFFICIALS TABLE (Officials directory)
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default request types
INSERT INTO request_types (name, description, required_documents, processing_fee, processing_days) VALUES
('Barangay Clearance', 'Certificate of good moral character and residence', 
 '["Valid Government-issued ID (with address)", "Proof of Billing / Proof of Residency (if not on ID)"]', 50.00, 3),
('Certificate of Residency', 'Proof of residence in the barangay', 
 '["Valid Government-issued ID with address", "Proof of Residency (e.g., utility bill, lease contract)"]', 30.00, 1),
('Certificate of Indigency', 'Certificate for low-income residents', 
 '["Valid ID", "No income or proof of unemployment"]', 0.00, 2),
('Barangay Business Clearance', 'Permit to operate a business in the barangay', 
 '["Business Name Registration (from DTI or SEC)", "Business Permit Application Form", "Valid ID of the business owner", "Location sketch of business"]', 200.00, 7),
('Barangay ID', 'Official barangay identification card', 
 '["Proof of Residency (utility bill, lease, or certification)", "Passport-sized photo", "Valid IDs"]', 100.00, 7);

-- Insert demo admin user
INSERT INTO admin_users (email, password, first_name, last_name, role, status) VALUES
('admin@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Admin', 'Super Admin', 'Active');

-- Insert sample resident for testing
INSERT INTO residents (
    email, password, first_name, last_name, middle_name, sex, birth_date, age, civil_status, citizenship,
    house_no, street, purok, barangay, city, province, zip_code, years_of_residency,
    mobile_number, voter_status, voter_id, valid_id_type, valid_id_number,
    emergency_contact_name, emergency_contact_relationship, emergency_contact_number,
    employment_status, occupation, monthly_income_range
) VALUES
('john.doe@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'John', 'Doe', 'Smith', 'Male', '1990-01-15', 34, 'Single', 'Filipino',
 '123', 'Main Street', 'Purok 1', 'Sample Barangay', 'Sample City', 'Sample Province', '1234', 5,
 '09123456789', 'Registered', '1234-5678-9012-3456', 'Driver\'s License', 'N01-12-123456',
 'Jane Doe', 'Sister', '09987654321',
 'Employed', 'Software Developer', '50,000 - 75,000');

-- Insert sample requests
INSERT INTO requests (type, purpose, resident_id, status, processing_fee, created_at) VALUES
('Barangay Clearance', 'Employment requirements', 1, 'approved', 50.00, '2024-01-15 10:00:00'),
('Certificate of Indigency', 'Medical assistance application', 1, 'pending', 0.00, '2024-01-20 14:30:00'),
('Certificate of Residency', 'Bank account opening', 1, 'rejected', 30.00, '2024-01-18 09:15:00');

-- Insert sample barangay officials
INSERT INTO barangay_officials (name, position, department, contact_number, email, term_start, term_end) VALUES
('Maria Santos', 'Barangay Captain', 'Executive', '09123456789', 'captain@barangay.gov.ph', '2023-01-01', '2025-12-31'),
('Jose Garcia', 'Barangay Secretary', 'Administrative', '09234567890', 'secretary@barangay.gov.ph', '2023-01-01', '2025-12-31'),
('Ana Cruz', 'Barangay Treasurer', 'Finance', '09345678901', 'treasurer@barangay.gov.ph', '2023-01-01', '2025-12-31'),
('Roberto Luna', 'Kagawad - Health', 'Health Services', '09456789012', 'health@barangay.gov.ph', '2023-01-01', '2025-12-31'),
('Carmen Reyes', 'Kagawad - Education', 'Education', '09567890123', 'education@barangay.gov.ph', '2023-01-01', '2025-12-31');

-- Insert sample announcements
INSERT INTO announcements (admin_id, title, content, announcement_type, priority, expiry_date) VALUES
(1, 'Community Health Program', 'Free health checkup and vaccination program will be conducted on January 30, 2024, at the Barangay Hall from 8:00 AM to 5:00 PM.', 'Event', 'High', '2024-01-30'),
(1, 'Barangay Assembly Meeting', 'All residents are invited to attend the quarterly barangay assembly meeting on February 15, 2024, at 7:00 PM at the Barangay Hall.', 'General', 'Medium', '2024-02-15'),
(1, 'Road Maintenance Notice', 'Road maintenance and repair work will be conducted on Main Street from February 1-5, 2024. Expect traffic delays and plan alternate routes.', 'Notice', 'Medium', '2024-02-05');

-- Insert sample activity logs
INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address) VALUES
(1, 'login', 'system', NULL, 'Admin logged in', '127.0.0.1'),
(1, 'approve_request', 'request', 1, 'Approved Barangay Clearance request', '127.0.0.1'),
(1, 'reject_request', 'request', 3, 'Rejected Certificate of Residency request - Incomplete documents', '127.0.0.1'),
(1, 'create_announcement', 'announcement', 1, 'Created new announcement: Community Health Program', '127.0.0.1');

INSERT INTO user_activity_log (user_id, user_type, action, target_type, target_id, details, ip_address) VALUES
(1, 'resident', 'login', 'system', NULL, 'User logged in', '127.0.0.1'),
(1, 'resident', 'update_profile', 'profile', 1, 'Updated personal information', '127.0.0.1'),
(1, 'resident', 'submit_request', 'request', 1, 'Submitted Barangay Clearance request', '127.0.0.1'),
(1, 'resident', 'submit_request', 'request', 2, 'Submitted Certificate of Indigency request', '127.0.0.1');

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Residents table indexes
CREATE INDEX idx_residents_email ON residents(email);
CREATE INDEX idx_residents_voter_status ON residents(voter_status);
CREATE INDEX idx_residents_valid_id_type ON residents(valid_id_type);
CREATE INDEX idx_residents_role ON residents(role);
CREATE INDEX idx_residents_status ON residents(status);

-- Requests table indexes
CREATE INDEX idx_requests_resident_id ON requests(resident_id);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_requests_type ON requests(type);
CREATE INDEX idx_requests_created_at ON requests(created_at);

-- Document uploads indexes
CREATE INDEX idx_document_uploads_resident_id ON document_uploads(resident_id);
CREATE INDEX idx_document_uploads_type ON document_uploads(document_type);

-- Blotter reports indexes
CREATE INDEX idx_blotter_complainant_id ON blotter_reports(complainant_id);
CREATE INDEX idx_blotter_status ON blotter_reports(status);
CREATE INDEX idx_blotter_incident_date ON blotter_reports(incident_date);

-- Admin users indexes
CREATE INDEX idx_admin_users_email ON admin_users(email);
CREATE INDEX idx_admin_users_role ON admin_users(role);
CREATE INDEX idx_admin_users_status ON admin_users(status);

-- Announcements indexes
CREATE INDEX idx_announcements_admin_id ON announcements(admin_id);
CREATE INDEX idx_announcements_type ON announcements(announcement_type);
CREATE INDEX idx_announcements_active ON announcements(is_active);
CREATE INDEX idx_announcements_date_posted ON announcements(date_posted);

-- Barangay officials indexes
CREATE INDEX idx_officials_position ON barangay_officials(position);
CREATE INDEX idx_officials_active ON barangay_officials(is_active);

-- =====================================================
-- CREATE UPLOAD DIRECTORIES (Manual step required)
-- =====================================================
-- Note: You need to manually create these directories in your XAMPP htdocs folder:
-- uploads/
-- ├── profile_pictures/
-- ├── certificates/
-- ├── requests/
-- └── announcements/

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
SELECT 'E-Barangay Portal database setup completed successfully!' as message,
       'Demo Accounts:' as demo_info,
       'Resident: john.doe@email.com / 123456' as resident_demo,
       'Admin: admin@barangay.gov.ph / admin123' as admin_demo;