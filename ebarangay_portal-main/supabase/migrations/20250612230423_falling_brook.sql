-- E-Barangay Resident Portal Database Setup
-- Import this file into phpMyAdmin to create the database and tables

-- Create database
CREATE DATABASE IF NOT EXISTS ebarangay_portal;
USE ebarangay_portal;

-- Create residents table
CREATE TABLE IF NOT EXISTS residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    address TEXT NOT NULL,
    phone VARCHAR(20),
    birth_date DATE,
    civil_status ENUM('single', 'married', 'widowed', 'separated') DEFAULT 'single',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- Insert sample data for testing (optional)
-- Sample resident
INSERT INTO residents (email, password, first_name, last_name, middle_name, address, phone, birth_date, civil_status) VALUES
('john.doe@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'Smith', '123 Main Street, Barangay Sample', '09123456789', '1990-01-15', 'single');

-- Sample requests (using resident ID 1)
INSERT INTO requests (type, purpose, resident_id, status, created_at) VALUES
('Barangay Clearance', 'Employment requirements', 1, 'approved', '2024-01-15 10:00:00'),
('Certificate of Indigency', 'Medical assistance application', 1, 'pending', '2024-01-20 14:30:00');

-- Sample blotter report
INSERT INTO blotter_reports (incident_type, incident_date, incident_time, location, description, complainant_id, respondent_name, respondent_address, status) VALUES
('Noise Complaint', '2024-01-10', '22:30:00', '456 Oak Street, Barangay Sample', 'Loud music disturbing the peace during late hours', 1, 'Jane Smith', '456 Oak Street, Barangay Sample', 'under_investigation');

-- Create indexes for better performance
CREATE INDEX idx_residents_email ON residents(email);
CREATE INDEX idx_requests_resident_id ON requests(resident_id);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_blotter_complainant_id ON blotter_reports(complainant_id);
CREATE INDEX idx_blotter_status ON blotter_reports(status);

-- Display success message
SELECT 'E-Barangay Portal database setup completed successfully!' as message;