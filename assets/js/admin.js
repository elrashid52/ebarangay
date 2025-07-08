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
        case 'backup':
            loadBackupData();
            break;
        case 'backup':
            loadBackupData();
            break;
        case 'backup':
            loadBackupData();
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
    // Open document in modal viewer
    openDocumentViewerModal(requestId, documentType);
}

// New function to open document viewer modal
function openDocumentViewerModal(requestId, documentType) {
    const modal = document.getElementById('documentViewerModal');
    const iframe = document.getElementById('documentViewerFrame');
    const title = document.getElementById('documentViewerTitle');
    const loading = document.querySelector('.document-viewer-loading');
    const errorDiv = document.querySelector('.document-viewer-error');
    
    // Clean up document type name for display
    let displayName = documentType;
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
    
    if (displayMappings[documentType]) {
        displayName = displayMappings[documentType];
    } else {
        displayName = documentType
            .replace(/document_/g, '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }
    
    // Set title
    title.textContent = `📄 ${displayName}`;
    
    // Show modal
    modal.style.display = 'flex';
    
    // Show loading state
    loading.style.display = 'flex';
    errorDiv.style.display = 'none';
    iframe.style.display = 'none';
    
    // Set iframe source
    const url = `api/admin-requests.php?action=view_document&request_id=${requestId}&document_type=${documentType}`;
    iframe.src = url;
    
    // Handle iframe load
    iframe.onload = function() {
        loading.style.display = 'none';
        iframe.style.display = 'block';
    };
    
    // Handle iframe error
    iframe.onerror = function() {
        loading.style.display = 'none';
        errorDiv.style.display = 'flex';
    };
    
    // Store current document info for download/new tab actions
    modal.dataset.requestId = requestId;
    modal.dataset.documentType = documentType;
}

// Close document viewer modal
function closeDocumentViewerModal() {
    const modal = document.getElementById('documentViewerModal');
    const iframe = document.getElementById('documentViewerFrame');
    
    modal.style.display = 'none';
    iframe.src = ''; // Clear iframe to stop loading
}

// Download document from viewer
function downloadDocumentFromViewer() {
    const modal = document.getElementById('documentViewerModal');
    const requestId = modal.dataset.requestId;
    const documentType = modal.dataset.documentType;
    
    if (requestId && documentType) {
        const url = `api/admin-requests.php?action=view_document&request_id=${requestId}&document_type=${documentType}`;
        const a = document.createElement('a');
        a.href = url;
        a.download = `${documentType}_${requestId}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
}

// Open document in new tab from viewer
function openDocumentInNewTab() {
    const modal = document.getElementById('documentViewerModal');
    const requestId = modal.dataset.requestId;
    const documentType = modal.dataset.documentType;
    
    if (requestId && documentType) {
        const url = `api/admin-requests.php?action=view_document&request_id=${requestId}&document_type=${documentType}`;
        window.open(url, '_blank');
    }
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
    
    generateActivityReport(content);
    
    modal.style.display = 'flex';
}

// Generate comprehensive activity report
async function generateActivityReport(content) {
    const dateFrom = document.getElementById('activitiesDateFrom')?.value || '';
    const dateTo = document.getElementById('activitiesDateTo')?.value || '';
    const currentDate = new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const currentTime = new Date().toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Get current activities from table
    const activitiesTable = document.getElementById('adminActivitiesTableBody');
    const activities = [];
    
    if (activitiesTable) {
        const rows = activitiesTable.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7 && !row.textContent.includes('Loading') && !row.textContent.includes('No activities')) {
                activities.push({
                    timestamp: cells[0].textContent.trim(),
                    user: cells[1].textContent.trim(),
                    type: cells[2].textContent.trim(),
                    action: cells[3].textContent.trim(),
                    target: cells[4].textContent.trim(),
                    details: cells[5].textContent.trim(),
                    ip: cells[6].textContent.trim()
                });
            }
        });
    }
    
    // Generate report content
    content.innerHTML = `
        <div class="report-header">
            <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <div style="font-size: 3rem; margin-right: 20px;">🏛️</div>
                <div>
                    <div class="report-title">E-BARANGAY PORTAL SYSTEM</div>
                    <div class="report-subtitle">Activity Report & System Audit</div>
                </div>
            </div>
            <div class="report-date-range">
                <strong>Report Generated:</strong> ${currentDate} at ${currentTime}<br>
                <strong>Period Covered:</strong> ${dateFrom || 'System Start'} to ${dateTo || 'Present'}
            </div>
        </div>
        
        <div class="report-stats" style="margin-bottom: 30px;">
            <h3 style="color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 10px;">📊 Activity Summary</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <table class="report-table">
                    <thead>
                        <tr style="background: #1e40af; color: white;">
                            <th colspan="2">System Statistics</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>Total Admin Activities</strong></td><td>${document.getElementById('totalAdminActivities').textContent}</td></tr>
                        <tr><td><strong>Total User Activities</strong></td><td>${document.getElementById('totalUserActivities').textContent}</td></tr>
                        <tr><td><strong>Admin Logins</strong></td><td>${document.getElementById('totalAdminLogins').textContent}</td></tr>
                        <tr><td><strong>User Logins</strong></td><td>${document.getElementById('totalUserLogins').textContent}</td></tr>
                    </tbody>
                </table>
                <table class="report-table">
                    <thead>
                        <tr style="background: #059669; color: white;">
                            <th colspan="2">Report Information</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>Activities Displayed</strong></td><td>${activities.length}</td></tr>
                        <tr><td><strong>Report Type</strong></td><td>Comprehensive Audit</td></tr>
                        <tr><td><strong>Generated By</strong></td><td>${currentAdminUser?.name || 'System Administrator'}</td></tr>
                        <tr><td><strong>System Status</strong></td><td>✅ Operational</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        ${activities.length > 0 ? `
        <div class="report-activities" style="margin-bottom: 30px;">
            <h3 style="color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 10px;">📋 Detailed Activity Log</h3>
            <table class="report-table">
                <thead>
                    <tr style="background: #1e40af; color: white;">
                        <th>Date & Time</th>
                        <th>User/Admin</th>
                        <th>Type</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    ${activities.map(activity => `
                        <tr>
                            <td>${activity.timestamp}</td>
                            <td>${activity.user}</td>
                            <td><span style="padding: 2px 6px; border-radius: 4px; background: ${activity.type.includes('admin') ? '#eff6ff' : '#f0fdf4'}; color: ${activity.type.includes('admin') ? '#1e40af' : '#059669'};">${activity.type}</span></td>
                            <td>${activity.action}</td>
                            <td>${activity.target}</td>
                            <td>${activity.details}</td>
                            <td style="font-family: monospace; font-size: 10px;">${activity.ip}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        ` : `
        <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; margin: 20px 0;">
            <div style="font-size: 3rem; margin-bottom: 20px;">📊</div>
            <h3>No Activities Found</h3>
            <p>No activities match the current filter criteria for the selected period.</p>
        </div>
        `}
        
        <div class="report-security" style="margin-bottom: 30px;">
            <h3 style="color: #dc2626; border-bottom: 2px solid #dc2626; padding-bottom: 10px;">🔒 Security & Compliance</h3>
            <div style="background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #dc2626;">
                <p><strong>Data Protection:</strong> This report contains sensitive system information and should be handled according to data protection policies.</p>
                <p><strong>Retention:</strong> Activity logs are maintained for audit and security purposes.</p>
                <p><strong>Access:</strong> This report is restricted to authorized administrative personnel only.</p>
            </div>
        </div>
        
        <div class="report-footer">
            <div style="border-top: 2px solid #e5e7eb; padding-top: 20px; text-align: center;">
                <p><strong>E-Barangay Portal System</strong> | Activity Report</p>
                <p>Generated on ${currentDate} at ${currentTime} | Page 1 of 1</p>
                <p style="font-size: 9px; color: #9ca3af;">This document is computer-generated and does not require a signature.</p>
            </div>
        </div>
    `;
}

// Export activities to CSV
function exportActivitiesCSV() {
    const activitiesTable = document.getElementById('adminActivitiesTableBody');
    const activities = [];
    
    if (activitiesTable) {
        const rows = activitiesTable.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7 && !row.textContent.includes('Loading') && !row.textContent.includes('No activities')) {
                activities.push([
                    cells[0].textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim(),
                    cells[6].textContent.trim()
                ]);
            }
        });
    }
    
    if (activities.length === 0) {
        showAdminMessage('No activities to export', 'error');
        return;
    }
    
    // Create CSV content
    const headers = ['Timestamp', 'User/Admin', 'Type', 'Action', 'Target', 'Details', 'IP Address'];
    const csvContent = [
        headers.join(','),
        ...activities.map(row => row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(','))
    ].join('\n');
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `activity_report_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAdminMessage('Activity report exported successfully!', 'success');
}

// Clear activity filters
function clearActivityFilters() {
    document.getElementById('activitiesSearch').value = '';
    document.getElementById('activitiesDateFrom').value = '';
    document.getElementById('activitiesDateTo').value = '';
    document.getElementById('activitiesTypeFilter').value = 'all';
    document.getElementById('activitiesActionFilter').value = '';
    
    // Reload activities
    loadActivitiesPage(1);
    
    showAdminMessage('Filters cleared', 'success');
}

// Backup and Restore Functions
async function loadBackupData() {
    try {
        // Load backup statistics
        const statsResponse = await fetch('api/admin-backup.php?action=get_backup_stats');
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            updateBackupStats(statsData.stats);
        }
        
        // Load backups list
        const response = await fetch('api/admin-backup.php?action=list_backups');
        const data = await response.json();
        
        if (data.success) {
            displayBackupsTable(data.backups);
        }
    } catch (error) {
        console.error('Failed to load backup data:', error);
        showAdminMessage('Failed to load backup data', 'error');
    }
}

function updateBackupStats(stats) {
    document.getElementById('totalBackups').textContent = stats.total_backups || 0;
    document.getElementById('totalBackupSize').textContent = stats.total_size_formatted || '0 B';
    document.getElementById('latestBackup').textContent = stats.latest_backup || 'Never';
    document.getElementById('backupStatus').textContent = stats.status || 'Ready';
}

function displayBackupsTable(backups) {
    const tbody = document.getElementById('backupsTableBody');
    if (!tbody) return;
    
    if (backups.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No backups found</td></tr>';
        return;
    }
    
    tbody.innerHTML = backups.map(backup => {
        const hasDatabase = backup.database;
        const hasFiles = backup.files;
        
        let typeDisplay = '';
        if (hasDatabase && hasFiles) {
            typeDisplay = '<span class="status-badge approved">✅ DATABASE</span> <span class="status-badge rejected">❌ FILES</span>';
        } else if (hasDatabase) {
            typeDisplay = '<span class="status-badge approved">✅ DATABASE</span>';
        } else if (hasFiles) {
            typeDisplay = '<span class="status-badge pending">📁 FILES</span>';
        }
        
        const status = (hasDatabase && hasFiles) ? 'COMPLETE' : 'PARTIAL';
        const statusClass = status === 'COMPLETE' ? 'approved' : 'pending';
        
        return `
            <tr>
                <td>
                    <div style="font-weight: 600;">${backup.name}</div>
                    <div style="font-size: 0.875rem; color: #64748b;">${formatAdminDate(backup.created_at)}</div>
                </td>
                <td>${typeDisplay}</td>
                <td>${backup.size_formatted || formatBytes(backup.size)}</td>
                <td>${formatAdminDate(backup.created_at)}</td>
                <td><span class="status-badge ${statusClass}">${status}</span></td>
                <td>
                    ${hasDatabase ? `<button class="admin-action-btn view" onclick="downloadBackup('${backup.name}', 'database')" title="Download Database">💾</button>` : ''}
                    ${hasFiles ? `<button class="admin-action-btn view" onclick="downloadBackup('${backup.name}', 'files')" title="Download Files">📁</button>` : ''}
                    <button class="admin-action-btn approve" onclick="openRestoreModal('${backup.name}', ${hasDatabase}, ${hasFiles})" title="Restore">🔄</button>
                    <button class="admin-action-btn delete" onclick="deleteBackup('${backup.name}')" title="Delete">🗑️</button>
                </td>
            </tr>
        `;
    }).join('');
}

async function createBackup() {
    const backupName = document.getElementById('backupName').value.trim();
    const backupType = document.getElementById('backupType').value;
    const createBtn = document.getElementById('createBackupBtn');
    
    if (!backupType) {
        showAdminMessage('Please select a backup type', 'error');
        return;
    }
    
    // Show loading state
    const originalText = createBtn.textContent;
    createBtn.disabled = true;
    createBtn.textContent = 'Creating Backup...';
    
    // Update status
    document.getElementById('backupStatus').textContent = 'Creating...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'create_backup');
        formData.append('backup_type', backupType);
        if (backupName) {
            formData.append('backup_name', backupName);
        }
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Backup created successfully!', 'success');
            
            // Clear form
            document.getElementById('backupName').value = '';
            document.getElementById('backupType').value = '';
            
            // Reload backup data
            loadBackupData();
        } else {
            showAdminMessage(data.message || 'Failed to create backup', 'error');
        }
    } catch (error) {
        console.error('Backup creation failed:', error);
        showAdminMessage('Failed to create backup. Please try again.', 'error');
    } finally {
        // Restore button state
        createBtn.disabled = false;
        createBtn.textContent = originalText;
        document.getElementById('backupStatus').textContent = 'Ready';
    }
}

function downloadBackup(backupName, backupType) {
    const url = `api/admin-backup.php?action=download_backup&backup_name=${encodeURIComponent(backupName)}&backup_type=${backupType}`;
    
    // Create temporary link and trigger download
    const a = document.createElement('a');
    a.href = url;
    a.download = `${backupName}_${backupType}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    
    showAdminMessage(`Downloading ${backupType} backup...`, 'info');
}

function openRestoreModal(backupName, hasDatabase, hasFiles) {
    const modal = document.createElement('div');
    modal.className = 'admin-modal';
    modal.style.display = 'flex';
    modal.id = 'restoreModal';
    
    const options = [];
    if (hasDatabase && hasFiles) {
        options.push('<option value="full">Full Restore (Database + Files)</option>');
    }
    if (hasDatabase) {
        options.push('<option value="database">Database Only</option>');
    }
    if (hasFiles) {
        options.push('<option value="files">Files Only</option>');
    }
    
    modal.innerHTML = `
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h2>🔄 Restore Backup</h2>
                <button class="admin-modal-close" onclick="closeRestoreModal()">✕</button>
            </div>
            <div class="admin-form">
                <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444;">
                    <h4 style="color: #991b1b; margin-bottom: 10px;">⚠️ Warning</h4>
                    <p style="color: #991b1b; margin: 0; line-height: 1.5;">
                        This will overwrite your current data. Make sure you have a recent backup before proceeding.
                        This action cannot be undone.
                    </p>
                </div>
                
                <div class="admin-form-group">
                    <label for="restoreType">Restore Type</label>
                    <select id="restoreType" class="admin-filter-select">
                        ${options.join('')}
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label>Backup Information</label>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div><strong>Backup Name:</strong> ${backupName}</div>
                        <div><strong>Available:</strong> ${hasDatabase ? 'Database' : ''} ${hasDatabase && hasFiles ? '+ ' : ''}${hasFiles ? 'Files' : ''}</div>
                    </div>
                </div>
            </div>
            <div class="admin-modal-actions">
                <button class="admin-btn admin-btn-secondary" onclick="closeRestoreModal()">Cancel</button>
                <button class="admin-btn admin-btn-primary" onclick="performRestore('${backupName}')">🔄 Restore Now</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeRestoreModal() {
    const modal = document.getElementById('restoreModal');
    if (modal) {
        modal.remove();
    }
}

async function performRestore(backupName) {
    const restoreType = document.getElementById('restoreType').value;
    const restoreBtn = document.querySelector('#restoreModal .admin-btn-primary');
    
    if (!restoreType) {
        showAdminMessage('Please select a restore type', 'error');
        return;
    }
    
    // Show loading state
    const originalText = restoreBtn.textContent;
    restoreBtn.disabled = true;
    restoreBtn.textContent = 'Restoring...';
    
    // Update status
    document.getElementById('backupStatus').textContent = 'Restoring...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'restore_backup');
        formData.append('backup_name', backupName);
        formData.append('restore_type', restoreType);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Restore completed successfully!', 'success');
            closeRestoreModal();
            
            // If database was restored, suggest page reload
            if (restoreType === 'database' || restoreType === 'full') {
                setTimeout(() => {
                    if (confirm('Database was restored. Would you like to reload the page to see the changes?')) {
                        window.location.reload();
                    }
                }, 2000);
            }
        } else {
            showAdminMessage(data.message || 'Restore failed', 'error');
        }
    } catch (error) {
        console.error('Restore failed:', error);
        showAdminMessage('Restore failed. Please try again.', 'error');
    } finally {
        // Restore button state
        restoreBtn.disabled = false;
        restoreBtn.textContent = originalText;
        document.getElementById('backupStatus').textContent = 'Ready';
    }
}

async function deleteBackup(backupName) {
    if (!confirm(`Are you sure you want to delete the backup "${backupName}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_backup');
        formData.append('backup_name', backupName);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Backup deleted successfully', 'success');
            loadBackupData(); // Reload the list
        } else {
            showAdminMessage(data.message || 'Failed to delete backup', 'error');
        }
    } catch (error) {
        console.error('Delete failed:', error);
        showAdminMessage('Failed to delete backup', 'error');
    }
}

