// E-Barangay Admin Portal JavaScript
let currentAdminUser = null;
let currentActivitiesPage = 1;

// Initialize admin app
document.addEventListener('DOMContentLoaded', function() {
    checkAdminSession();
    initializeAdminEventListeners();
});

// Check if admin is logged in
async function checkAdminSession() {
    try {
        const response = await fetch('api/admin-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_session'
        });
        
        const data = await response.json();
        console.log('Admin session check response:', data);
        
        if (data.success) {
            currentAdminUser = data.admin;
            showAdminDashboard();
        } else {
            showAdminAuth();
        }
    } catch (error) {
        console.error('Session check failed:', error);
        showAdminAuth();
    }
}

// Initialize event listeners
function initializeAdminEventListeners() {
    // Navigation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.admin-nav-item')) {
            e.preventDefault();
            const page = e.target.getAttribute('data-admin-page');
            if (page) {
                showAdminPage(page);
            }
        }
        
        if (e.target.matches('.admin-modal-close') || (e.target.matches('.admin-modal') && e.target === e.currentTarget)) {
            closeAdminModal();
        }
    });

    // Auth form
    const adminLoginForm = document.getElementById('adminLoginForm');
    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', handleAdminLogin);
    }
}

// Authentication functions
async function handleAdminLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'login');
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    
    try {
        const response = await fetch('api/admin-auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Admin login response:', data);
        
        if (data.success) {
            currentAdminUser = data.admin;
            showAdminMessage('Login successful!', 'success');
            showAdminDashboard();
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Admin login failed:', error);
        showAdminMessage('Login failed. Please try again.', 'error');
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function adminSignOut() {
    try {
        const response = await fetch('api/admin-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=logout'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentAdminUser = null;
            showAdminMessage('Signed out successfully!', 'success');
            
            // Redirect to unified login page
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        }
    } catch (error) {
        console.error('Admin sign out failed:', error);
        // Even if sign out fails, redirect to login page
        window.location.href = 'index.php';
    }
}

// Page navigation
function showAdminAuth() {
    document.getElementById('adminAuthContainer').style.display = 'flex';
    document.getElementById('adminDashboardContainer').style.display = 'none';
}

function showAdminDashboard() {
    document.getElementById('adminAuthContainer').style.display = 'none';
    document.getElementById('adminDashboardContainer').style.display = 'flex';
    showAdminPage('dashboard');
    updateAdminUserInfo();
}

function showAdminPage(page) {
    // Hide all pages
    const pages = document.querySelectorAll('.admin-page');
    pages.forEach(p => p.style.display = 'none');
    
    // Remove active class from nav items
    const navItems = document.querySelectorAll('.admin-nav-item');
    navItems.forEach(item => item.classList.remove('active'));
    
    // Show selected page
    const targetPage = document.getElementById('admin' + page.charAt(0).toUpperCase() + page.slice(1) + 'Page');
    if (targetPage) {
        targetPage.style.display = 'block';
    }
    
    // Add active class to nav item
    const activeNav = document.querySelector(`[data-admin-page="${page}"]`);
    if (activeNav) {
        activeNav.classList.add('active');
    }
    
    // Load page-specific data
    switch(page) {
        case 'dashboard':
            loadAdminDashboardData();
            break;
        case 'residents':
            loadAdminResidentsData();
            break;
        case 'requests':
            loadAdminRequestsData();
            break;
        case 'activities':
            loadAdminActivitiesData();
            break;
    }
}

function updateAdminUserInfo() {
    if (currentAdminUser) {
        const userNameElements = document.querySelectorAll('.admin-user-name');
        userNameElements.forEach(el => {
            el.textContent = currentAdminUser.name;
        });
    }
}

// Dashboard functions
async function loadAdminDashboardData() {
    try {
        const response = await fetch('api/admin-dashboard.php?action=get_stats');
        const data = await response.json();
        
        if (data.success) {
            updateAdminDashboardStats(data.stats);
        }
        
        // Load recent requests
        loadAdminRecentRequests();
    } catch (error) {
        console.error('Failed to load admin dashboard data:', error);
    }
}

function updateAdminDashboardStats(stats) {
    document.getElementById('adminTotalResidents').textContent = stats.total_residents || 0;
    document.getElementById('adminPendingRequests').textContent = stats.pending_requests || 0;
    document.getElementById('adminApprovedToday').textContent = stats.approved_today || 0;
    document.getElementById('adminOpenBlotters').textContent = stats.open_blotters || 0;
    document.getElementById('adminTotalRequests').textContent = stats.total_requests || 0;
    document.getElementById('adminRejected').textContent = stats.rejected || 0;
}

async function loadAdminRecentRequests() {
    try {
        const response = await fetch('api/admin-requests.php?action=get_recent');
        const data = await response.json();
        
        if (data.success) {
            displayAdminRecentRequests(data.requests);
        }
    } catch (error) {
        console.error('Failed to load recent requests:', error);
    }
}

function displayAdminRecentRequests(requests) {
    const tbody = document.getElementById('adminRecentRequestsBody');
    if (!tbody) return;
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #64748b;">No recent requests</td></tr>';
        return;
    }
    
    tbody.innerHTML = requests.map(request => `
        <tr>
            <td>${request.resident_name}</td>
            <td>${request.type}</td>
            <td><span class="status-badge ${request.status}">${formatAdminStatus(request.status)}</span></td>
            <td>${formatAdminDate(request.created_at)}</td>
        </tr>
    `).join('');
}

// Residents management
async function loadAdminResidentsData() {
    try {
        const response = await fetch('api/admin-residents.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayAdminResidentsTable(data.residents);
        }
    } catch (error) {
        console.error('Failed to load residents:', error);
    }
}

function displayAdminResidentsTable(residents) {
    const tbody = document.getElementById('adminResidentsTableBody');
    if (!tbody) return;
    
    if (residents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No residents found</td></tr>';
        return;
    }
    
    tbody.innerHTML = residents.map(resident => `
        <tr>
            <td>
                <div style="font-weight: 600;">${resident.first_name} ${resident.last_name}</div>
                <div style="font-size: 0.875rem; color: #64748b;">${resident.middle_name || ''}</div>
            </td>
            <td>
                <div>${resident.email}</div>
                <div style="font-size: 0.875rem; color: #64748b;">${resident.mobile_number || 'No phone'}</div>
            </td>
            <td>
                <div>${[resident.house_no, resident.street, resident.purok].filter(Boolean).join(', ')}</div>
                <div style="font-size: 0.875rem; color: #64748b;">${[resident.barangay, resident.city].filter(Boolean).join(', ')}</div>
            </td>
            <td><span class="status-badge ${resident.status}">${resident.status}</span></td>
            <td>${formatAdminDate(resident.created_at)}</td>
            <td>
                <button class="admin-action-btn edit" onclick="editResident(${resident.id})" title="Edit">✏️</button>
                <button class="admin-action-btn delete" onclick="deleteResident(${resident.id})" title="Delete">🗑️</button>
            </td>
        </tr>
    `).join('');
}

// Requests management
async function loadAdminRequestsData() {
    try {
        const response = await fetch('api/admin-requests.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayAdminRequestsTable(data.requests);
        }
    } catch (error) {
        console.error('Failed to load requests:', error);
    }
}

function displayAdminRequestsTable(requests) {
    const tbody = document.getElementById('adminRequestsTableBody');
    if (!tbody) return;
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #64748b;">No requests found</td></tr>';
        return;
    }
    
    tbody.innerHTML = requests.map(request => `
        <tr>
            <td>
                <div style="font-weight: 600;">${request.resident_name}</div>
                <div style="font-size: 0.875rem; color: #64748b;">${request.resident_email}</div>
            </td>
            <td>${request.type}</td>
            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${request.purpose}">${request.purpose}</td>
            <td><span class="status-badge ${request.status}">${formatAdminStatus(request.status)}</span></td>
            <td>₱${parseFloat(request.processing_fee || 0).toFixed(2)}</td>
            <td>${formatAdminDate(request.created_at)}</td>
            <td>
                <button class="admin-action-btn view" onclick="openAdminRequestReviewModal(${request.id})" title="Review">📋</button>
                ${request.status === 'pending' ? 
                    `<button class="admin-action-btn approve" onclick="quickApproveRequest(${request.id})" title="Quick Approve">✅</button>
                     <button class="admin-action-btn reject" onclick="quickRejectRequest(${request.id})" title="Quick Reject">❌</button>` : ''}
            </td>
        </tr>
    `).join('');
}

