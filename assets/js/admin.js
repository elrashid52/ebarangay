// E-Barangay Admin Portal JavaScript - Updated for Unified Auth
let currentAdmin = null;
let currentRequestReview = null;
let currentActivitiesPage = 1;
let activitiesFilters = {};

// Initialize admin app
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin portal initializing...');
    checkAdminSession();
    initializeAdminEventListeners();
});

// Check if admin is logged in using unified auth
async function checkAdminSession() {
    console.log('Checking admin session...');
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_session'
        });
        
        const data = await response.json();
        console.log('Session check response:', data);
        
        if (data.success && data.user.type === 'admin') {
            currentAdmin = data.user;
            console.log('Admin session found:', currentAdmin);
            showAdminDashboard();
        } else if (data.success && data.user.type === 'resident') {
            // Resident logged in, redirect to resident portal
            console.log('Resident detected on admin portal, redirecting...');
            window.location.href = 'index.php';
        } else {
            console.log('No admin session found, redirecting to unified login...');
            // No session, redirect to unified login page
            window.location.href = 'index.php';
        }
    } catch (error) {
        console.error('Admin session check failed:', error);
        // On error, redirect to unified login page
        window.location.href = 'index.php';
    }
}

// Initialize event listeners
function initializeAdminEventListeners() {
    console.log('Initializing admin event listeners...');
    
    // Admin navigation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.admin-nav-item')) {
            e.preventDefault();
            const page = e.target.getAttribute('data-admin-page');
            if (page) {
                showAdminPage(page);
            }
        }
        
        if (e.target.matches('.admin-modal-close') || (e.target.matches('.admin-modal') && e.target === e.currentTarget)) {
            closeAdminModals();
        }
    });

    // Admin sign in form (if it exists on this page)
    const adminSignInForm = document.getElementById('adminSignInForm');
    if (adminSignInForm) {
        console.log('Admin sign in form found');
        adminSignInForm.addEventListener('submit', handleAdminSignIn);
    }

    // Add resident form
    const addResidentForm = document.getElementById('addResidentForm');
    if (addResidentForm) {
        addResidentForm.addEventListener('submit', handleAddResident);
    }

    // Search and filter functionality
    const residentsSearch = document.getElementById('residentsSearch');
    if (residentsSearch) {
        residentsSearch.addEventListener('input', filterResidents);
    }

    const requestsSearch = document.getElementById('requestsSearch');
    if (requestsSearch) {
        requestsSearch.addEventListener('input', filterRequests);
    }

    const residentsStatusFilter = document.getElementById('residentsStatusFilter');
    if (residentsStatusFilter) {
        residentsStatusFilter.addEventListener('change', filterResidents);
    }

    const requestsStatusFilter = document.getElementById('requestsStatusFilter');
    if (requestsStatusFilter) {
        requestsStatusFilter.addEventListener('change', filterRequests);
    }

    const requestsTypeFilter = document.getElementById('requestsTypeFilter');
    if (requestsTypeFilter) {
        requestsTypeFilter.addEventListener('change', filterRequests);
    }

    // Activities search
    const activitiesSearch = document.getElementById('activitiesSearch');
    if (activitiesSearch) {
        activitiesSearch.addEventListener('input', debounce(filterActivities, 300));
    }
}

