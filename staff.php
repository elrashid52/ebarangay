<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Barangay Staff Portal</title>
    <link rel="stylesheet" href="assets/css/staff.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Staff Authentication Container -->
    <div id="staffAuthContainer" class="staff-auth-container">
        <div class="staff-auth-card">
            <div class="staff-auth-header">
                <div class="staff-logo-icon">👨‍💼</div>
                <h1 class="staff-auth-title">Barangay Staff Portal</h1>
                <p class="staff-auth-subtitle">Staff access to barangay services</p>
            </div>
            
            <div id="staffAuthMessage" class="staff-auth-message" style="display: none;"></div>
            
            <form id="staffLoginForm" class="staff-auth-form">
                <div class="staff-form-group">
                    <label for="staffEmail">Email Address</label>
                    <div class="staff-input-wrapper">
                        <div class="staff-input-icon">📧</div>
                        <input type="email" id="staffEmail" name="email" required placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="staff-form-group">
                    <label for="staffPassword">Password</label>
                    <div class="staff-input-wrapper">
                        <div class="staff-input-icon">🔒</div>
                        <input type="password" id="staffPassword" name="password" required placeholder="Enter your password">
                    </div>
                </div>
                
                <button type="submit" class="staff-btn staff-btn-primary">
                    Sign In to Staff Portal
                </button>
            </form>
            
            <div class="staff-demo-accounts">
                <div class="staff-demo-title">Demo Staff Account</div>
                <div class="staff-demo-account">
                    <strong>Staff Member:</strong><br>
                    staff@barangay.gov.ph / password
                    <button type="button" class="staff-btn staff-btn-secondary" onclick="fillStaffDemoAccount()">
                        Use Staff Demo
                    </button>
                </div>
            </div>
            
            <div class="staff-auth-switch">
                <p>Need different access? <a href="index.php">Resident Portal</a> | <a href="admin.php">Admin Portal</a></p>
            </div>
        </div>
    </div>

    <!-- Staff Dashboard Container -->
    <div id="staffDashboardContainer" class="staff-dashboard-container" style="display: none;">
        <!-- Staff Sidebar -->
        <div class="staff-sidebar">
            <div class="staff-sidebar-header">
                <div class="staff-sidebar-logo">
                    <div class="staff-sidebar-logo-icon">👨‍💼</div>
                    <div>
                        <div>E-Barangay</div>
                        <div class="staff-sidebar-subtitle">Staff Portal</div>
                    </div>
                </div>
            </div>
            
            <nav class="staff-sidebar-nav">
                <a href="#" class="staff-nav-item active" data-staff-page="dashboard">
                    <div class="staff-nav-icon">📊</div>
                    Dashboard
                </a>
                <a href="#" class="staff-nav-item" data-staff-page="requests">
                    <div class="staff-nav-icon">📄</div>
                    Process Requests
                </a>
                <a href="#" class="staff-nav-item" data-staff-page="residents">
                    <div class="staff-nav-icon">👥</div>
                    View Residents
                </a>
                <a href="#" class="staff-nav-item" data-staff-page="announcements">
                    <div class="staff-nav-icon">📢</div>
                    Announcements
                </a>
                <a href="#" class="staff-nav-item" data-staff-page="reports">
                    <div class="staff-nav-icon">📈</div>
                    Reports
                </a>
                <a href="#" class="staff-nav-item" onclick="staffSignOut()">
                    <div class="staff-nav-icon">🚪</div>
                    Sign Out
                </a>
            </nav>
        </div>

        <!-- Staff Main Content -->
        <div class="staff-main-content">
            <!-- Dashboard Page -->
            <div id="staffDashboardPage" class="staff-page">
                <div class="staff-page-header">
                    <h1 class="staff-page-title">Staff Dashboard</h1>
                    <p class="staff-page-subtitle">Overview of your assigned tasks and responsibilities</p>
                </div>
                
                <div class="staff-dashboard-grid">
                    <div class="staff-dashboard-card">
                        <div class="staff-card-header">
                            <div class="staff-card-icon pending-requests">⏳</div>
                            <div class="staff-card-content">
                                <h3>Pending Requests</h3>
                                <div class="staff-card-number" id="staffPendingRequests">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-dashboard-card">
                        <div class="staff-card-header">
                            <div class="staff-card-icon processed-today">✅</div>
                            <div class="staff-card-content">
                                <h3>Processed Today</h3>
                                <div class="staff-card-number" id="staffProcessedToday">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-dashboard-card">
                        <div class="staff-card-header">
                            <div class="staff-card-icon total-residents">👥</div>
                            <div class="staff-card-content">
                                <h3>Total Residents</h3>
                                <div class="staff-card-number" id="staffTotalResidents">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-dashboard-card">
                        <div class="staff-card-header">
                            <div class="staff-card-icon active-announcements">📢</div>
                            <div class="staff-card-content">
                                <h3>Active Announcements</h3>
                                <div class="staff-card-number" id="staffActiveAnnouncements">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="staff-dashboard-tables">
                    <div class="staff-recent-requests">
                        <div class="staff-section-header">
                            <h2>Recent Requests</h2>
                            <a href="#" class="staff-btn staff-btn-secondary" onclick="showStaffPage('requests')">View All</a>
                        </div>
                        
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th>RESIDENT</th>
                                    <th>TYPE</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="staffRecentRequestsBody">
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #64748b;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Requests Page -->
            <div id="staffRequestsPage" class="staff-page" style="display: none;">
                <div class="staff-page-header">
                    <h1 class="staff-page-title">Process Requests</h1>
                    <p class="staff-page-subtitle">Review and process certificate requests</p>
                </div>
                
                <div class="staff-data-grid">
                    <div class="staff-data-grid-header">
                        <div class="staff-search-bar">
                            <input type="text" id="staffRequestsSearch" placeholder="Search requests..." class="staff-search-input">
                        </div>
                        <div class="staff-filter-controls">
                            <select id="staffRequestsStatusFilter" class="staff-filter-select">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <select id="staffRequestsTypeFilter" class="staff-filter-select">
                                <option value="">All Types</option>
                                <option value="Barangay Clearance">Barangay Clearance</option>
                                <option value="Certificate of Indigency">Certificate of Indigency</option>
                                <option value="Certificate of Residency">Certificate of Residency</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="staff-table-container">
                        <table class="staff-data-table">
                            <thead>
                                <tr>
                                    <th>RESIDENT</th>
                                    <th>CERTIFICATE TYPE</th>
                                    <th>PURPOSE</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="staffRequestsTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b;">Loading requests...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Residents Page -->
            <div id="staffResidentsPage" class="staff-page" style="display: none;">
                <div class="staff-page-header">
                    <h1 class="staff-page-title">View Residents</h1>
                    <p class="staff-page-subtitle">Browse resident information (read-only)</p>
                </div>
                
                <div class="staff-data-grid">
                    <div class="staff-data-grid-header">
                        <div class="staff-search-bar">
                            <input type="text" id="staffResidentsSearch" placeholder="Search residents..." class="staff-search-input">
                        </div>
                        <div class="staff-filter-controls">
                            <select id="staffResidentsStatusFilter" class="staff-filter-select">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Deactivated">Deactivated</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="staff-table-container">
                        <table class="staff-data-table">
                            <thead>
                                <tr>
                                    <th>NAME</th>
                                    <th>CONTACT INFO</th>
                                    <th>ADDRESS</th>
                                    <th>STATUS</th>
                                    <th>REGISTERED</th>
                                </tr>
                            </thead>
                            <tbody id="staffResidentsTableBody">
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #64748b;">Loading residents...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Announcements Page -->
            <div id="staffAnnouncementsPage" class="staff-page" style="display: none;">
                <div class="staff-page-header">
                    <h1 class="staff-page-title">Announcements</h1>
                    <p class="staff-page-subtitle">Create and manage barangay announcements</p>
                    <button class="staff-btn staff-btn-primary" onclick="openCreateAnnouncementModal()">
                        ➕ Create Announcement
                    </button>
                </div>
                
                <div class="staff-announcements-grid" id="staffAnnouncementsGrid">
                    <div class="staff-announcement-card">
                        <div class="announcement-header">
                            <h3>Loading announcements...</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Page -->
            <div id="staffReportsPage" class="staff-page" style="display: none;">
                <div class="staff-page-header">
                    <h1 class="staff-page-title">Reports</h1>
                    <p class="staff-page-subtitle">Generate reports and view statistics</p>
                </div>
                
                <div class="staff-reports-grid">
                    <div class="staff-report-card">
                        <div class="report-icon">📊</div>
                        <h3>Requests Report</h3>
                        <p>Generate detailed reports on certificate requests</p>
                        <button class="staff-btn staff-btn-primary" onclick="generateRequestsReport()">Generate Report</button>
                    </div>
                    
                    <div class="staff-report-card">
                        <div class="report-icon">👥</div>
                        <h3>Residents Summary</h3>
                        <p>View summary statistics of registered residents</p>
                        <button class="staff-btn staff-btn-primary" onclick="generateResidentsReport()">Generate Report</button>
                    </div>
                    
                    <div class="staff-report-card">
                        <div class="report-icon">📈</div>
                        <h3>Monthly Statistics</h3>
                        <p>Monthly performance and activity statistics</p>
                        <button class="staff-btn staff-btn-primary" onclick="generateMonthlyReport()">Generate Report</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Review Modal -->
    <div id="staffRequestReviewModal" class="staff-modal">
        <div class="staff-modal-content">
            <div class="staff-modal-header">
                <h2 id="staffRequestReviewTitle">📄 Review Request</h2>
                <button class="staff-modal-close" onclick="closeStaffRequestReviewModal()">✕</button>
            </div>
            <div id="staffRequestReviewBody" class="staff-request-review-body">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Create Announcement Modal -->
    <div id="createAnnouncementModal" class="staff-modal">
        <div class="staff-modal-content">
            <div class="staff-modal-header">
                <h2>📢 Create Announcement</h2>
                <button class="staff-modal-close" onclick="closeCreateAnnouncementModal()">✕</button>
            </div>
            <form id="createAnnouncementForm" class="staff-form">
                <div class="staff-form-group">
                    <label for="announcementTitle">Title *</label>
                    <input type="text" id="announcementTitle" name="title" required>
                </div>
                <div class="staff-form-group">
                    <label for="announcementContent">Content *</label>
                    <textarea id="announcementContent" name="content" rows="6" required></textarea>
                </div>
                <div class="staff-form-grid">
                    <div class="staff-form-group">
                        <label for="announcementType">Type</label>
                        <select id="announcementType" name="type">
                            <option value="general">General</option>
                            <option value="urgent">Urgent</option>
                            <option value="event">Event</option>
                            <option value="service">Service</option>
                        </select>
                    </div>
                    <div class="staff-form-group">
                        <label for="announcementPriority">Priority</label>
                        <select id="announcementPriority" name="priority">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="staff-form-group">
                    <label for="announcementExpiry">Expiry Date (Optional)</label>
                    <input type="date" id="announcementExpiry" name="expiry_date">
                </div>
                <div class="staff-modal-actions">
                    <button type="button" class="staff-btn staff-btn-secondary" onclick="closeCreateAnnouncementModal()">Cancel</button>
                    <button type="submit" class="staff-btn staff-btn-primary">Create Announcement</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/staff.js"></script>
</body>
</html>