// Request review modal
async function openAdminRequestReviewModal(requestId) {
    try {
        const response = await fetch(`api/admin-requests.php?action=get_details&id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
            showAdminRequestReviewModal(data.request);
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to load request details:', error);
        showAdminMessage('Failed to load request details', 'error');
    }
}

function showAdminRequestReviewModal(request) {
    const modal = document.getElementById('adminRequestReviewModal');
    
    // Update modal content
    document.getElementById('adminRequestReviewTitle').textContent = `📄 Certificate Request Review - ${request.type}`;
    document.getElementById('reviewResidentName').textContent = request.resident_name;
    document.getElementById('reviewResidentEmail').textContent = request.resident_email;
    document.getElementById('reviewCertificateType').textContent = request.type;
    document.getElementById('reviewCertificateDelivery').textContent = request.type.toLowerCase().includes('barangay id') ? '📍 Pickup Only' : '📥 Downloadable PDF';
    document.getElementById('reviewCurrentStatus').textContent = formatAdminStatus(request.status);
    document.getElementById('reviewCurrentStatus').className = `status-badge ${request.status}`;
    document.getElementById('reviewPaymentFee').textContent = `₱${parseFloat(request.processing_fee || 0).toFixed(2)}`;
    document.getElementById('reviewPurpose').textContent = request.purpose;
    
    // Handle payment information
    const requestDetails = request.request_details ? JSON.parse(request.request_details) : {};
    if (requestDetails.payment_method) {
        document.getElementById('reviewPaymentMethod').textContent = requestDetails.payment_method;
        document.getElementById('reviewPaymentReference').textContent = requestDetails.payment_reference || 'N/A';
    }
    
    // Handle uploaded documents
    updateDocumentsSection(requestDetails.uploaded_documents || {}, request.id);
    
    // Set status select
    const statusSelect = document.getElementById('reviewStatusSelect');
    statusSelect.value = request.status;
    
    // Store request ID for actions
    modal.dataset.requestId = request.id;
    
    modal.classList.add('active');
    modal.style.display = 'flex';
}

function updateDocumentsSection(uploadedDocuments, requestId) {
    const documentsContainer = document.querySelector('.documents-list');
    if (!documentsContainer) return;
    
    // Clear existing documents
    documentsContainer.innerHTML = '';
    
    if (Object.keys(uploadedDocuments).length === 0) {
        documentsContainer.innerHTML = '<div style="text-align: center; color: #64748b; padding: 20px;">No documents uploaded</div>';
        return;
    }
    
    // Create document items for ONLY the uploaded documents
    Object.entries(uploadedDocuments).forEach(([docType, filename]) => {
        const documentItem = document.createElement('div');
        documentItem.className = 'document-item uploaded';
        
        // Clean up document type name for display - handle long field names
        let displayName = docType;
        
        // Handle specific document type mappings for better display
        const displayMappings = {
            'document_valid_government_issued_id__with_address_': 'Valid Government-issued ID (with address)',
            'document_proof_of_billing__proof_of_residency__if_not_on_id_': 'Proof of Billing / Proof of Residency',
            'document_cedula': 'Cedula',
            'document_no_income_or_proof_of_unemployment': 'No Income or Proof of Unemployment',
            'valid_id': 'Valid ID',
            'proof_billing': 'Proof of Billing',
            'cedula': 'Cedula',
            'proof_of_residency': 'Proof of Residency',
            'proof_of_unemployment': 'Proof of Unemployment'
        };
        
        if (displayMappings[docType]) {
            displayName = displayMappings[docType];
        } else {
            // Fallback: clean up the field name
            displayName = docType
                .replace(/document_/g, '')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, l => l.toUpperCase());
        }
        
        documentItem.innerHTML = `
            <div class="document-icon">✅</div>
            <div class="document-info">
                <div class="document-name">${displayName}</div>
                <div class="document-status">Uploaded</div>
            </div>
            <button class="document-view-btn" onclick="viewDocument('${requestId}', '${docType}')" title="View Document">👁️</button>
        `;
        
        documentsContainer.appendChild(documentItem);
    });
}

function viewDocument(requestId, documentType) {
    // Open document in new tab
    const url = `api/admin-requests.php?action=view_document&request_id=${requestId}&document_type=${documentType}`;
    window.open(url, '_blank');
}

function closeAdminRequestReviewModal() {
    const modal = document.getElementById('adminRequestReviewModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
}

// Certificate upload handling
function handleCertificateUpload(input) {
    const file = input.files[0];
    const uploadArea = input.closest('.upload-certificate-area');
    const placeholder = uploadArea.querySelector('.upload-placeholder');
    
    if (file) {
        // Validate file type
        if (file.type !== 'application/pdf') {
            showAdminMessage('Only PDF files are allowed for certificates', 'error');
            input.value = '';
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAdminMessage('File size must be less than 5MB', 'error');
            input.value = '';
            return;
        }
        
        // Update UI
        placeholder.innerHTML = `
            <div class="upload-icon">✅</div>
            <div class="upload-text">
                <div class="upload-title">Certificate Ready</div>
                <div class="upload-subtitle">${file.name}</div>
            </div>
        `;
        placeholder.style.background = '#ecfdf5';
        placeholder.style.borderColor = '#bbf7d0';
    }
}

// Request actions
async function approveAndUploadRequest() {
    const modal = document.getElementById('adminRequestReviewModal');
    const requestId = modal.dataset.requestId;
    const certificateFile = document.getElementById('certificateUpload').files[0];
    const status = document.getElementById('reviewStatusSelect').value;
    
    if (!certificateFile && status === 'approved') {
        showAdminMessage('Please upload a certificate PDF before approving', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('id', requestId);
    formData.append('status', status);
    
    if (certificateFile) {
        formData.append('certificate', certificateFile);
    }
    
    try {
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            closeAdminRequestReviewModal();
            loadAdminRequestsData(); // Refresh the table
            loadAdminDashboardData(); // Refresh dashboard stats
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to approve request:', error);
        showAdminMessage('Failed to approve request', 'error');
    }
}

async function rejectRequestWithReason() {
    const reason = prompt('Please enter the reason for rejection:');
    if (!reason || reason.trim() === '') {
        showAdminMessage('Rejection reason is required', 'error');
        return;
    }
    
    const modal = document.getElementById('adminRequestReviewModal');
    const requestId = modal.dataset.requestId;
    
    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('id', requestId);
    formData.append('reason', reason.trim());
    
    try {
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            closeAdminRequestReviewModal();
            loadAdminRequestsData(); // Refresh the table
            loadAdminDashboardData(); // Refresh dashboard stats
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to reject request:', error);
        showAdminMessage('Failed to reject request', 'error');
    }
}

// Quick actions
async function quickApproveRequest(requestId) {
    if (!confirm('Are you sure you want to approve this request? Note: You should upload a certificate document first.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('id', requestId);
    formData.append('status', 'approved');
    
    try {
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            loadAdminRequestsData();
            loadAdminDashboardData();
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to approve request:', error);
        showAdminMessage('Failed to approve request', 'error');
    }
}

async function quickRejectRequest(requestId) {
    const reason = prompt('Please enter the reason for rejection:');
    if (!reason || reason.trim() === '') {
        showAdminMessage('Rejection reason is required', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('id', requestId);
    formData.append('reason', reason.trim());
    
    try {
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            loadAdminRequestsData();
            loadAdminDashboardData();
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to reject request:', error);
        showAdminMessage('Failed to reject request', 'error');
    }
}

// Activities management
async function loadAdminActivitiesData() {
    try {
        const response = await fetch('api/admin-activities.php?action=get_activity_stats');
        const data = await response.json();
        
        if (data.success) {
            updateActivityStats(data.admin_stats, data.user_stats);
        }
        
        // Load activities list
        loadActivitiesPage(1);
    } catch (error) {
        console.error('Failed to load activities data:', error);
    }
}

function updateActivityStats(adminStats, userStats) {
    document.getElementById('totalAdminActivities').textContent = adminStats.total_admin_activities || 0;
    document.getElementById('totalUserActivities').textContent = userStats.total_user_activities || 0;
    document.getElementById('totalAdminLogins').textContent = adminStats.admin_logins || 0;
    document.getElementById('totalUserLogins').textContent = userStats.user_logins || 0;
}

async function loadActivitiesPage(page) {
    currentActivitiesPage = page;
    
    const params = new URLSearchParams({
        action: 'get_activities',
        page: page,
        limit: 20,
        date_from: document.getElementById('activitiesDateFrom')?.value || '',
        date_to: document.getElementById('activitiesDateTo')?.value || '',
        activity_type: document.getElementById('activitiesTypeFilter')?.value || 'all',
        action_filter: document.getElementById('activitiesActionFilter')?.value || '',
        admin_filter: document.getElementById('activitiesSearch')?.value || ''
    });
    
    try {
        const response = await fetch(`api/admin-activities.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayActivitiesTable(data.activities);
            updateActivitiesPagination(data.page, data.total_pages);
        }
    } catch (error) {
        console.error('Failed to load activities:', error);
    }
}