function openBackupScheduleModal() {
    document.getElementById('backupScheduleModal').style.display = 'flex';
}

function closeBackupScheduleModal() {
    document.getElementById('backupScheduleModal').style.display = 'none';
}

function saveBackupSchedule() {
    // This would implement scheduled backup functionality
    showAdminMessage('Backup scheduling feature coming soon!', 'info');
    closeBackupScheduleModal();
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}


// Backup and Restore Functions
async function loadBackupData() {
    try {
        const response = await fetch('api/admin-backup.php?action=list_backups');
        const data = await response.json();
        
        if (data.success) {
            displayBackupsList(data.backups);
        }
    } catch (error) {
        console.error('Failed to load backup data:', error);
    }
}

function displayBackupsList(backups) {
    const tbody = document.getElementById('backupsTableBody');
    if (!tbody) return;
    
    if (backups.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No backups found</td></tr>';
        return;
    }
    
    tbody.innerHTML = backups.map(backup => `
        <tr>
            <td>
                <div style="font-weight: 600;">${backup.name}</div>
                <div style="font-size: 0.875rem; color: #64748b;">${formatAdminDateTime(backup.created_at)}</div>
            </td>
            <td>
                ${backup.database ? '<span class="status-badge approved">✅ Database</span>' : '<span class="status-badge pending">❌ Database</span>'}
                ${backup.files ? '<span class="status-badge approved">✅ Files</span>' : '<span class="status-badge pending">❌ Files</span>'}
            </td>
            <td>${formatFileSize(backup.size)}</td>
            <td>${formatAdminDate(backup.created_at)}</td>
            <td>
                ${backup.database && backup.files ? '<span class="status-badge approved">Complete</span>' : '<span class="status-badge pending">Partial</span>'}
            </td>
            <td>
                <button class="admin-action-btn view" onclick="downloadBackup('${backup.name}', 'database')" title="Download Database" ${!backup.database ? 'disabled' : ''}>💾</button>
                <button class="admin-action-btn view" onclick="downloadBackup('${backup.name}', 'files')" title="Download Files" ${!backup.files ? 'disabled' : ''}>📁</button>
                <button class="admin-action-btn approve" onclick="restoreBackup('${backup.name}')" title="Restore">🔄</button>
                <button class="admin-action-btn delete" onclick="deleteBackup('${backup.name}')" title="Delete">🗑️</button>
            </td>
        </tr>
    `).join('');
}

