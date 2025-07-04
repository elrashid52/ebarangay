-- E-Barangay Resident Portal Database Setup
-- Import this file into phpMyAdmin to create the database and tables

-- Create database
CREATE DATABASE IF NOT EXISTS ebarangay_portal;
USE ebarangay_portal;

-- Create residents table with comprehensive profile fields
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
    role ENUM('Resident', 'Admin', 'Barangay Official') DEFAULT 'Resident',
    status ENUM('Active', 'Deactivated', 'Pending Approval') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create document_uploads table for file attachments
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

-- Create request_types table
CREATE TABLE IF NOT EXISTS request_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    required_fields JSON,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    processing_days INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create requests table
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    resident_id INT NOT NULL,
    request_details JSON,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- Create blotter_reports table
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

-- Insert default request types
INSERT INTO request_types (name, description, required_fields, processing_fee, processing_days) VALUES
('Barangay Clearance', 'Certificate of good moral character and residence', '["purpose"]', 50.00, 3),
('Certificate of Indigency', 'Certificate for low-income residents', '["purpose", "family_income"]', 0.00, 2),
('Certificate of Residency', 'Proof of residence in the barangay', '["purpose", "years_of_residence"]', 30.00, 1),
('Business Permit', 'Permit to operate a business in the barangay', '["business_name", "business_type", "business_address"]', 200.00, 7),
('Cedula', 'Community Tax Certificate', '["purpose"]', 5.00, 1);

-- Insert sample data for testing
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

-- Sample requests
INSERT INTO requests (type, purpose, resident_id, status, created_at) VALUES
('Barangay Clearance', 'Employment requirements', 1, 'approved', '2024-01-15 10:00:00'),
('Certificate of Indigency', 'Medical assistance application', 1, 'pending', '2024-01-20 14:30:00');

-- Create indexes for better performance
CREATE INDEX idx_residents_email ON residents(email);
CREATE INDEX idx_residents_voter_status ON residents(voter_status);
CREATE INDEX idx_requests_resident_id ON requests(resident_id);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_blotter_complainant_id ON blotter_reports(complainant_id);
CREATE INDEX idx_document_uploads_resident_id ON document_uploads(resident_id);

-- Create uploads directory structure
-- Note: You'll need to create these directories manually in your XAMPP htdocs folder
-- uploads/
-- ├── profile_pictures/
-- ├── valid_ids/
-- ├── proof_of_billing/
-- └── cedula/

SELECT 'E-Barangay Portal database setup completed successfully!' as message;