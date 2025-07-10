<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Barangay Resident Portal</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Authentication Container -->
    <div id="authContainer" class="auth-container">
        <!-- Login Card -->
        <div id="loginCard" class="auth-card">
            <div class="auth-header">
                <div class="auth-logo-icon">🏛️</div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your E-Barangay account</p>
            </div>
            
            <form class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        <i class="input-icon">📧</i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="input-icon">🔒</i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
            
             <!-- Demo Accounts - Only show in login mode -->
                <div class="demo-accounts" id="demoAccounts">
                <div class="demo-title">Demo Accounts</div>
                <div class="demo-account">
                    <strong>Resident:</strong> john.doe@email.com / password<br>
                    <button type="button" class="btn btn-secondary" 
                            onclick="fillDemoAccount('john.doe@email.com', '123456')">
                        Use Resident Demo
                    </button>
                </div>
                <div class="demo-account" style="margin-top: 10px;">
                    <strong>Admin:</strong> admin@barangay.gov.ph / admin123<br>
                    <button type="button" class="btn btn-secondary" 
                <p>Need an account? <a href="#" onclick="showRegistrationForm()">Sign Up</a> | <a href="admin.php">Admin Portal</a> | <a href="staff.php">Staff Portal</a></p>
                        Use Admin Demo
                    </button>
                </div>
            </div> 
            
            <form class="auth-form" id="signupForm" style="display: none;">
                <div class="form-group">
                    <label for="signup_first_name">First Name</label>
                    <input type="text" id="signup_first_name" name="first_name" placeholder="Enter your first name" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_last_name">Last Name</label>
                    <input type="text" id="signup_last_name" name="last_name" placeholder="Enter your last name" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_email">Email Address</label>
                    <input type="email" id="signup_email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_birth_date">Birth Date</label>
                    <input type="date" id="signup_birth_date" name="birth_date">
                </div>
                
                <div class="form-group">
                    <label for="signup_phone">Phone Number</label>
                    <input type="tel" id="signup_phone" name="phone" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_password">Password</label>
                    <input type="password" id="signup_password" name="password" placeholder="Enter your password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="signup_confirm_password">Confirm Password</label>
                    <input type="password" id="signup_confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Sign Up</button>
            </form>

            <div class="auth-switch">
                <p id="authSwitchText">Don't have an account? <a href="#" onclick="toggleAuthForm()">Sign up here</a></p>
            </div>

            <!-- Sign Up Form -->
            <form id="signupForm" class="auth-form" style="display: none;">
                <div class="form-group">
                    <label for="signup_first_name">First name</label>
                    <input type="text" id="signup_first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_last_name">Last name</label>
                    <input type="text" id="signup_last_name" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_email">Email</label>
                    <input type="email" id="signup_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_birth_date">Birth date</label>
                    <input type="date" id="signup_birth_date" name="birth_date">
                </div>
                
                <div class="form-group">
                    <label for="signup_phone">Phone number</label>
                    <input type="tel" id="signup_phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_password">Password</label>
                    <input type="password" id="signup_password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="signup_confirm_password">Confirm password</label>
                    <input type="password" id="signup_confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">Sign Up</button>
                
                <div class="auth-switch">
                    <p>Already have an account? <a href="#" id="showLogin">Log in</a></p>
                </div>
            </form>
        </div>
        
        <!-- Register Card -->
        <div id="registerCard" class="auth-card" style="display: none;">
            <div class="auth-header">
                <div class="auth-logo-icon">🏛️</div>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join the E-Barangay community</p>
            </div>
            
            <form id="signUpForm" class="auth-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="first_name" placeholder="First name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name" placeholder="Last name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="middleName">Middle Name</label>
                        <input type="text" id="middleName" name="middle_name" placeholder="Middle name (optional)">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="birthDate">Birth Date</label>
                        <input type="date" id="birthDate" name="birth_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="civilStatus">Civil Status</label>
                        <select id="civilStatus" name="civil_status">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="registerEmail">Email Address</label>
                        <input type="email" id="registerEmail" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="registerPassword">Password</label>
                        <input type="password" id="registerPassword" name="password" placeholder="Create a password" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            
            <div class="auth-switch">
                <p>Already have an account? <a href="#" id="switchToLogin">Sign in here</a></p>
            </div>
        </div>
    </div>

    <!-- Main Application Container -->
    <div id="appContainer" class="app-layout" style="display: none;">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">🏛️</div>
                    <div>
                        <div>E-Barangay</div>
                        <div class="sidebar-subtitle">Resident Portal</div>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-page="dashboard">
                    <div class="nav-icon">📊</div>
                    Dashboard
                </a>
                <a href="#" class="nav-item" data-page="profile">
                    <div class="nav-icon">👤</div>
                    My Profile
                </a>
                <a href="#" class="nav-item" data-page="requests">
                    <div class="nav-icon">📄</div>
                    My Requests
                </a>
                <a href="#" class="nav-item" data-page="certificate">
                    <div class="nav-icon">📋</div>
                    Request Certificate
                </a>
                <a href="#" class="nav-item" data-page="blotter">
                    <div class="nav-icon">⚠️</div>
                    File Blotter
                </a>
                <a href="#" class="nav-item" id="signOutBtn">
                    <div class="nav-icon">🚪</div>
                    Sign Out
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Page -->
            <div id="dashboardPage" class="page">
                <div class="page-header">
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <span class="user-name">Resident</span></p>
                </div>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon total">📊</div>
                            <div class="card-content">
                                <h3>Total Requests</h3>
                                <div class="card-number" id="totalRequests">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon pending">⏳</div>
                            <div class="card-content">
                                <h3>Pending</h3>
                                <div class="card-number" id="pendingRequests">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon approved">✅</div>
                            <div class="card-content">
                                <h3>Approved</h3>
                                <div class="card-number" id="approvedRequests">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon rejected">❌</div>
                            <div class="card-content">
                                <h3>Rejected</h3>
                                <div class="card-number" id="rejectedRequests">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon blotter">⚠️</div>
                            <div class="card-content">
                                <h3>Blotter Reports</h3>
                                <div class="card-number" id="blotterReports">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="recent-requests">
                    <div class="section-header">
                        <h2>Recent Requests</h2>
                        <a href="#" class="btn btn-secondary" onclick="showPage('requests')">View All</a>
                    </div>
                    
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Certificate Type</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="recentRequestsBody">
                            <tr>
                                <td colspan="4" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profile Page -->
            <div id="profilePage" class="page" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle">Manage your personal information</p>
                </div>
                
                <div class="profile-header">
                    <div class="profile-avatar">
                        <img id="profilePicture" src="https://via.placeholder.com/120x120/4f46e5/ffffff?text=👤" alt="Profile Picture">
                        <div class="profile-upload">
                            <button type="button" class="btn-upload">📷 Change Photo</button>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <h2 id="profileName">Loading...</h2>
                        <p id="profileEmail">Loading...</p>
                        <div class="profile-status">
                            <span id="voterStatus" class="status-badge approved">NOT REGISTERED</span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-form-container">
                    <form id="profileForm">
                        <div class="form-section">
                            <h3 class="section-title">📋 Basic Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="profileFirstName">First Name *</label>
                                    <input type="text" id="profileFirstName" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="profileLastName">Last Name *</label>
                                    <input type="text" id="profileLastName" name="last_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="profileMiddleName">Middle Name</label>
                                    <input type="text" id="profileMiddleName" name="middle_name">
                                </div>
                                <div class="form-group">
                                    <label for="profileSex">Sex / Gender *</label>
                                    <select id="profileSex" name="sex" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="profileBirthDate">Date of Birth *</label>
                                    <input type="date" id="profileBirthDate" name="birth_date">
                                </div>
                                <div class="form-group">
                                    <label for="profileCivilStatus">Civil Status *</label>
                                    <select id="profileCivilStatus" name="civil_status" required>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                        <option value="Divorced">Divorced</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="profileCitizenship">Citizenship</label>
                                    <input type="text" id="profileCitizenship" name="citizenship" value="Filipino">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">🏠 Address and Residency</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="profileHouseNo">House No.</label>
                                    <input type="text" id="profileHouseNo" name="house_no">
                                </div>
                                <div class="form-group">
                                    <label for="profileLot">Lot</label>
                                    <input type="text" id="profileLot" name="lot">
                                </div>
                                <div class="form-group">
                                    <label for="profileStreet">Street</label>
                                    <input type="text" id="profileStreet" name="street">
                                </div>
                                <div class="form-group">
                                    <label for="profilePurok">Purok</label>
                                    <input type="text" id="profilePurok" name="purok">
                                </div>
                                <div class="form-group">
                                    <label for="profileBarangay">Barangay</label>
                                    <input type="text" id="profileBarangay" name="barangay" value="Sample Barangay" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="profileCity">City / Municipality</label>
                                    <input type="text" id="profileCity" name="city">
                                </div>
                                <div class="form-group">
                                    <label for="profileProvince">Province</label>
                                    <input type="text" id="profileProvince" name="province">
                                </div>
                                <div class="form-group">
                                    <label for="profileZipCode">ZIP Code</label>
                                    <input type="text" id="profileZipCode" name="zip_code">
                                </div>
                                <div class="form-group">
                                    <label for="profileYearsOfResidency">Years of Residency</label>
                                    <input type="number" id="profileYearsOfResidency" name="years_of_residency" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">📞 Contact Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="profileMobileNumber">Mobile Number *</label>
                                    <input type="tel" id="profileMobileNumber" name="mobile_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="profileLandlineNumber">Landline Number</label>
                                    <input type="tel" id="profileLandlineNumber" name="landline_number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">🗳️ Voter Information</h3>
                            <div class="voter-grid">
                                <div class="form-group">
                                    <label for="voterStatusSelect">Voter Status</label>
                                    <select id="voterStatusSelect" name="voter_status">
                                        <option value="Not Registered">Not Registered</option>
                                        <option value="Registered">Registered</option>
                                    </select>
                                </div>
                                <div class="form-group" id="voterIdGroup" style="display: none;">
                                    <label for="voterId">Voter ID Number</label>
                                    <input type="text" id="voterId" name="voter_id" placeholder="Enter if registered">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">🆔 Government IDs</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="profileValidIdType">Valid ID Type</label>
                                    <select id="profileValidIdType" name="valid_id_type">
                                        <option value="">Select ID Type</option>
                                        <option value="Driver's License">Driver's License</option>
                                        <option value="SSS ID">SSS ID</option>
                                        <option value="PhilHealth ID">PhilHealth ID</option>
                                        <option value="TIN ID">TIN ID</option>
                                        <option value="Postal ID">Postal ID</option>
                                        <option value="Voter's ID">Voter's ID</option>
                                        <option value="Senior Citizen ID">Senior Citizen ID</option>
                                        <option value="PWD ID">PWD ID</option>
                                        <option value="UMID">UMID</option>
                                        <option value="Passport">Passport</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="profileValidIdNumber">Valid ID Number</label>
                                    <input type="text" id="profileValidIdNumber" name="valid_id_number">
                                </div>
                                <div class="form-group">
                                    <label for="profileBarangayIdNumber">Barangay ID Number</label>
                                    <input type="text" id="profileBarangayIdNumber" name="barangay_id_number">
                                </div>
                                <div class="form-group">
                                    <label for="profileCedulaNumber">Cedula Number</label>
                                    <input type="text" id="profileCedulaNumber" name="cedula_number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">🚨 Emergency Contact</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="profileEmergencyContactName">Contact Name</label>
                                    <input type="text" id="profileEmergencyContactName" name="emergency_contact_name">
                                </div>
                                <div class="form-group">
                                    <label for="profileEmergencyContactRelationship">Relationship</label>
                                    <input type="text" id="profileEmergencyContactRelationship" name="emergency_contact_relationship">
                                </div>
                                <div class="form-group">
                                    <label for="profileEmergencyContactNumber">Contact Number</label>
                                    <input type="tel" id="profileEmergencyContactNumber" name="emergency_contact_number">
                                </div>
                                <div class="form-group">
                                    <label for="profileEmergencyContactAddress">Address</label>
                                    <textarea id="profileEmergencyContactAddress" name="emergency_contact_address" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">💼 Employment Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="profileEmploymentStatus">Employment Status</label>
                                    <select id="profileEmploymentStatus" name="employment_status">
                                        <option value="Unemployed">Unemployed</option>
                                        <option value="Employed">Employed</option>
                                        <option value="Self-employed">Self-employed</option>
                                        <option value="Student">Student</option>
                                        <option value="Retired">Retired</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="profileOccupation">Occupation</label>
                                    <input type="text" id="profileOccupation" name="occupation">
                                </div>
                                <div class="form-group">
                                    <label for="profilePlaceOfWork">Place of Work</label>
                                    <input type="text" id="profilePlaceOfWork" name="place_of_work">
                                </div>
                                <div class="form-group">
                                    <label for="profileMonthlyIncomeRange">Monthly Income Range</label>
                                    <select id="profileMonthlyIncomeRange" name="monthly_income_range">
                                        <option value="">Select Income Range</option>
                                        <option value="Below 10,000">Below ₱10,000</option>
                                        <option value="10,000 - 25,000">₱10,000 - ₱25,000</option>
                                        <option value="25,000 - 50,000">₱25,000 - ₱50,000</option>
                                        <option value="50,000 - 75,000">₱50,000 - ₱75,000</option>
                                        <option value="75,000 - 100,000">₱75,000 - ₱100,000</option>
                                        <option value="Above 100,000">Above ₱100,000</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Requests Page -->
            <div id="requestsPage" class="page" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">My Requests</h1>
                    <p class="page-subtitle">Track the status of your certificate requests and download approved documents</p>
                </div>
                
                <div class="requests-data-grid">
                    <div class="data-grid-header">
                        <div class="data-grid-controls">
                            <div class="filter-dropdown">
                                <select class="filter-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="ready_for_pickup">Ready for Pickup</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="data-grid-container">
                        <table class="data-grid-table">
                            <thead>
                                <tr>
                                    <th class="col-certificate-type">CERTIFICATE TYPE</th>
                                    <th class="col-purpose">PURPOSE</th>
                                    <th class="col-status">STATUS</th>
                                    <th class="col-fee">FEE</th>
                                    <th class="col-date">REQUESTED DATE</th>
                                    <th class="col-actions">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="requestsDataGridBody">
                                <tr>
                                    <td colspan="6" class="text-center">Loading requests...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="requestsEmptyState" class="data-grid-empty" style="display: none;">
                        <div class="empty-icon">📄</div>
                        <h3>No Requests Found</h3>
                        <p>You haven't submitted any certificate requests yet.</p>
                        <button class="btn btn-primary" onclick="showPage('certificate')">Request Certificate</button>
                    </div>
                </div>
            </div>

            <!-- Certificate Request Page -->
            <div id="certificatePage" class="page" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">Request Certificate</h1>
                    <p class="page-subtitle">Submit a request for barangay documents</p>
                </div>
                
                <div class="certificate-request-container">
                    <div class="certificate-form-card">
                        <div class="certificate-form-header">
                            <div class="form-icon">📋</div>
                            <div class="form-title-section">
                                <h2>Certificate Request Form</h2>
                                <p>Select certificate type and provide required information</p>
                            </div>
                        </div>
                        
                        <form id="certificateRequestForm" class="multi-step-form">
                            <!-- Step 1: Select Certificate Type -->
                            <div id="step1" class="form-step active">
                                <div class="step-header">
                                    <h3>Step 1: Select Certificate Type</h3>
                                    <p>Choose the type of certificate you need</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="certificateType">Certificate Type *</label>
                                    <select id="certificateType" name="certificate_type" required>
                                        <option value="">Select certificate type</option>
                                    </select>
                                </div>
                                
                                <div id="certificateInfo" class="certificate-info-card" style="display: none;">
                                    <div class="info-header">
                                        <span>ℹ️</span>
                                        <span id="certificateInfoTitle">Certificate Information</span>
                                    </div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">💰 Fee</div>
                                            <span id="certificateFee">₱0.00</span>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">⏱️ Processing Time</div>
                                            <span id="certificateProcessingTime">1 business day</span>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">🚚 Delivery</div>
                                            <span id="certificateDelivery">Downloadable PDF</span>
                                        </div>
                                    </div>
                                    <div class="info-purpose">
                                        <div class="info-label">📝 Purpose</div>
                                        <p id="certificatePurpose">Certificate description</p>
                                    </div>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-primary btn-next" disabled id="step1NextBtn" onclick="nextStep()">Next: Upload Documents</button>
                                </div>
                            </div>
                            
                            <!-- Step 2: Upload Documents -->
                            <div id="step2" class="form-step">
                                <div class="step-header">
                                    <h3>Step 2: Upload Required Documents</h3>
                                    <p>Please upload all required documents for your certificate request</p>
                                </div>
                                
                                <div id="documentUploads" class="document-uploads">
                                    <!-- Document upload fields will be populated here -->
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep()">Previous</button>
                                    <button type="button" class="btn btn-primary" disabled id="step2NextBtn" onclick="nextStep()">Next: Payment</button>
                                </div>
                            </div>
                            
                            <!-- Step 3: Payment and Submit -->
                            <div id="step3" class="form-step">
                                <div class="step-header">
                                    <h3>Step 3: Payment and Submit</h3>
                                    <p>Choose your payment method and complete the request</p>
                                </div>
                                
                                <div class="payment-section">
                                    <div class="form-group">
                                        <label>Payment Method *</label>
                                        <div class="payment-options">
                                            <div class="payment-option">
                                                <input type="radio" id="gcash" name="payment_method" value="gcash" checked>
                                                <label for="gcash" class="payment-label">
                                                    <div class="payment-icon">💳</div>
                                                    <div class="payment-details">
                                                        <div class="payment-name">GCash / PayMaya</div>
                                                        <div class="payment-info">
                                                            <div>Send payment to: <strong>09123456789</strong> (Barangay Sample)</div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-option">
                                                <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                                <label for="bank_transfer" class="payment-label">
                                                    <div class="payment-icon">🏦</div>
                                                    <div class="payment-details">
                                                        <div class="payment-name">Bank Transfer</div>
                                                        <div class="payment-info">
                                                            <div><strong>Bank:</strong> BPI (Bank of the Philippine Islands)</div>
                                                            <div><strong>Account Number:</strong> 1234-5678-90</div>
                                                            <div><strong>Account Name:</strong> Barangay Sample</div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="paymentReferenceGroup" class="form-group">
                                        <label for="paymentReference">GCash/PayMaya Reference Number *</label>
                                        <input type="text" id="paymentReference" name="payment_reference" placeholder="Enter reference number" required>
                                    </div>
                                    
                                    <div id="receiptUploadGroup" class="form-group" style="display: none;">
                                        <label for="receiptUpload">Upload Payment Receipt *</label>
                                        <div class="file-upload-area" onclick="document.getElementById('receiptUpload').click()">
                                            <div class="upload-placeholder">
                                                <div class="upload-icon">📁</div>
                                                <div class="upload-text">
                                                    <div class="upload-title">Click to upload receipt</div>
                                                    <div class="upload-subtitle">JPG, PNG, PDF (Max 5MB)</div>
                                                </div>
                                            </div>
                                            <div class="upload-success" style="display: none;">
                                                <div class="success-icon">✅</div>
                                                <div class="success-text">
                                                    <div class="success-title">Receipt uploaded</div>
                                                    <div class="success-filename"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="file" id="receiptUpload" name="payment_receipt" accept=".jpg,.jpeg,.png,.pdf" style="display: none;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="requestPurpose">Purpose *</label>
                                        <textarea id="requestPurpose" name="purpose" placeholder="Please specify the purpose of this certificate request..." required rows="3"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="additionalNotes">Additional Notes (Optional)</label>
                                        <textarea id="additionalNotes" name="additional_notes" placeholder="Any additional information..." rows="3"></textarea>
                                    </div>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep()">Previous</button>
                                    <button type="submit" class="btn btn-primary" id="submitRequestBtn">🚀 Submit Request</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Blotter Page -->
            <div id="blotterPage" class="page" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">File Blotter</h1>
                    <p class="page-subtitle">Report incidents to the barangay</p>
                </div>
                
                <div class="form-container">
                    <div class="empty-state">
                        <div class="empty-icon">⚠️</div>
                        <h3>Blotter Reporting</h3>
                        <p>This feature is coming soon. You will be able to file incident reports here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="requestDetailsModal" class="modal">
        <div class="modal-content request-details-modal">
            <div class="modal-header">
                <h2 id="requestDetailsTitle">Request Details</h2>
                <button class="modal-close" onclick="closeRequestDetailsModal()">✕</button>
            </div>
            <div class="request-details-content">
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Certificate Type</label>
                        <span id="detailCertificateType">-</span>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <span id="detailStatus" class="status-badge">-</span>
                    </div>
                    <div class="detail-item">
                        <label>Fee</label>
                        <span id="detailFee">-</span>
                    </div>
                    <div class="detail-item">
                        <label>Delivery Method</label>
                        <span id="detailDeliveryMethod">🚚 Home Delivery (3-5 business days)</span>
                    </div>
                    <div class="detail-item">
                        <label>Request Date</label>
                        <span id="detailRequestDate">-</span>
                    </div>
                    <div class="detail-item" id="detailApprovedDateItem" style="display: none;">
                        <label>Approved Date</label>
                        <span id="detailApprovedDate">-</span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Purpose</h4>
                    <div class="purpose-content" id="detailPurpose">-</div>
                </div>
                
                <div class="detail-section" id="detailPaymentSection" style="display: none;">
                    <h4>Payment Information</h4>
                    <div class="payment-info">
                        <div class="payment-method">
                            <span class="payment-icon">💳</span>
                            <div class="payment-details">
                                <div class="payment-type">GCash / PayMaya</div>
                                <div class="payment-ref" id="detailPaymentRef">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section" id="detailRejectionSection" style="display: none;">
                    <div class="rejection-alert">
                        <div class="rejection-header">
                            <span class="rejection-icon">❌</span>
                            <span class="rejection-title">Request Rejected</span>
                        </div>
                        <div class="rejection-message" id="detailRejectionMessage">-</div>
                    </div>
                
                </div>
                
                <div class="detail-section" id="detailBarangayIdSection" style="display: none;">
                    <h4>🚚 Barangay ID Delivery Information</h4>
                    <div id="barangayIdDeliveryInfo">
                        <!-- Delivery information will be populated by JavaScript -->
                    </div>
                </div>
                
                <div class="detail-actions">
                    <button id="detailDownloadBtn" class="btn download-btn" style="display: none;">
                        📥 Download Certificate
                    </button>
                    <button class="btn btn-secondary" onclick="closeRequestDetailsModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resubmit Request Modal -->
    <div id="resubmitModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📤 Resubmit Request</h2>
                <button class="modal-close" onclick="closeResubmitModal()">✕</button>
            </div>
            <form id="resubmitForm">
                <input type="hidden" id="resubmitRequestId" name="request_id">
                
                <div class="form-group">
                    <label for="resubmitPurpose">Purpose *</label>
                    <textarea id="resubmitPurpose" name="purpose" placeholder="Please specify the purpose of this certificate request..." required rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="resubmitNotes">Additional Notes</label>
                    <textarea id="resubmitNotes" name="additional_notes" placeholder="Any additional information or corrections..." rows="3"></textarea>
                </div>
                
                <div class="form-section">
                    <h4>📋 Upload Required Documents</h4>
                    <p style="color: #6b7280; margin-bottom: 20px;">Please upload all required documents again. Make sure they are clear and valid.</p>
                    
                    <div class="document-uploads" id="resubmitDocuments">
                        <div class="document-upload-group">
                            <label for="resubmit_valid_id">Valid Government-issued ID *</label>
                            <div class="file-upload-area" onclick="document.getElementById('resubmit_valid_id').click()">
                                <div class="upload-placeholder">
                                    <div class="upload-icon">📁</div>
                                    <div class="upload-text">
                                        <div class="upload-title">Click to upload Valid ID</div>
                                        <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="resubmit_valid_id" name="valid_id" accept=".pdf,.jpg,.jpeg,.png" style="display: none;" onchange="handleResubmitFileUpload(this, 'valid_id')">
                        </div>
                        
                        <div class="document-upload-group">
                            <label for="resubmit_proof_billing">Proof of Billing / Proof of Residency *</label>
                            <div class="file-upload-area" onclick="document.getElementById('resubmit_proof_billing').click()">
                                <div class="upload-placeholder">
                                    <div class="upload-icon">📁</div>
                                    <div class="upload-text">
                                        <div class="upload-title">Click to upload Proof of Billing</div>
                                        <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="resubmit_proof_billing" name="proof_billing" accept=".pdf,.jpg,.jpeg,.png" style="display: none;" onchange="handleResubmitFileUpload(this, 'proof_billing')">
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeResubmitModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">🚀 Resubmit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentUser = null;
        let isSignUpMode = false;
        
        // Check session on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkSession();
        });
        
        // Toggle between login and signup forms
        function toggleAuthForm() {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const authTitle = document.querySelector('.auth-title');
            const authSubtitle = document.querySelector('.auth-subtitle');
            const authSwitchText = document.getElementById('authSwitchText');
            const demoAccounts = document.getElementById('demoAccounts');
            
            if (isSignUpMode) {
                // Switch to login
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                authTitle.textContent = 'Welcome Back';
                authSubtitle.textContent = 'Sign in to your E-Barangay account';
                authSwitchText.innerHTML = 'Don\'t have an account? <a href="#" onclick="toggleAuthForm()">Sign up here</a>';
                demoAccounts.style.display = 'block'; // Show demo accounts in login
                isSignUpMode = false;
            } else {
                // Switch to signup
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
                authTitle.textContent = 'Create Account';
                authSubtitle.textContent = 'Join the E-Barangay community';
                authSwitchText.innerHTML = 'Already have an account? <a href="#" onclick="toggleAuthForm()">Sign in here</a>';
                demoAccounts.style.display = 'none'; // Hide demo accounts in signup
                isSignUpMode = true;
            }
        }
        
        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                showMessage('Please fill in all fields', 'error');
                return;
            }
            
            login(email, password);
        });
        
        // Handle signup form submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            // Validate passwords match
            if (password !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }
            
            // Validate password length
            if (password.length < 6) {
                showMessage('Password must be at least 6 characters long', 'error');
                return;
            }
            
            register(formData);
        });
        
        // Register function
        function register(formData) {
            formData.append('action', 'register');
            
            fetch('api/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Registration successful! Please sign in with your new account.', 'success');
                    // Reset form and switch to login
                    document.getElementById('signupForm').reset();
                    setTimeout(() => {
                        toggleAuthForm();
                    }, 2000);
                } else {
                    showMessage(data.message || 'Registration failed', 'error');
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                showMessage('Registration failed. Please try again.', 'error');
            });
        }
        
        // Login function
        function login(email, password) {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);
            
            fetch('api/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentUser = data.user;
                    showMessage('Login successful!', 'success');
                    
                    // Redirect based on user type
                    setTimeout(() => {
                        if (data.redirect === 'admin') {
                            window.location.href = 'admin.php';
                        } else {
                            showDashboard();
                        }
                    }, 1000);
                } else {
                    showMessage(data.message || 'Login failed', 'error');
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                showMessage('Login failed. Please try again.', 'error');
            });
        }
        
        // Use demo account
        function useDemoAccount(type) {
            if (type === 'resident') {
                document.getElementById('email').value = 'john.doe@email.com';
                document.getElementById('password').value = 'password';
            } else if (type === 'admin') {
                document.getElementById('email').value = 'admin@barangay.gov.ph';
                document.getElementById('password').value = 'password';
            }
        }
        
        // Toggle between login and signup forms
        document.getElementById('showSignup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('signupForm').style.display = 'block';
            document.querySelector('.auth-title').textContent = 'Sign up';
            document.querySelector('.auth-subtitle').textContent = 'Create your account to access barangay services';
        });
        
        document.getElementById('showLogin').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('signupForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
            document.querySelector('.auth-title').textContent = 'Welcome back';
            document.querySelector('.auth-subtitle').textContent = 'Sign in to your account to continue';
        });
        
        // Handle signup form submission
        document.getElementById('signupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            // Validate password confirmation
            if (password !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'register',
                        first_name: formData.get('first_name'),
                        last_name: formData.get('last_name'),
                        email: formData.get('email'),
                        phone: formData.get('phone'),
                        birth_date: formData.get('birth_date'),
                        password: password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Account created successfully! Please log in.', 'success');
                    // Switch back to login form
                    document.getElementById('showLogin').click();
                    // Clear signup form
                    document.getElementById('signupForm').reset();
                } else {
                    showMessage(data.message || 'Registration failed', 'error');
                }
            } catch (error) {
                console.error('Signup error:', error);
                showMessage('An error occurred during registration', 'error');
            }
        });
        
        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'login',
                        email: formData.get('email'),
                        password: formData.get('password')
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentUser = data.user;
                    showApp();
                    loadDashboard();
                } else {
                    showMessage(data.message || 'Login failed', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showMessage('An error occurred during login', 'error');
            }
        });
        
        // Check if user is already logged in
        checkSession();
        
        async function checkSession() {
            try {
                const response = await fetch('api/auth.php?action=check_session');
                const data = await response.json();
                
                if (data.success && data.user) {
                    currentUser = data.user;
                    showApp();
                    loadDashboard();
                } else {
                    showAuth();
                }
            } catch (error) {
                console.error('Session check error:', error);
                showMessage(data.message || 'Session check failed', 'error');
            }
        }
        
        // Demo account functions
        function useResidentDemo() {
            const email = 'john.doe@email.com';
            const password = 'password';
            
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Automatically submit the login form
            login();
        }
        
        function useAdminDemo() {
            const email = 'admin@barangay.gov.ph';
            const password = 'password';
            
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Automatically submit the login form
            login();
        }
        
        function useStaffDemo() {
            const email = 'staff@barangay.gov.ph';
            const password = 'password';
            
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Automatically submit the login form
            login();
        }
        
        function showMessage(message, type) {
            // Remove existing messages
            const existingMessage = document.querySelector('.auth-message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Create new message
            const messageDiv = document.createElement('div');
            messageDiv.className = `auth-message ${type}`;
            messageDiv.textContent = message;
            
            // Insert message before the active form
            const activeForm = document.getElementById('loginForm').style.display !== 'none' ? 
                document.getElementById('loginForm') : document.getElementById('signupForm');
            activeForm.parentNode.insertBefore(messageDiv, activeForm);
            
            // Auto-remove success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.remove();
                    }
                }, 5000);
            }
        }
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>