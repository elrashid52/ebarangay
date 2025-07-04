# E-Barangay Resident Portal - PHP MySQL Version

A complete barangay management system for residents to request certificates, file blotter reports, and manage their profiles. Built with PHP, MySQL, HTML, CSS, and JavaScript for XAMPP.

## Features

- **Resident Authentication**: Secure registration and login system
- **Certificate Requests**: Request various barangay certificates
- **Request Tracking**: Monitor status of submitted requests
- **Dashboard Analytics**: View statistics and recent activity
- **Profile Management**: Update personal information
- **Blotter Reporting**: File incident reports (coming soon)
- **Responsive Design**: Works on desktop and mobile devices

## Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Setup Instructions

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the SQL file: `config/setup.sql`
   - Or manually run the SQL commands to create the database and tables

3. **Deploy Application**
   - Copy all project files to your XAMPP htdocs directory
   - Example: `C:\xampp\htdocs\ebarangay-portal\`

4. **Access Application**
   - Open your browser and go to: `http://localhost/ebarangay-portal/`

## File Structure

```
ebarangay-portal/
├── api/
│   ├── auth.php          # Authentication endpoints
│   └── requests.php      # Request management endpoints
├── assets/
│   ├── css/
│   │   └── styles.css    # Application styles
│   └── js/
│       └── app.js        # Frontend JavaScript
├── classes/
│   ├── Resident.php      # Resident model and operations
│   └── Request.php       # Request model and operations
├── config/
│   ├── database.php      # Database connection
│   └── setup.sql         # Database schema
├── index.php             # Main application page
└── README.md
```

## Database Schema

### Residents Table
- `id` - Primary key
- `email` - Resident email (unique)
- `password` - Hashed password
- `first_name` - First name
- `last_name` - Last name
- `middle_name` - Middle name (optional)
- `address` - Complete address
- `phone` - Phone number
- `birth_date` - Date of birth
- `civil_status` - Civil status (single, married, widowed, separated)
- `created_at` - Registration timestamp

### Requests Table
- `id` - Primary key
- `type` - Request type (Barangay Clearance, Certificate of Indigency, etc.)
- `purpose` - Purpose of the request
- `status` - Request status (pending, approved, rejected)
- `resident_id` - Foreign key to residents table
- `request_details` - Additional request details (JSON)
- `admin_notes` - Administrator notes
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp
- `processed_at` - Processing timestamp

### Request Types Table
- `id` - Primary key
- `name` - Request type name
- `description` - Description
- `required_fields` - Required fields (JSON)
- `processing_fee` - Processing fee
- `processing_days` - Processing time in days
- `is_active` - Active status

### Blotter Reports Table
- `id` - Primary key
- `incident_type` - Type of incident
- `incident_date` - Date of incident
- `incident_time` - Time of incident
- `location` - Location of incident
- `description` - Incident description
- `complainant_id` - Foreign key to residents table
- `respondent_name` - Name of respondent
- `respondent_address` - Address of respondent
- `status` - Report status
- `admin_notes` - Administrator notes

## Available Certificate Types

1. **Barangay Clearance** - ₱50.00 (3 days processing)
2. **Certificate of Indigency** - Free (2 days processing)
3. **Certificate of Residency** - ₱30.00 (1 day processing)
4. **Business Permit** - ₱200.00 (7 days processing)
5. **Cedula** - ₱5.00 (1 day processing)

## API Endpoints

### Authentication (`api/auth.php`)
- `POST` - Register new resident
- `POST` - Login resident
- `POST` - Logout resident
- `POST` - Check session status

### Requests (`api/requests.php`)
- `GET` - Get all resident requests
- `POST` - Create new request
- `GET` - Get request statistics
- `GET` - Get available request types

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with HTML escaping
- Session-based authentication
- Resident data isolation (residents can only access their own data)

## Usage

1. **Registration**: Create a new account with complete personal information
2. **Login**: Sign in with your email and password
3. **Dashboard**: View your request statistics and recent activity
4. **Request Certificates**: Submit new certificate requests with purpose
5. **Track Requests**: Monitor the status of your submitted requests
6. **Profile Management**: Update your personal information (coming soon)
7. **File Blotter**: Report incidents to the barangay (coming soon)

## Request Status Types

- **Pending**: Request has been submitted and is awaiting review
- **Approved**: Request has been approved and certificate is ready
- **Rejected**: Request has been rejected with admin notes

## Customization

- **Styling**: Modify `assets/css/styles.css` for custom appearance
- **Request Types**: Add new certificate types in the database
- **Processing Fees**: Update fees and processing times in request_types table
- **Features**: Extend functionality by adding new API endpoints and frontend features

## Troubleshooting

- **Database Connection Issues**: Check MySQL service is running and credentials in `config/database.php`
- **Permission Errors**: Ensure proper file permissions for the web server
- **Session Issues**: Check PHP session configuration in XAMPP
- **JavaScript Errors**: Check browser console for debugging information

## Browser Compatibility

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers

## Future Enhancements

- Profile management system
- Blotter reporting functionality
- Document upload capabilities
- Email notifications
- Payment integration
- Admin panel for barangay officials
- Mobile app version

This application provides a modern, user-friendly interface for barangay residents to interact with their local government services digitally.