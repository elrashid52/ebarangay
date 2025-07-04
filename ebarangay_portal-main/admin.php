<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Barangay Admin Portal</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Admin Dashboard Container (No separate login page) -->
    <div id="adminDashboardContainer" class="admin-dashboard-container">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <div class="admin-sidebar-logo">
                    <div class="admin-sidebar-logo-icon">🏛️</div>
                    <div>
                        <div>E-Barangay</div>
                        <div class="admin-sidebar-subtitle">Admin Portal</div>
                    </div>
                </div>
            </div>
            
            <nav class="admin-sidebar-nav">
                <a href="#" class="admin-nav-item active" data-admin-page="dashboard">
                    <div class="admin-nav-icon">📊</div>
                    Dashboard
                </a>
                <a href="#" class="admin-nav-item" data-admin-page="residents">
                    <div class="admin-nav-icon">👥</div>
                    Manage Residents
                </a>
                <a href="#" class="admin-nav-item" data-admin-page="requests">
                    <div class="admin-nav-icon">📄</div>
                    Certificate Requests
                </a>
                <a href="#" class="admin-nav-item" data-admin-page="activities">
                    <div class="admin-nav-icon">📋</div>
                    Report Activities
                </a>
                <a href="#" class="admin-nav-item" data-admin-page="blotter">
                    <div class="admin-nav-icon">⚠️</div>
                    Blotter Reports
                </a>
                <a href="#" class="admin-nav-item" data-admin-page="users">
                    <div class="admin-nav-icon">👤</div>
                    Admin Users
                </a>
                <a href="#" class="admin-nav-item" onclick="adminSignOut()">
                    <div class="admin-nav-icon">🚪</div>
                    Sign Out
                </a>
            </nav>
        </div>

        <!-- Admin Main Content -->
        <div class="admin-main-content">
            <!-- Dashboard Page -->
            <div id="adminDashboardPage" class="admin-page">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">Admin Dashboard</h1>
                    <p class="admin-page-subtitle">Overview of barangay operations and activities</p>
                </div>
                
                <div class="admin-dashboard-grid">
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon total-residents">👥</div>
                            <div class="admin-card-content">
                                <h3>Total Residents</h3>
                                <div class="admin-card-number" id="adminTotalResidents">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon pending-requests">⏳</div>
                            <div class="admin-card-content">
                                <h3>Pending Requests</h3>
                                <div class="admin-card-number" id="adminPendingRequests">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon approved-today">✅</div>
                            <div class="admin-card-content">
                                <h3>Approved Today</h3>
                                <div class="admin-card-number" id="adminApprovedToday">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon open-blotters">⚠️</div>
                            <div class="admin-card-content">
                                <h3>Open Blotters</h3>
                                <div class="admin-card-number" id="adminOpenBlotters">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon total-requests">📄</div>
                            <div class="admin-card-content">
                                <h3>Total Requests</h3>
                                <div class="admin-card-number" id="adminTotalRequests">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon rejected">❌</div>
                            <div class="admin-card-content">
                                <h3>Rejected</h3>
                                <div class="admin-card-number" id="adminRejected">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="admin-dashboard-tables">
                    <div class="admin-recent-requests">
                        <div class="admin-section-header">
                            <h2>Recent Requests</h2>
                            <a href="#" class="admin-btn admin-btn-secondary" onclick="showAdminPage('requests')">View All</a>
                        </div>
                        
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>RESIDENT</th>
                                    <th>TYPE</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                </tr>
                            </thead>
                            <tbody id="adminRecentRequestsBody">
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #64748b;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="admin-recent-blotters">
                        <div class="admin-section-header">
                            <h2>Recent Blotter Reports</h2>
                            <a href="#" class="admin-btn admin-btn-secondary" onclick="showAdminPage('blotter')">View All</a>
                        </div>
                        
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>COMPLAINANT</th>
                                    <th>INCIDENT</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                </tr>
                            </thead>
                            <tbody id="adminRecentBlottersBody">
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #64748b;">No blotter reports</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Residents Management Page -->
            <div id="adminResidentsPage" class="admin-page" style="display: none;">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">Manage Residents</h1>
                    <p class="admin-page-subtitle">View and manage resident information</p>
                    <button class="admin-btn admin-btn-primary" onclick="openAddResidentModal()">
                        ➕ Add Resident
                    </button>
                </div>
                
                <div class="admin-data-grid">
                    <div class="admin-data-grid-header">
                        <div class="admin-search-bar">
                            <input type="text" id="residentsSearch" placeholder="Search residents..." class="admin-search-input">
                        </div>
                        <div class="admin-filter-controls">
                            <select id="residentsStatusFilter" class="admin-filter-select">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Pending Approval">Pending Approval</option>
                                <option value="Deactivated">Deactivated</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="admin-table-container">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>NAME</th>
                                    <th>CONTACT INFO</th>
                                    <th>ADDRESS</th>
                                    <th>STATUS</th>
                                    <th>REGISTERED</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="adminResidentsTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b;">Loading residents...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Requests Management Page -->
            <div id="adminRequestsPage" class="admin-page" style="display: none;">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">Certificate Requests</h1>
                    <p class="admin-page-subtitle">Review and process certificate requests</p>
                </div>
                
                <div class="admin-data-grid">
                    <div class="admin-data-grid-header">
                        <div class="admin-search-bar">
                            <input type="text" id="requestsSearch" placeholder="Search requests..." class="admin-search-input">
                        </div>
                        <div class="admin-filter-controls">
                            <select id="requestsStatusFilter" class="admin-filter-select">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="ready_for_pickup">Ready for Pickup</option>
                            </select>
                            <select id="requestsTypeFilter" class="admin-filter-select">
                                <option value="">All Types</option>
                                <option value="Barangay Clearance">Barangay Clearance</option>
                                <option value="Certificate of Indigency">Certificate of Indigency</option>
                                <option value="Certificate of Residency">Certificate of Residency</option>
                                <option value="Barangay Business Clearance">Business Clearance</option>
                                <option value="Barangay ID">Barangay ID</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="admin-table-container">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>RESIDENT</th>
                                    <th>CERTIFICATE TYPE</th>
                                    <th>PURPOSE</th>
                                    <th>STATUS</th>
                                    <th>FEE</th>
                                    <th>DATE</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="adminRequestsTableBody">
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b;">Loading requests...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Activities Report Page -->
            <div id="adminActivitiesPage" class="admin-page" style="display: none;">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">Report Activities</h1>
                    <p class="admin-page-subtitle">Monitor system activities and user actions</p>
                    <button class="admin-btn admin-btn-primary" onclick="printActivityReport()">
                        🖨️ Print Report
                    </button>
                </div>
                
                <!-- Activity Statistics -->
                <div class="admin-dashboard-grid" style="margin-bottom: 30px;">
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon admin-activities">👨‍💼</div>
                            <div class="admin-card-content">
                                <h3>Admin Activities</h3>
                                <div class="admin-card-number" id="totalAdminActivities">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon user-activities">👥</div>
                            <div class="admin-card-content">
                                <h3>User Activities</h3>
                                <div class="admin-card-number" id="totalUserActivities">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon admin-logins">🔐</div>
                            <div class="admin-card-content">
                                <h3>Admin Logins</h3>
                                <div class="admin-card-number" id="totalAdminLogins">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-dashboard-card">
                        <div class="admin-card-header">
                            <div class="admin-card-icon user-logins">🚪</div>
                            <div class="admin-card-content">
                                <h3>User Logins</h3>
                                <div class="admin-card-number" id="totalUserLogins">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Filters -->
                <div class="admin-data-grid">
                    <div class="admin-data-grid-header">
                        <div class="admin-search-bar">
                            <input type="text" id="activitiesSearch" placeholder="Search activities..." class="admin-search-input">
                        </div>
                        <div class="admin-filter-controls">
                            <input type="date" id="activitiesDateFrom" class="admin-filter-select" title="From Date">
                            <input type="date" id="activitiesDateTo" class="admin-filter-select" title="To Date">
                            <select id="activitiesTypeFilter" class="admin-filter-select">
                                <option value="all">All Activities</option>
                                <option value="admin">Admin Only</option>
                                <option value="user">Users Only</option>
                            </select>
                            <select id="activitiesActionFilter" class="admin-filter-select">
                                <option value="">All Actions</option>
                                <option value="login">Login</option>
                                <option value="logout">Logout</option>
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button class="admin-btn admin-btn-secondary" onclick="filterActivities()">
                                🔍 Filter
                            </button>
                        </div>
                    </div>
                    
                    <div class="admin-table-container">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>TIMESTAMP</th>
                                    <th>USER/ADMIN</th>
                                    <th>TYPE</th>
                                    <th>ACTION</th>
                                    <th>TARGET</th>
                                    <th>DETAILS</th>
                                    <th>IP ADDRESS</th>
                                </tr>
                            </thead>
                            <tbody id="adminActivitiesTableBody">
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b;">Loading activities...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="admin-pagination" id="activitiesPagination" style="display: none;">
                        <button class="admin-btn admin-btn-secondary" onclick="loadActivitiesPage(currentActivitiesPage - 1)" id="prevActivitiesBtn">
                            ← Previous
                        </button>
                        <span id="activitiesPageInfo">Page 1 of 1</span>
                        <button class="admin-btn admin-btn-secondary" onclick="loadActivitiesPage(currentActivitiesPage + 1)" id="nextActivitiesBtn">
                            Next →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Blotter Reports Page -->
            <div id="adminBlotterPage" class="admin-page" style="display: none;">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">Blotter Reports</h1>
                    <p class="admin-page-subtitle">Manage incident reports and investigations</p>
                </div>
                
                <div class="admin-empty-state">
                    <div class="admin-empty-icon">⚠️</div>
                    <h3>Blotter Management</h3>
                    <p>Blotter report management system is coming soon.</p>
                </div>
            </div>

            <!-- Admin Users Page -->
            <div id="adminUsersPage" class="admin-page" style="display: none;">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">Admin Users</h1>
                    <p class="admin-page-subtitle">Manage administrator accounts</p>
                </div>
                
                <div class="admin-empty-state">
                    <div class="admin-empty-icon">👤</div>
                    <h3>User Management</h3>
                    <p>Admin user management system is coming soon.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate Request Review Modal -->
    <div id="adminRequestReviewModal" class="admin-modal">
        <div class="admin-modal-content admin-request-review-modal">
            <div class="admin-modal-header">
                <h2 id="adminRequestReviewTitle">📄 Certificate Request Review - Barangay Clearance</h2>
                <button class="admin-modal-close" onclick="closeAdminRequestReviewModal()">✕</button>
            </div>
            <div class="admin-request-review-body">
                <!-- Request Header Info -->
                <div class="request-review-header">
                    <div class="request-info-grid">
                        <div class="request-info-item">
                            <label>Resident</label>
                            <div class="resident-info">
                                <div class="resident-name" id="reviewResidentName">Juan Dela Cruz</div>
                                <div class="resident-email" id="reviewResidentEmail">juan@resident.com</div>
                            </div>
                        </div>
                        <div class="request-info-item">
                            <label>Certificate Type</label>
                            <div class="certificate-info">
                                <div class="certificate-name" id="reviewCertificateType">Barangay Clearance</div>
                                <div class="certificate-delivery" id="reviewCertificateDelivery">Downloadable PDF</div>
                            </div>
                        </div>
                        <div class="request-info-item">
                            <label>Status</label>
                            <span class="status-badge pending" id="reviewCurrentStatus">Pending Approval</span>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="request-review-content">
                    <!-- Left Column: Payment & Purpose -->
                    <div class="review-left-column">
                        <!-- Payment Information -->
                        <div class="review-section payment-section">
                            <h3>💰 Payment Information</h3>
                            <div class="payment-details">
                                <div class="payment-item">
                                    <label>Fee:</label>
                                    <span class="payment-fee" id="reviewPaymentFee">₱50</span>
                                </div>
                                <div class="payment-item">
                                    <label>Method:</label>
                                    <span id="reviewPaymentMethod">GCash</span>
                                </div>
                                <div class="payment-item">
                                    <label>Reference:</label>
                                    <span class="payment-reference" id="reviewPaymentReference">GC123456789</span>
                                </div>
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="review-section purpose-section">
                            <h3>📝 Purpose</h3>
                            <div class="purpose-content" id="reviewPurpose">
                                Employment Requirements
                            </div>
                        </div>

                        <!-- Upload Certificate Section -->
                        <div class="review-section upload-section">
                            <h3>📤 Upload Certificate PDF</h3>
                            <div class="upload-certificate-area">
                                <input type="file" id="certificateUpload" accept=".pdf" style="display: none;" onchange="handleCertificateUpload(this)">
                                <div class="upload-placeholder" onclick="document.getElementById('certificateUpload').click()">
                                    <div class="upload-icon">📁</div>
                                    <div class="upload-text">
                                        <div class="upload-title">Choose File</div>
                                        <div class="upload-subtitle">No file chosen</div>
                                    </div>
                                </div>
                                <div class="upload-note">Upload the completed certificate PDF before approving the request.</div>
                            </div>
                        </div>

                        <!-- Update Status -->
                        <div class="review-section status-section">
                            <h3>🔄 Update Status</h3>
                            <select id="reviewStatusSelect" class="status-select">
                                <option value="pending">Pending Approval</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="ready_for_pickup">Ready for Pickup</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Column: Submitted Documents -->
                    <div class="review-right-column">
                        <div class="review-section documents-section">
                            <h3>📋 Submitted Documents</h3>
                            <div class="documents-list">
                                <div class="document-item uploaded">
                                    <div class="document-icon">✅</div>
                                    <div class="document-info">
                                        <div class="document-name">Valid ID</div>
                                        <div class="document-status">Uploaded</div>
                                    </div>
                                    <button class="document-view-btn" onclick="viewDocument('valid_id')">👁️</button>
                                </div>
                                <div class="document-item uploaded">
                                    <div class="document-icon">✅</div>
                                    <div class="document-info">
                                        <div class="document-name">Cedula</div>
                                        <div class="document-status">Uploaded</div>
                                    </div>
                                    <button class="document-view-btn" onclick="viewDocument('cedula')">👁️</button>
                                </div>
                                <div class="document-item uploaded">
                                    <div class="document-icon">✅</div>
                                    <div class="document-info">
                                        <div class="document-name">Proof of Billing</div>
                                        <div class="document-status">Uploaded</div>
                                    </div>
                                    <button class="document-view-btn" onclick="viewDocument('proof_billing')">👁️</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="request-review-actions">
                    <button class="admin-btn approve-btn" onclick="approveAndUploadRequest()">
                        ✅ Approve & Upload
                    </button>
                    <button class="admin-btn reject-btn" onclick="rejectRequestWithReason()">
                        ❌ Reject Request
                    </button>
                    <button class="admin-btn admin-btn-secondary" onclick="closeAdminRequestReviewModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Details Modal (Original) -->
    <div id="adminRequestModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h2 id="adminRequestModalTitle">Request Details</h2>
                <button class="admin-modal-close" onclick="closeAdminRequestModal()">✕</button>
            </div>
            <div id="adminRequestModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Add Resident Modal -->
    <div id="addResidentModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h2>Add New Resident</h2>
                <button class="admin-modal-close" onclick="closeAddResidentModal()">✕</button>
            </div>
            <form id="addResidentForm" class="admin-form">
                <div class="admin-form-grid">
                    <div class="admin-form-group">
                        <label for="newResidentFirstName">First Name *</label>
                        <input type="text" id="newResidentFirstName" name="first_name" required>
                    </div>
                    <div class="admin-form-group">
                        <label for="newResidentLastName">Last Name *</label>
                        <input type="text" id="newResidentLastName" name="last_name" required>
                    </div>
                    <div class="admin-form-group">
                        <label for="newResidentEmail">Email *</label>
                        <input type="email" id="newResidentEmail" name="email" required>
                    </div>
                    <div class="admin-form-group">
                        <label for="newResidentPhone">Phone Number *</label>
                        <input type="tel" id="newResidentPhone" name="phone" required>
                    </div>
                    <div class="admin-form-group">
                        <label for="newResidentBirthDate">Birth Date</label>
                        <input type="date" id="newResidentBirthDate" name="birth_date">
                    </div>
                    <div class="admin-form-group">
                        <label for="newResidentCivilStatus">Civil Status</label>
                        <select id="newResidentCivilStatus" name="civil_status">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                </div>
                <div class="admin-modal-actions">
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="closeAddResidentModal()">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Add Resident</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Report Print Modal -->
    <div id="activityReportModal" class="admin-modal">
        <div class="admin-modal-content activity-report-modal">
            <div class="admin-modal-header">
                <h2>📊 Activity Report</h2>
                <button class="admin-modal-close" onclick="closeActivityReportModal()">✕</button>
            </div>
            <div id="activityReportContent" class="activity-report-content">
                <!-- Report content will be generated here -->
            </div>
            <div class="admin-modal-actions">
                <button class="admin-btn admin-btn-primary" onclick="window.print()">🖨️ Print</button>
                <button class="admin-btn admin-btn-secondary" onclick="closeActivityReportModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>