function displayActivitiesTable(activities) {
    const tbody = document.getElementById('adminActivitiesTableBody');
    if (!tbody) return;
    
    if (activities.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #64748b;">No activities found</td></tr>';
        return;
    }
    
    tbody.innerHTML = activities.map(activity => `
        <tr>
            <td>${formatAdminDateTime(activity.created_at)}</td>
            <td>
                <div style="font-weight: 600;">${activity.admin_name}</div>
                <div style="font-size: 0.875rem; color: #64748b;">${activity.admin_email}</div>
            </td>
            <td><span class="status-badge ${activity.activity_type}">${activity.activity_type}</span></td>
            <td>${activity.action}</td>
            <td>${activity.target_type || '-'}</td>
            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${activity.details || ''}">${activity.details || '-'}</td>
            <td style="font-family: monospace; font-size: 0.875rem;">${activity.ip_address}</td>
        </tr>
    `).join('');
}

function updateActivitiesPagination(currentPage, totalPages) {
    const pagination = document.getElementById('activitiesPagination');
    const pageInfo = document.getElementById('activitiesPageInfo');
    const prevBtn = document.getElementById('prevActivitiesBtn');
    const nextBtn = document.getElementById('nextActivitiesBtn');
    
    if (pagination && pageInfo && prevBtn && nextBtn) {
        pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
        pagination.style.display = totalPages > 1 ? 'flex' : 'none';
    }
}