// Authentication functions using unified auth
async function handleAdminSignIn(e) {
    e.preventDefault();
    console.log('Admin sign in form submitted');
    
    const formData = new FormData(e.target);
    formData.append('action', 'login');
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    
    try {
        console.log('Sending login request to unified auth...');
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Login response:', data);
        
        if (data.success) {
            if (data.user.type === 'admin' || data.redirect === 'admin') {
                currentAdmin = data.user;
                console.log('Admin login successful:', currentAdmin);
                showAdminMessage('Login successful!', 'success');
                
                // Small delay to show success message
                setTimeout(() => {
                    showAdminDashboard();
                }, 1000);
            } else {
                // Resident logged in, redirect to resident portal
                console.log('Resident login detected, redirecting...');
                window.location.href = 'index.php';
            }
        } else {
            console.log('Admin login failed:', data.message);
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Admin login request failed:', error);
        showAdminMessage('Login failed. Please try again. Check console for details.', 'error');
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function adminSignOut() {
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=logout'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentAdmin = null;
            showAdminMessage('Signed out successfully!', 'success');
            
            // ALWAYS redirect to unified login page (index.php)
            console.log('Admin signing out, redirecting to unified login page...');
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
    console.log('Redirecting to unified login page...');
    window.location.href = 'index.php';
}

function showAdminDashboard() {
    console.log('Showing admin dashboard');
    const authContainer = document.getElementById('adminAuthContainer');
    const dashboardContainer = document.getElementById('adminDashboardContainer');
    
    if (authContainer) authContainer.style.display = 'none';
    if (dashboardContainer) dashboardContainer.style.display = 'flex';
    
    showAdminPage('dashboard');
}

function showAdminPage(page) {
    console.log('Showing admin page:', page);
    
    // Hide all pages
    const pages = document.querySelectorAll('.admin-page');
    pages.forEach(p => p.style.display = 'none');
    
    // Remove active class from nav items
    const navItems = document.querySelectorAll('.admin-nav-item');
    navItems.forEach(item => item.classList.remove('active'));
    
    // Show selected page
    const targetPage = document.getElementById(`admin${page.charAt(0).toUpperCase() + page.slice(1)}Page`);
    if (targetPage) {
        targetPage.style.display = 'block';
    } else {
        console.error('Target page not found:', `admin${page.charAt(0).toUpperCase() + page.slice(1)}Page`);
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
            loadResidentsData();
            break;
        case 'requests':
            loadAdminRequestsData();
            break;
        case 'activities':
            loadActivitiesData();
            break;
        case 'blotter':
            // Blotter functionality coming soon
            break;
        case 'users':
            // User management functionality coming soon
            break;
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
        
        // Load recent requests and blotters
        loadAdminRecentRequests();
        loadAdminRecentBlotters();
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
            <td><span class="status-badge ${request.status}">${formatStatus(request.status)}</span></td>
            <td>${formatDate(request.created_at)}</td>
        </tr>
    `).join('');
}

async function loadAdminRecentBlotters() {
    try {
        // For now, show empty state since blotter system is not implemented
        const tbody = document.getElementById('adminRecentBlottersBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #64748b;">No blotter reports</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load recent blotters:', error);
    }
}

// Activities functions
async function loadActivitiesData() {
    try {
        // Load activity statistics
        await loadActivityStats();
        
        // Load activities list
        await loadActivitiesList();
    } catch (error) {
        console.error('Failed to load activities data:', error);
    }
}

async function loadActivityStats() {
    try {
        const dateFrom = document.getElementById('activitiesDateFrom').value || '';
        const dateTo = document.getElementById('activitiesDateTo').value || '';
        
        const params = new URLSearchParams({
            action: 'get_activity_stats',
            date_from: dateFrom,
            date_to: dateTo
        });
        
        const response = await fetch(`api/admin-activities.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            updateActivityStats(data.admin_stats, data.user_stats);
        }
    } catch (error) {
        console.error('Failed to load activity stats:', error);
    }
}

function updateActivityStats(adminStats, userStats) {
    document.getElementById('totalAdminActivities').textContent = adminStats.total_admin_activities || 0;
    document.getElementById('totalUserActivities').textContent = userStats.total_user_activities || 0;
    document.getElementById('totalAdminLogins').textContent = adminStats.admin_logins || 0;
    document.getElementById('totalUserLogins').textContent = userStats.user_logins || 0;
}

async function loadActivitiesList(page = 1) {
    try {
        const params = new URLSearchParams({
            action: 'get_activities',
            page: page,
            limit: 50,
            ...activitiesFilters
        });
        
        const response = await fetch(`api/admin-activities.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayActivitiesTable(data.activities);
            updateActivitiesPagination(data.page, data.total_pages, data.total);
            currentActivitiesPage = data.page;
        }
    } catch (error) {
        console.error('Failed to load activities list:', error);
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
            <td>${formatDateTime(activity.created_at)}</td>
            <td>
                <div style="display: flex; flex-direction: column;">
                    <strong>${activity.admin_name}</strong>
                    <small style="color: #64748b;">${activity.admin_email}</small>
                </div>
            </td>
            <td>
                <span class="status-badge ${activity.activity_type === 'admin' ? 'approved' : 'pending'}">
                    ${activity.activity_type}
                </span>
            </td>
            <td>
                <span style="font-weight: 600; color: #374151;">${activity.action}</span>
            </td>
            <td>
                ${activity.target_type ? `
                    <div style="display: flex; flex-direction: column;">
                        <span>${activity.target_type}</span>
                        ${activity.target_id ? `<small style="color: #64748b;">ID: ${activity.target_id}</small>` : ''}
                    </div>
                ` : '-'}
            </td>
            <td>
                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${activity.details || ''}">
                    ${activity.details || '-'}
                </div>
            </td>
            <td>
                <span style="font-family: monospace; font-size: 0.8rem;">${activity.ip_address}</span>
            </td>
        </tr>
    `).join('');
}

function updateActivitiesPagination(currentPage, totalPages, totalItems) {
    const pagination = document.getElementById('activitiesPagination');
    const pageInfo = document.getElementById('activitiesPageInfo');
    const prevBtn = document.getElementById('prevActivitiesBtn');
    const nextBtn = document.getElementById('nextActivitiesBtn');
    
    if (pagination && pageInfo && prevBtn && nextBtn) {
        pagination.style.display = totalPages > 1 ? 'flex' : 'none';
        pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${totalItems} items)`;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
    }
}

function loadActivitiesPage(page) {
    if (page < 1) return;
    loadActivitiesList(page);
}

function filterActivities() {
    // Update filters object
    activitiesFilters = {
        date_from: document.getElementById('activitiesDateFrom').value || '',
        date_to: document.getElementById('activitiesDateTo').value || '',
        activity_type: document.getElementById('activitiesTypeFilter').value || 'all',
        action_filter: document.getElementById('activitiesActionFilter').value || '',
        admin_filter: document.getElementById('activitiesSearch').value || ''
    };
    
    // Reset to first page and load
    currentActivitiesPage = 1;
    loadActivitiesList(1);
    loadActivityStats();
}

// Print Activity Report
async function printActivityReport() {
    try {
        // Get current filters
        const dateFrom = document.getElementById('activitiesDateFrom').value || '';
        const dateTo = document.getElementById('activitiesDateTo').value || '';
        const activityType = document.getElementById('activitiesTypeFilter').value || 'all';
        const actionFilter = document.getElementById('activitiesActionFilter').value || '';
        
        // Get all activities for the report (no pagination)
        const params = new URLSearchParams({
            action: 'get_activities',
            page: 1,
            limit: 1000, // Get more records for the report
            date_from: dateFrom,
            date_to: dateTo,
            activity_type: activityType,
            action_filter: actionFilter
        });
        
        const response = await fetch(`api/admin-activities.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            generateActivityReport(data.activities, {
                dateFrom: dateFrom || 'All time',
                dateTo: dateTo || 'Present',
                activityType: activityType,
                actionFilter: actionFilter
            });
        }
    } catch (error) {
        console.error('Failed to generate activity report:', error);
        showAdminMessage('Failed to generate report', 'error');
    }
}

function generateActivityReport(activities, filters) {
    const modal = document.getElementById('activityReportModal');
    const content = document.getElementById('activityReportContent');
    
    const reportHtml = `
        <div class="report-header">
            <div class="report-title">E-BARANGAY ACTIVITY REPORT</div>
            <div class="report-subtitle">Barangay Sample - Administrative Portal</div>
            <div class="report-date-range">
                Report Period: ${filters.dateFrom} to ${filters.dateTo}
                ${filters.activityType !== 'all' ? `| Activity Type: ${filters.activityType}` : ''}
                ${filters.actionFilter ? `| Action Filter: ${filters.actionFilter}` : ''}
            </div>
            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                Generated on: ${new Date().toLocaleString()}
            </div>
        </div>
        
        <div class="report-summary" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div><strong>Total Activities:</strong> ${activities.length}</div>
                <div><strong>Admin Activities:</strong> ${activities.filter(a => a.activity_type === 'admin').length}</div>
                <div><strong>User Activities:</strong> ${activities.filter(a => a.activity_type.includes('user')).length}</div>
                <div><strong>Login Activities:</strong> ${activities.filter(a => a.action === 'login').length}</div>
            </div>
        </div>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Date & Time</th>
                    <th style="width: 20%;">User/Admin</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 15%;">Action</th>
                    <th style="width: 15%;">Target</th>
                    <th style="width: 25%;">Details</th>
                </tr>
            </thead>
            <tbody>
                ${activities.map((activity, index) => `
                    <tr ${index > 0 && index % 25 === 0 ? 'class="page-break"' : ''}>
                        <td>${formatDateTime(activity.created_at)}</td>
                        <td>
                            <div style="font-size: 11px;">
                                <strong>${activity.admin_name}</strong><br>
                                <span style="color: #666;">${activity.admin_email}</span>
                            </div>
                        </td>
                        <td>${activity.activity_type}</td>
                        <td>${activity.action}</td>
                        <td>
                            ${activity.target_type ? `${activity.target_type}${activity.target_id ? ` (${activity.target_id})` : ''}` : '-'}
                        </td>
                        <td style="font-size: 10px; max-width: 200px; word-wrap: break-word;">
                            ${activity.details || '-'}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
        
        <div class="report-footer">
            <div>E-Barangay Administrative System | Generated by: ${currentAdmin?.name || 'Admin'}</div>
            <div>This is a computer-generated report. Page ${Math.ceil(activities.length / 25)} of ${Math.ceil(activities.length / 25)}</div>
        </div>
    `;
    
    content.innerHTML = reportHtml;
    modal.classList.add('active');
    modal.style.display = 'flex';
}

function closeActivityReportModal() {
    const modal = document.getElementById('activityReportModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
}

// Residents management
async function loadResidentsData() {
    try {
        const response = await fetch('api/admin-residents.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayResidentsTable(data.residents);
        }
    } catch (error) {
        console.error('Failed to load residents:', error);
    }
}

function displayResidentsTable(residents) {
    const tbody = document.getElementById('adminResidentsTableBody');
    if (!tbody) return;
    
    if (residents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No residents found</td></tr>';
        return;
    }
    
    tbody.innerHTML = residents.map(resident => `
        <tr>
            <td>
                <div style="display: flex; flex-direction: column;">
                    <strong>${resident.first_name} ${resident.last_name}</strong>
                    <small style="color: #64748b;">${resident.email}</small>
                </div>
            </td>
            <td>
                <div style="display: flex; flex-direction: column;">
                    <span>${resident.mobile_number || 'N/A'}</span>
                    <small style="color: #64748b;">${resident.civil_status || 'N/A'}</small>
                </div>
            </td>
            <td>
                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                    ${formatAddress(resident)}
                </div>
            </td>
            <td><span class="status-badge ${resident.status || 'Active'}">${resident.status || 'Active'}</span></td>
            <td>${formatDate(resident.created_at)}</td>
            <td>
                <div style="display: flex; gap: 4px;">
                    <button class="admin-action-btn view" onclick="viewResident(${resident.id})" title="View Details">👁️</button>
                    <button class="admin-action-btn edit" onclick="editResident(${resident.id})" title="Edit">✏️</button>
                    <button class="admin-action-btn delete" onclick="deleteResident(${resident.id})" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function formatAddress(resident) {
    const parts = [
        resident.house_no,
        resident.street,
        resident.purok,
        resident.barangay,
        resident.city
    ].filter(part => part && part.trim() !== '');
    
    return parts.length > 0 ? parts.join(', ') : 'No address provided';
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
        console.error('Failed to load admin requests:', error);
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
                <div style="display: flex; flex-direction: column;">
                    <strong>${request.resident_name}</strong>
                    <small style="color: #64748b;">${request.resident_email}</small>
                </div>
            </td>
            <td>${request.type}</td>
            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">${request.purpose}</td>
            <td><span class="status-badge ${request.status}">${formatStatus(request.status)}</span></td>
            <td>₱${parseFloat(request.processing_fee || 0).toFixed(2)}</td>
            <td>${formatDate(request.created_at)}</td>
            <td>
                <div style="display: flex; gap: 4px;">
                    <button class="admin-action-btn view" onclick="reviewAdminRequest(${request.id})" title="Review Request">📋</button>
                    ${request.status === 'pending' ? `
                        <button class="admin-action-btn approve" onclick="approveRequest(${request.id})" title="Quick Approve">✅</button>
                        <button class="admin-action-btn reject" onclick="rejectRequest(${request.id})" title="Quick Reject">❌</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// Request Review Modal Functions
async function reviewAdminRequest(requestId) {
    try {
        const response = await fetch(`api/admin-requests.php?action=get_details&id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
            currentRequestReview = data.request;
            showAdminRequestReviewModal(data.request);
        }
    } catch (error) {
        console.error('Failed to load request details:', error);
    }
}

function showAdminRequestReviewModal(request) {
    const modal = document.getElementById('adminRequestReviewModal');
    
    // Store request ID in modal for document viewing
    modal.dataset.requestId = request.id;
    
    // Update modal title
    document.getElementById('adminRequestReviewTitle').textContent = 
        `📄 Certificate Request Review - ${request.type}`;
    
    // Update request info
    document.getElementById('reviewResidentName').textContent = request.resident_name;
    document.getElementById('reviewResidentEmail').textContent = request.resident_email;
    document.getElementById('reviewCertificateType').textContent = request.type;
    document.getElementById('reviewCertificateDelivery').textContent = 
        request.type.toLowerCase().includes('barangay id') ? '📍 Pickup Only' : '📥 Downloadable PDF';
    
    // Update status
    const statusElement = document.getElementById('reviewCurrentStatus');
    statusElement.textContent = formatStatus(request.status);
    statusElement.className = `status-badge ${request.status}`;
    
    // Update payment info
    document.getElementById('reviewPaymentFee').textContent = `₱${parseFloat(request.processing_fee || 0).toFixed(2)}`;
    
    const details = request.request_details ? JSON.parse(request.request_details) : {};
    document.getElementById('reviewPaymentMethod').textContent = 
        details.payment_method === 'gcash' ? 'GCash' : 'Bank Transfer';
    document.getElementById('reviewPaymentReference').textContent = 
        details.payment_reference || 'N/A';
    
    // Update purpose
    document.getElementById('reviewPurpose').textContent = request.purpose;
    
    // Update status select
    document.getElementById('reviewStatusSelect').value = request.status;
    
    // Reset certificate upload
    const uploadInput = document.getElementById('certificateUpload');
    if (uploadInput) {
        uploadInput.value = '';
        const placeholder = uploadInput.parentElement.querySelector('.upload-placeholder');
        if (placeholder) {
            placeholder.innerHTML = `
                <div class="upload-icon">📁</div>
                <div class="upload-text">
                    <div class="upload-title">Choose File</div>
                    <div class="upload-subtitle">No file chosen</div>
                </div>
            `;
            placeholder.style.borderColor = '#d1d5db';
            placeholder.style.background = 'white';
        }
    }
    
    // Show modal
    modal.classList.add('active');
    modal.style.display = 'flex';
}

function closeAdminRequestReviewModal() {
    const modal = document.getElementById('adminRequestReviewModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
    currentRequestReview = null;
}

// Certificate Upload Handler
function handleCertificateUpload(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    if (file.type !== 'application/pdf') {
        showAdminMessage('Please upload a PDF file only', 'error');
        input.value = '';
        return;
    }
    
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showAdminMessage('File size must be less than 5MB', 'error');
        input.value = '';
        return;
    }
    
    // Update UI to show uploaded file
    const placeholder = input.parentElement.querySelector('.upload-placeholder');
    placeholder.innerHTML = `
        <div class="upload-icon">✅</div>
        <div class="upload-text">
            <div class="upload-title">Certificate Uploaded</div>
            <div class="upload-subtitle">${file.name}</div>
        </div>
    `;
    placeholder.style.borderColor = '#10b981';
    placeholder.style.background = '#ecfdf5';
}

// Document Viewer
function viewDocument(documentType) {
    // Get the current request ID from the modal
    const modal = document.getElementById('adminRequestReviewModal');
    const requestId = modal ? modal.dataset.requestId : null;
    
    if (!requestId) {
        console.error('Request ID not found');
        showAdminMessage('Request ID not found', 'error');
        return;
    }
    
    // Map document types to the actual field names used in the form
    const documentTypeMap = {
        'valid_id': 'document_valid_government_issued_id_with_address',
        'cedula': 'document_cedula',
        'proof_billing': 'document_proof_of_billing_proof_of_residency_if_not_on_id'
    };
    
    const actualDocumentType = documentTypeMap[documentType] || documentType;
    
    console.log('Viewing document:', {
        requestId: requestId,
        documentType: documentType,
        actualDocumentType: actualDocumentType
    });
    
    const url = `api/admin-requests.php?action=view_document&request_id=${requestId}&document_type=${actualDocumentType}`;
    
    // Open in new tab
    const newWindow = window.open(url, '_blank');
    
    // Check if popup was blocked
    if (!newWindow) {
        showAdminMessage('Popup blocked. Please allow popups for this site.', 'error');
    }
}

// Approve and Upload Request
async function approveAndUploadRequest() {
    if (!currentRequestReview) return;
    
    const certificateFile = document.getElementById('certificateUpload').files[0];
    const newStatus = document.getElementById('reviewStatusSelect').value;
    
    if (newStatus === 'approved' && !certificateFile) {
        showAdminMessage('Please upload the certificate PDF before approving', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'approve');
        formData.append('id', currentRequestReview.id);
        formData.append('status', newStatus);
        
        if (certificateFile) {
            formData.append('certificate', certificateFile);
        }
        
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Request processed successfully!', 'success');
            closeAdminRequestReviewModal();
            loadAdminRequestsData();
            loadAdminDashboardData();
            
            // Log activity
            logAdminActivity('approve_request', 'request', currentRequestReview.id, 
                `Approved ${currentRequestReview.type} request for ${currentRequestReview.resident_name}`);
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to process request:', error);
        showAdminMessage('Failed to process request', 'error');
    }
}

// Reject Request with Reason
async function rejectRequestWithReason() {
    if (!currentRequestReview) return;
    
    const reason = prompt('Please provide a reason for rejection:');
    if (!reason) return;
    
    try {
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&id=${currentRequestReview.id}&reason=${encodeURIComponent(reason)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Request rejected successfully!', 'success');
            closeAdminRequestReviewModal();
            loadAdminRequestsData();
            loadAdminDashboardData();
            
            // Log activity
            logAdminActivity('reject_request', 'request', currentRequestReview.id, 
                `Rejected ${currentRequestReview.type} request for ${currentRequestReview.resident_name}: ${reason}`);
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to reject request:', error);
        showAdminMessage('Failed to reject request', 'error');
    }
}

// Activity Logging Function
async function logAdminActivity(action, targetType = null, targetId = null, details = null) {
    try {
        const response = await fetch('api/admin-activities.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=log_activity&log_action=${encodeURIComponent(action)}&target_type=${encodeURIComponent(targetType || '')}&target_id=${encodeURIComponent(targetId || '')}&details=${encodeURIComponent(details || '')}`
        });
        
        const data = await response.json();
        if (!data.success) {
            console.error('Failed to log activity:', data.message);
        }
    } catch (error) {
        console.error('Failed to log activity:', error);
    }
}

// Request actions (legacy functions for quick actions)
async function viewAdminRequest(requestId) {
    try {
        const response = await fetch(`api/admin-requests.php?action=get_details&id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
            showAdminRequestModal(data.request);
        }
    } catch (error) {
        console.error('Failed to load request details:', error);
    }
}

function showAdminRequestModal(request) {
    const modal = document.getElementById('adminRequestModal');
    const title = document.getElementById('adminRequestModalTitle');
    const body = document.getElementById('adminRequestModalBody');
    
    title.textContent = `Request #${request.id} - ${request.type}`;
    
    const details = request.request_details ? JSON.parse(request.request_details) : {};
    
    body.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Resident</label>
                <div>${request.resident_name}</div>
                <div style="color: #64748b; font-size: 0.875rem;">${request.resident_email}</div>
            </div>
            <div>
                <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Certificate Type</label>
                <div>${request.type}</div>
            </div>
            <div>
                <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Status</label>
                <div><span class="status-badge ${request.status}">${formatStatus(request.status)}</span></div>
            </div>
            <div>
                <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Processing Fee</label>
                <div>₱${parseFloat(request.processing_fee || 0).toFixed(2)}</div>
            </div>
            <div>
                <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Request Date</label>
                <div>${formatDate(request.created_at)}</div>
            </div>
            ${request.processed_at ? `
            <div>
                <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Processed Date</label>
                <div>${formatDate(request.processed_at)}</div>
            </div>
            ` : ''}
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Purpose</label>
            <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-top: 4px;">${request.purpose}</div>
        </div>
        
        ${details.payment_method ? `
        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Payment Information</label>
            <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-top: 4px;">
                <div><strong>Method:</strong> ${details.payment_method === 'gcash' ? 'GCash/PayMaya' : 'Bank Transfer'}</div>
                ${details.payment_reference ? `<div><strong>Reference:</strong> ${details.payment_reference}</div>` : ''}
            </div>
        </div>
        ` : ''}
        
        ${request.admin_notes ? `
        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; color: #374151; font-size: 0.875rem;">Admin Notes</label>
            <div style="padding: 12px; background: #fef2f2; border-radius: 8px; margin-top: 4px; border-left: 4px solid #ef4444;">${request.admin_notes}</div>
        </div>
        ` : ''}
        
        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            ${request.status === 'pending' ? `
                <button class="admin-btn admin-btn-secondary" onclick="approveRequest(${request.id}); closeAdminRequestModal();">✅ Approve</button>
                <button class="admin-btn admin-btn-secondary" onclick="rejectRequest(${request.id}); closeAdminRequestModal();" style="background: #fef2f2; color: #dc2626;">❌ Reject</button>
            ` : ''}
            <button class="admin-btn admin-btn-secondary" onclick="closeAdminRequestModal()">Close</button>
        </div>
    `;
    
    modal.classList.add('active');
    modal.style.display = 'flex';
}

function closeAdminRequestModal() {
    const modal = document.getElementById('adminRequestModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
}

async function approveRequest(requestId) {
    if (!confirm('Are you sure you want to approve this request?')) return;
    
    try {
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&id=${requestId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Request approved successfully!', 'success');
            loadAdminRequestsData();
            loadAdminDashboardData();
            
            // Log activity
            logAdminActivity('approve_request', 'request', requestId, 'Quick approved request');
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to approve request:', error);
        showAdminMessage('Failed to approve request', 'error');
    }
}

async function rejectRequest(requestId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (!reason) return;
    
    try {
        const response = await fetch('api/admin-requests.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&id=${requestId}&reason=${encodeURIComponent(reason)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Request rejected successfully!', 'success');
            loadAdminRequestsData();
            loadAdminDashboardData();
            
            // Log activity
            logAdminActivity('reject_request', 'request', requestId, `Quick rejected request: ${reason}`);
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to reject request:', error);
        showAdminMessage('Failed to reject request', 'error');
    }
}

// Resident actions
function viewResident(residentId) {
    // Implement view resident details
    showAdminMessage('View resident functionality coming soon', 'info');
    
    // Log activity
    logAdminActivity('view_resident', 'resident', residentId, 'Viewed resident details');
}

function editResident(residentId) {
    // Implement edit resident
    showAdminMessage('Edit resident functionality coming soon', 'info');
    
    // Log activity
    logAdminActivity('edit_resident', 'resident', residentId, 'Opened resident edit form');
}

async function deleteResident(residentId) {
    if (!confirm('Are you sure you want to delete this resident? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('api/admin-residents.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${residentId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Resident deleted successfully!', 'success');
            loadResidentsData();
            loadAdminDashboardData();
            
            // Log activity
            logAdminActivity('delete_resident', 'resident', residentId, 'Deleted resident account');
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to delete resident:', error);
        showAdminMessage('Failed to delete resident', 'error');
    }
}

// Add resident modal
function openAddResidentModal() {
    document.getElementById('addResidentModal').classList.add('active');
    document.getElementById('addResidentModal').style.display = 'flex';
    
    // Log activity
    logAdminActivity('open_add_resident', 'resident', null, 'Opened add resident form');
}

function closeAddResidentModal() {
    document.getElementById('addResidentModal').style.display = 'none';
    document.getElementById('addResidentForm').reset();
}

async function handleAddResident(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'add');
    formData.append('password', 'resident123'); // Default password
    
    try {
        const response = await fetch('api/admin-residents.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAdminMessage('Resident added successfully!', 'success');
            closeAddResidentModal();
            loadResidentsData();
            loadAdminDashboardData();
            
            // Log activity
            logAdminActivity('add_resident', 'resident', null, `Added new resident: ${formData.get('first_name')} ${formData.get('last_name')}`);
        } else {
            showAdminMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to add resident:', error);
        showAdminMessage('Failed to add resident', 'error');
    }
}

// Filter functions
function filterResidents() {
    const searchTerm = document.getElementById('residentsSearch').value.toLowerCase();
    const statusFilter = document.getElementById('residentsStatusFilter').value;
    const rows = document.querySelectorAll('#adminResidentsTableBody tr');
    
    rows.forEach(row => {
        const name = row.cells[0]?.textContent.toLowerCase() || '';
        const email = row.cells[0]?.textContent.toLowerCase() || '';
        const status = row.cells[3]?.textContent.trim() || '';
        
        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterRequests() {
    const searchTerm = document.getElementById('requestsSearch').value.toLowerCase();
    const statusFilter = document.getElementById('requestsStatusFilter').value;
    const typeFilter = document.getElementById('requestsTypeFilter').value;
    const rows = document.querySelectorAll('#adminRequestsTableBody tr');
    
    rows.forEach(row => {
        const resident = row.cells[0]?.textContent.toLowerCase() || '';
        const type = row.cells[1]?.textContent || '';
        const statusBadge = row.cells[3]?.querySelector('.status-badge');
        const status = statusBadge ? statusBadge.classList[1] : '';
        
        const matchesSearch = resident.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesType = !typeFilter || type === typeFilter;
        
        if (matchesSearch && matchesStatus && matchesType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Utility functions
function closeAdminModals() {
    const modals = document.querySelectorAll('.admin-modal');
    modals.forEach(modal => {
        modal.classList.remove('active');
        modal.style.display = 'none';
    });
}

function formatStatus(status) {
    const statusMap = {
        'pending': 'Under Review',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'ready_for_pickup': 'Ready for Pickup'
    };
    return statusMap[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
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
    console.log('Showing admin message:', message, type);
    
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.admin-auth-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create message element
    const messageEl = document.createElement('div');
    messageEl.className = `admin-auth-message ${type}`;
    messageEl.textContent = message;
    messageEl.style.display = 'block';
    
    // Find a container to show the message
    const container = document.querySelector('.admin-page:not([style*="display: none"]) .admin-main-content') || 
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

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Demo account function
function fillAdminDemo(email, password) {
    console.log('Filling demo admin credentials:', email, password);
    document.getElementById('adminEmail').value = email;
    document.getElementById('adminPassword').value = password;
}

// Set default date filters for activities
document.addEventListener('DOMContentLoaded', function() {
    // Set default date range to last 30 days
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    const dateFromInput = document.getElementById('activitiesDateFrom');
    const dateToInput = document.getElementById('activitiesDateTo');
    
    if (dateFromInput) {
        dateFromInput.value = thirtyDaysAgo.toISOString().split('T')[0];
    }
    
    if (dateToInput) {
        dateToInput.value = today.toISOString().split('T')[0];
    }
});