async function createBackup() {
    const backupType = document.getElementById('backupType').value;
    const backupName = document.getElementById('backupName').value.trim() || 'backup_' + new Date().toISOString().slice(0, 19).replace(/[:-]/g, '');
    
    if (!backupType) {
        showAdminMessage('Please select backup type', 'error');
        return;
    }
    
    // Show loading state
    const createBtn = document.getElementById('createBackupBtn');
    const originalText = createBtn.textContent;
    createBtn.disabled = true;
    createBtn.textContent = 'Creating Backup...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'create_backup');
        formData.append('backup_type', backupType);
        formData.append('backup_name', backupName);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Backup created successfully!', 'success');
            loadBackupData(); // Refresh the list
            
            // Reset form
            document.getElementById('backupName').value = '';
            document.getElementById('backupType').value = '';
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Backup creation failed:', error);
        showAdminMessage('Failed to create backup', 'error');
    } finally {
        // Restore button state
        createBtn.disabled = false;
        createBtn.textContent = originalText;
    }
}

async function restoreBackup(backupName) {
    if (!confirm(`Are you sure you want to restore backup "${backupName}"? This will overwrite current data and cannot be undone.`)) {
        return;
    }
    
    const restoreType = prompt('Restore type (database/files/full):', 'full');
    if (!restoreType || !['database', 'files', 'full'].includes(restoreType)) {
        showAdminMessage('Invalid restore type', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'restore_backup');
        formData.append('backup_name', backupName);
        formData.append('restore_type', restoreType);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Backup restored successfully!', 'success');
            
            // If database was restored, suggest page reload
            if (restoreType === 'database' || restoreType === 'full') {
                setTimeout(() => {
                    if (confirm('Database was restored. Would you like to reload the page to see changes?')) {
                        window.location.reload();
                    }
                }, 2000);
            }
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Restore failed:', error);
        showAdminMessage('Failed to restore backup', 'error');
    }
}