function filterActivities() {
    loadActivitiesPage(1);
}

// Print activity report
function printActivityReport() {
    const modal = document.getElementById('activityReportModal');
    const content = document.getElementById('activityReportContent');
    
    // Generate report content
    content.innerHTML = `
        <div class="report-header">
            <div class="report-title">E-Barangay Activity Report</div>
            <div class="report-subtitle">Generated on ${new Date().toLocaleDateString()}</div>
            <div class="report-date-range">Activity Period: ${document.getElementById('activitiesDateFrom')?.value || 'All time'} to ${document.getElementById('activitiesDateTo')?.value || 'Present'}</div>
        </div>
        
        <div class="report-stats">
            <h3>Activity Summary</h3>
            <table class="report-table">
                <tr><th>Metric</th><th>Count</th></tr>
                <tr><td>Total Admin Activities</td><td>${document.getElementById('totalAdminActivities').textContent}</td></tr>
                <tr><td>Total User Activities</td><td>${document.getElementById('totalUserActivities').textContent}</td></tr>
                <tr><td>Admin Logins</td><td>${document.getElementById('totalAdminLogins').textContent}</td></tr>
                <tr><td>User Logins</td><td>${document.getElementById('totalUserLogins').textContent}</td></tr>
            </table>
        </div>
        
        <div class="report-footer">
            <p>This report was generated by the E-Barangay Portal System</p>
            <p>For official use only</p>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeActivityReportModal() {
    document.getElementById('activityReportModal').style.display = 'none';
}

// Resident management functions
function openAddResidentModal() {
    document.getElementById('addResidentModal').style.display = 'flex';
}

function closeAddResidentModal() {
    document.getElementById('addResidentModal').style.display = 'none';
    document.getElementById('addResidentForm').reset();
}

async function editResident(residentId) {
    showAdminMessage('Edit resident functionality coming soon', 'info');
}

async function deleteResident(residentId) {
    if (!confirm('Are you sure you want to delete this resident? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', residentId);
    
    try {
        const response = await fetch('api/admin-residents.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            loadAdminResidentsData();
            loadAdminDashboardData();
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to delete resident:', error);
        showAdminMessage('Failed to delete resident', 'error');
    }
}

// Modal functions
function closeAdminModal() {
    const modals = document.querySelectorAll('.admin-modal');
    modals.forEach(modal => {
        modal.classList.remove('active');
        modal.style.display = 'none';
    });
}

// Utility functions
function formatAdminStatus(status) {
    const statusMap = {
        'pending': 'Under Review',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'ready_for_pickup': 'Ready for Pickup'
    };
    return statusMap[status] || status;
}

function formatAdminDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatAdminDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showAdminMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.admin-auth-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create message element
    const messageEl = document.createElement('div');
    messageEl.className = `admin-auth-message ${type}`;
    messageEl.textContent = message;
    messageEl.style.display = 'block';
    
    // Find a container to show the message
    const container = document.querySelector('.admin-page:not([style*="display: none"]) .admin-page-header') || 
                     document.querySelector('.admin-auth-card') || 
                     document.body;
    
    // Insert at the top
    container.insertBefore(messageEl, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (messageEl.parentNode) {
            messageEl.parentNode.removeChild(messageEl);
        }
    }, 5000);
}

// Demo account function for admin
function fillAdminDemoAccount() {
    const emailField = document.getElementById('adminEmail');
    const passwordField = document.getElementById('adminPassword');
    
    if (emailField) emailField.value = 'admin@barangay.gov.ph';
    if (passwordField) passwordField.value = 'admin123';
}