async function deleteBackup(backupName) {
    if (!confirm(`Are you sure you want to delete backup "${backupName}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_backup');
        formData.append('backup_name', backupName);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Backup deleted successfully!', 'success');
            loadBackupData(); // Refresh the list
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Delete failed:', error);
        showAdminMessage('Failed to delete backup', 'error');
    }
}

function downloadBackup(backupName, backupType) {
    const url = `api/admin-backup.php?action=download_backup&backup_name=${encodeURIComponent(backupName)}&backup_type=${backupType}`;
    const a = document.createElement('a');
    a.href = url;
    a.download = `${backupName}_${backupType}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function openBackupScheduleModal() {
    document.getElementById('backupScheduleModal').style.display = 'flex';
}

function closeBackupScheduleModal() {
    document.getElementById('backupScheduleModal').style.display = 'none';
}

async function saveBackupSchedule() {
    // This would implement automatic backup scheduling
    showAdminMessage('Backup scheduling feature coming soon!', 'info');
    closeBackupScheduleModal();
}

function closeActivityReportModal() {
    document.getElementById('activityReportModal').style.display = 'none';
}

// Backup & Restore functions
async function loadBackupData() {
    try {
        // Load backup statistics
        const statsResponse = await fetch('api/admin-backup.php?action=get_backup_stats');
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            updateBackupStats(statsData.stats);
        }
        
        // Load backups list
        const listResponse = await fetch('api/admin-backup.php?action=list_backups');
        const listData = await listResponse.json();
        
        if (listData.success) {
            displayBackupsList(listData.backups);
        }
        
    } catch (error) {
        console.error('Failed to load backup data:', error);
        showAdminMessage('Failed to load backup data', 'error');
    }
}

function updateBackupStats(stats) {
    document.getElementById('totalBackups').textContent = stats.total_backups || 0;
    document.getElementById('totalBackupSize').textContent = stats.total_size_formatted || '0 B';
    document.getElementById('latestBackup').textContent = stats.latest_backup || 'Never';
    document.getElementById('backupStatus').textContent = stats.status || 'Ready';
}

function displayBackupsList(backups) {
    const container = document.getElementById('backupsGrid');
    
    if (!backups || backups.length === 0) {
        container.innerHTML = `
            <div class="backup-empty-state">
                <div class="backup-empty-icon">💾</div>
                <h3>No Backups Found</h3>
                <p>Create your first backup to get started with data protection.</p>
                <button class="backup-btn backup-btn-primary" onclick="document.getElementById('backupType').focus()">
                    <span class="backup-btn-icon">💾</span>
                    <span class="backup-btn-text">Create First Backup</span>
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = backups.map(backup => `
        <div class="backup-card">
            <div class="backup-card-header">
                <div>
                    <h3 class="backup-card-title">${backup.name}</h3>
                    <p class="backup-card-date">${formatAdminDate(backup.created_at)}</p>
                </div>
                <span class="backup-status-badge ${backup.status.toLowerCase()}">${backup.status}</span>
            </div>
            
            <div class="backup-card-content">
                <div class="backup-type-indicators">
                    <div class="backup-type-indicator ${backup.database ? 'database' : 'unavailable'}">
                        🗄️ Database ${backup.database ? '✓' : '✗'}
                    </div>
                    <div class="backup-type-indicator ${backup.files ? 'files' : 'unavailable'}">
                        📁 Files ${backup.files ? '✓' : '✗'}
                    </div>
                </div>
                <div class="backup-size-info">
                    Size: ${backup.size_formatted}
                </div>
            </div>
            
            <div class="backup-card-actions">
                <button class="backup-action-btn restore" onclick="openRestoreModal('${backup.name}')" title="Restore Backup">
                    🔄 Restore
                </button>
                ${backup.database ? `
                    <a href="api/admin-backup.php?action=download_backup&backup_name=${backup.name}&backup_type=database" 
                       class="backup-action-btn download" title="Download Database">
                        💾 DB
                    </a>
                ` : ''}
                ${backup.files ? `
                    <a href="api/admin-backup.php?action=download_backup&backup_name=${backup.name}&backup_type=files" 
                       class="backup-action-btn download" title="Download Files">
                        📁 Files
                    </a>
                ` : ''}
                <button class="backup-action-btn delete" onclick="deleteBackup('${backup.name}')" title="Delete Backup">
                    🗑️ Delete
                </button>
            </div>
        </div>
    `).join('');
}

async function createBackup() {
    const backupName = document.getElementById('backupName').value.trim();
    const backupType = document.getElementById('backupType').value;
    
    if (!backupType) {
        showAdminMessage('Please select a backup type', 'error');
        return;
    }
    
    const createBtn = document.getElementById('createBackupBtn');
    const originalText = createBtn.innerHTML;
    
    // Show loading state
    createBtn.disabled = true;
    createBtn.innerHTML = `
        <span class="backup-btn-icon">⏳</span>
        <span class="backup-btn-text">Creating Backup...</span>
    `;
    
    try {
        const formData = new FormData();
        formData.append('action', 'create_backup');
        formData.append('backup_name', backupName);
        formData.append('backup_type', backupType);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            
            // Clear form
            document.getElementById('backupName').value = '';
            document.getElementById('backupType').value = '';
            hideBackupTypeInfo();
            
            // Reload backup data
            loadBackupData();
        } else {
            showAdminMessage(data.message, data.has_failures ? 'error' : 'error');
        }
        
    } catch (error) {
        console.error('Backup creation failed:', error);
        showAdminMessage('Backup creation failed. Please try again.', 'error');
    } finally {
        // Restore button state
        createBtn.disabled = false;
        createBtn.innerHTML = originalText;
    }
}

function openRestoreModal(backupName) {
    const modal = document.getElementById('backupRestoreModal');
    const title = document.getElementById('restoreModalTitle');
    
    title.textContent = `🔄 Restore Backup: ${backupName}`;
    modal.dataset.backupName = backupName;
    
    // Reset form
    document.querySelector('input[name="restore_type"][value="full"]').checked = true;
    document.getElementById('restoreConfirmation').checked = false;
    document.getElementById('confirmRestoreBtn').disabled = true;
    
    modal.style.display = 'flex';
}

function closeBackupRestoreModal() {
    document.getElementById('backupRestoreModal').style.display = 'none';
}

async function confirmRestore() {
    const modal = document.getElementById('backupRestoreModal');
    const backupName = modal.dataset.backupName;
    const restoreType = document.querySelector('input[name="restore_type"]:checked').value;
    
    if (!document.getElementById('restoreConfirmation').checked) {
        showAdminMessage('Please confirm that you understand the risks', 'error');
        return;
    }
    
    const confirmBtn = document.getElementById('confirmRestoreBtn');
    const originalText = confirmBtn.textContent;
    
    // Show loading state
    confirmBtn.disabled = true;
    confirmBtn.textContent = '🔄 Restoring...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'restore_backup');
        formData.append('backup_name', backupName);
        formData.append('restore_type', restoreType);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            closeBackupRestoreModal();
            
            // If database was restored, suggest page reload
            if (restoreType === 'database' || restoreType === 'full') {
                setTimeout(() => {
                    if (confirm('Database has been restored. Would you like to reload the page to see the changes?')) {
                        window.location.reload();
                    }
                }, 2000);
            }
        } else {
            showAdminMessage(data.message, 'error');
        }
        
    } catch (error) {
        console.error('Restore failed:', error);
        showAdminMessage('Restore failed. Please try again.', 'error');
    } finally {
        // Restore button state
        confirmBtn.disabled = false;
        confirmBtn.textContent = originalText;
    }
}

async function deleteBackup(backupName) {
    if (!confirm(`Are you sure you want to delete the backup "${backupName}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_backup');
        formData.append('backup_name', backupName);
        
        const response = await fetch('api/admin-backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage(data.message, 'success');
            loadBackupData(); // Reload backup data
        } else {
            showAdminMessage(data.message, 'error');
        }
        
    } catch (error) {
        console.error('Delete failed:', error);
        showAdminMessage('Delete failed. Please try again.', 'error');
    }
}

function openBackupScheduleModal() {
    document.getElementById('backupScheduleModal').style.display = 'flex';
}

function closeBackupScheduleModal() {
    document.getElementById('backupScheduleModal').style.display = 'none';
}

function saveBackupSchedule() {
    // This would implement scheduled backup functionality
    showAdminMessage('Backup scheduling feature coming soon!', 'info');
    closeBackupScheduleModal();
}

// Show/hide backup type info based on selection
document.addEventListener('DOMContentLoaded', function() {
    const backupTypeSelect = document.getElementById('backupType');
    const restoreConfirmation = document.getElementById('restoreConfirmation');
    const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
    
    if (backupTypeSelect) {
        backupTypeSelect.addEventListener('change', function() {
            const infoDiv = document.getElementById('backupTypeInfo');
            if (this.value) {
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });
    }
    
    if (restoreConfirmation && confirmRestoreBtn) {
        restoreConfirmation.addEventListener('change', function() {
            confirmRestoreBtn.disabled = !this.checked;
        });
    }
});

function hideBackupTypeInfo() {
    const infoDiv = document.getElementById('backupTypeInfo');
    if (infoDiv) {
        infoDiv.style.display = 'none';
    }
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