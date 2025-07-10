// E-Barangay Staff Portal JavaScript
let currentStaffUser = null;

// Initialize staff app
document.addEventListener('DOMContentLoaded', function() {
    checkStaffSession();
    initializeStaffEventListeners();
});

// Check if staff is logged in
async function checkStaffSession() {
    try {
        const response = await fetch('api/staff-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_session'
        });
        
        const data = await response.json();
        console.log('Staff session check response:', data);
        
        if (data.success) {
            currentStaffUser = data.staff;
            showStaffDashboard();
        } else {
            showStaffAuth();
        }
    } catch (error) {
        console.error('Staff session check failed:', error);
        showStaffAuth();
    }
}

// Initialize event listeners
function initializeStaffEventListeners() {
    // Navigation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.staff-nav-item')) {
            e.preventDefault();
            const page = e.target.getAttribute('data-staff-page');
            if (page) {
                showStaffPage(page);
            }
        }
        
        if (e.target.matches('.staff-modal-close') || (e.target.matches('.staff-modal') && e.target === e.currentTarget)) {
            closeStaffModal();
        }
    });

    // Auth form
    const staffLoginForm = document.getElementById('staffLoginForm');
    if (staffLoginForm) {
        staffLoginForm.addEventListener('submit', handleStaffLogin);
    }

    // Create announcement form
    const createAnnouncementForm = document.getElementById('createAnnouncementForm');
    if (createAnnouncementForm) {
        createAnnouncementForm.addEventListener('submit', handleCreateAnnouncement);
    }
}

// Authentication functions
async function handleStaffLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'login');
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    
    try {
        const response = await fetch('api/staff-auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Staff login response:', data);
        
        if (data.success) {
            currentStaffUser = data.staff;
            showStaffMessage('Login successful!', 'success');
            showStaffDashboard();
        } else {
            showStaffMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Staff login failed:', error);
        showStaffMessage('Login failed. Please try again.', 'error');
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function staffSignOut() {
    try {
        const response = await fetch('api/staff-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=logout'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentStaffUser = null;
            showStaffMessage('Signed out successfully!', 'success');
            
            // Redirect to unified login page
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        }
    } catch (error) {
        console.error('Staff sign out failed:', error);
        // Even if sign out fails, redirect to login page
        window.location.href = 'index.php';
    }
}

// Page navigation
function showStaffAuth() {
    document.getElementById('staffAuthContainer').style.display = 'flex';
    document.getElementById('staffDashboardContainer').style.display = 'none';
}

function showStaffDashboard() {
    document.getElementById('staffAuthContainer').style.display = 'none';
    document.getElementById('staffDashboardContainer').style.display = 'flex';
    showStaffPage('dashboard');
    updateStaffUserInfo();
}

function showStaffPage(page) {
    // Hide all pages
    const pages = document.querySelectorAll('.staff-page');
    pages.forEach(p => p.style.display = 'none');
    
    // Remove active class from nav items
    const navItems = document.querySelectorAll('.staff-nav-item');
    navItems.forEach(item => item.classList.remove('active'));
    
    // Show selected page
    const targetPage = document.getElementById('staff' + page.charAt(0).toUpperCase() + page.slice(1) + 'Page');
    if (targetPage) {
        targetPage.style.display = 'block';
    }
    
    // Add active class to nav item
    const activeNav = document.querySelector(`[data-staff-page="${page}"]`);
    if (activeNav) {
        activeNav.classList.add('active');
    }
    
    // Load page-specific data
    switch(page) {
        case 'dashboard':
            loadStaffDashboardData();
            break;
        case 'requests':
            loadStaffRequestsData();
            break;
        case 'residents':
            loadStaffResidentsData();
            break;
        case 'announcements':
            loadStaffAnnouncementsData();
            break;
        case 'reports':
            loadStaffReportsData();
            break;
    }
}

function updateStaffUserInfo() {
    if (currentStaffUser) {
        const userNameElements = document.querySelectorAll('.staff-user-name');
        userNameElements.forEach(el => {
            el.textContent = currentStaffUser.name;
        });
    }
}

// Dashboard functions
async function loadStaffDashboardData() {
    try {
        const response = await fetch('api/staff-dashboard.php?action=get_stats');
        const data = await response.json();
        
        if (data.success) {
            updateStaffDashboardStats(data.stats);
        }
        
        // Load recent requests
        loadStaffRecentRequests();
    } catch (error) {
        console.error('Failed to load staff dashboard data:', error);
    }
}

function updateStaffDashboardStats(stats) {
    document.getElementById('staffPendingRequests').textContent = stats.pending_requests || 0;
    document.getElementById('staffProcessedToday').textContent = stats.processed_today || 0;
    document.getElementById('staffTotalResidents').textContent = stats.total_residents || 0;
    document.getElementById('staffActiveAnnouncements').textContent = stats.active_announcements || 0;
}

async function loadStaffRecentRequests() {
    try {
        const response = await fetch('api/staff-requests.php?action=get_recent');
        const data = await response.json();
        
        if (data.success) {
            displayStaffRecentRequests(data.requests);
        }
    } catch (error) {
        console.error('Failed to load recent requests:', error);
    }
}

function displayStaffRecentRequests(requests) {
    const tbody = document.getElementById('staffRecentRequestsBody');
    if (!tbody) return;
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #64748b;">No recent requests</td></tr>';
        return;
    }
    
    tbody.innerHTML = requests.map(request => `
        <tr>
            <td>${request.resident_name}</td>
            <td>${request.type}</td>
            <td><span class="status-badge ${request.status}">${formatStaffStatus(request.status)}</span></td>
            <td>${formatStaffDate(request.created_at)}</td>
            <td>
                <button class="staff-action-btn view" onclick="viewStaffRequest(${request.id})" title="View">👁️</button>
                ${request.status === 'pending' ? 
                    `<button class="staff-action-btn approve" onclick="quickApproveStaffRequest(${request.id})" title="Approve">✅</button>
                     <button class="staff-action-btn reject" onclick="quickRejectStaffRequest(${request.id})" title="Reject">❌</button>` : ''}
            </td>
        </tr>
    `).join('');
}

// Requests management
async function loadStaffRequestsData() {
    try {
        const response = await fetch('api/staff-requests.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayStaffRequestsTable(data.requests);
        }
    } catch (error) {
        console.error('Failed to load staff requests:', error);
    }
}

function displayStaffRequestsTable(requests) {
    const tbody = document.getElementById('staffRequestsTableBody');
    if (!tbody) return;
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No requests found</td></tr>';
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
            <td><span class="status-badge ${request.status}">${formatStaffStatus(request.status)}</span></td>
            <td>${formatStaffDate(request.created_at)}</td>
            <td>
                <button class="staff-action-btn view" onclick="viewStaffRequest(${request.id})" title="View">👁️</button>
                ${request.status === 'pending' ? 
                    `<button class="staff-action-btn approve" onclick="quickApproveStaffRequest(${request.id})" title="Approve">✅</button>
                     <button class="staff-action-btn reject" onclick="quickRejectStaffRequest(${request.id})" title="Reject">❌</button>` : ''}
            </td>
        </tr>
    `).join('');
}

// Residents management (read-only)
async function loadStaffResidentsData() {
    try {
        const response = await fetch('api/staff-residents.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayStaffResidentsTable(data.residents);
        }
    } catch (error) {
        console.error('Failed to load staff residents:', error);
    }
}

function displayStaffResidentsTable(residents) {
    const tbody = document.getElementById('staffResidentsTableBody');
    if (!tbody) return;
    
    if (residents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #64748b;">No residents found</td></tr>';
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
            <td>${formatStaffDate(resident.created_at)}</td>
        </tr>
    `).join('');
}

// Announcements management
async function loadStaffAnnouncementsData() {
    try {
        const response = await fetch('api/staff-announcements.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayStaffAnnouncementsGrid(data.announcements);
        }
    } catch (error) {
        console.error('Failed to load staff announcements:', error);
    }
}

function displayStaffAnnouncementsGrid(announcements) {
    const grid = document.getElementById('staffAnnouncementsGrid');
    if (!grid) return;
    
    if (announcements.length === 0) {
        grid.innerHTML = `
            <div class="staff-announcement-card">
                <div class="announcement-header">
                    <h3>No announcements yet</h3>
                </div>
                <div class="announcement-content">
                    Create your first announcement to get started.
                </div>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = announcements.map(announcement => `
        <div class="staff-announcement-card">
            <div class="announcement-header">
                <h3>${announcement.title}</h3>
                <span class="announcement-type ${announcement.type}">${announcement.type}</span>
            </div>
            <div class="announcement-content">
                ${announcement.content.substring(0, 150)}${announcement.content.length > 150 ? '...' : ''}
            </div>
            <div class="announcement-footer">
                <span>Posted: ${formatStaffDate(announcement.created_at)}</span>
                <div>
                    <button class="staff-action-btn view" onclick="editAnnouncement(${announcement.id})" title="Edit">✏️</button>
                    <button class="staff-action-btn reject" onclick="deleteAnnouncement(${announcement.id})" title="Delete">🗑️</button>
                </div>
            </div>
        </div>
    `).join('');
}

// Request actions
async function viewStaffRequest(requestId) {
    try {
        const response = await fetch(`api/staff-requests.php?action=get_details&id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
            showStaffRequestReviewModal(data.request);
        } else {
            showStaffMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to load request details:', error);
        showStaffMessage('Failed to load request details', 'error');
    }
}

function showStaffRequestReviewModal(request) {
    const modal = document.getElementById('staffRequestReviewModal');
    const body = document.getElementById('staffRequestReviewBody');
    
    document.getElementById('staffRequestReviewTitle').textContent = `📄 Review Request - ${request.type}`;
    
    body.innerHTML = `
        <div class="request-details">
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Resident</label>
                    <span>${request.resident_name}</span>
                </div>
                <div class="detail-item">
                    <label>Email</label>
                    <span>${request.resident_email}</span>
                </div>
                <div class="detail-item">
                    <label>Certificate Type</label>
                    <span>${request.type}</span>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <span class="status-badge ${request.status}">${formatStaffStatus(request.status)}</span>
                </div>
                <div class="detail-item">
                    <label>Processing Fee</label>
                    <span>₱${parseFloat(request.processing_fee || 0).toFixed(2)}</span>
                </div>
                <div class="detail-item">
                    <label>Date Submitted</label>
                    <span>${formatStaffDate(request.created_at)}</span>
                </div>
            </div>
            
            <div class="purpose-section">
                <label>Purpose</label>
                <div class="purpose-content">${request.purpose}</div>
            </div>
            
            ${request.status === 'pending' ? `
                <div class="action-section">
                    <button class="staff-btn staff-btn-primary" onclick="approveStaffRequest(${request.id})">
                        ✅ Approve Request
                    </button>
                    <button class="staff-btn staff-btn-secondary" onclick="rejectStaffRequest(${request.id})">
                        ❌ Reject Request
                    </button>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.classList.add('active');
    modal.style.display = 'flex';
}

async function quickApproveStaffRequest(requestId) {
    if (!confirm('Are you sure you want to approve this request?')) {
        return;
    }
    
    try {
        const response = await fetch('api/staff-requests.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&id=${requestId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showStaffMessage(data.message, 'success');
            loadStaffRequestsData();
            loadStaffDashboardData();
        } else {
            showStaffMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to approve request:', error);
        showStaffMessage('Failed to approve request', 'error');
    }
}

async function quickRejectStaffRequest(requestId) {
    const reason = prompt('Please enter the reason for rejection:');
    if (!reason || reason.trim() === '') {
        showStaffMessage('Rejection reason is required', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/staff-requests.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&id=${requestId}&reason=${encodeURIComponent(reason.trim())}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showStaffMessage(data.message, 'success');
            loadStaffRequestsData();
            loadStaffDashboardData();
        } else {
            showStaffMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to reject request:', error);
        showStaffMessage('Failed to reject request', 'error');
    }
}

// Announcement functions
function openCreateAnnouncementModal() {
    document.getElementById('createAnnouncementModal').classList.add('active');
    document.getElementById('createAnnouncementModal').style.display = 'flex';
}

function closeCreateAnnouncementModal() {
    document.getElementById('createAnnouncementModal').classList.remove('active');
    document.getElementById('createAnnouncementModal').style.display = 'none';
    document.getElementById('createAnnouncementForm').reset();
}

async function handleCreateAnnouncement(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'create');
    
    try {
        const response = await fetch('api/staff-announcements.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showStaffMessage('Announcement created successfully!', 'success');
            closeCreateAnnouncementModal();
            loadStaffAnnouncementsData();
            loadStaffDashboardData();
        } else {
            showStaffMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to create announcement:', error);
        showStaffMessage('Failed to create announcement', 'error');
    }
}

// Report functions
function loadStaffReportsData() {
    // Reports page is loaded - no additional data needed for now
    console.log('Reports page loaded');
}

function generateRequestsReport() {
    showStaffMessage('Requests report generation coming soon!', 'info');
}

function generateResidentsReport() {
    showStaffMessage('Residents report generation coming soon!', 'info');
}

function generateMonthlyReport() {
    showStaffMessage('Monthly report generation coming soon!', 'info');
}

// Modal functions
function closeStaffModal() {
    const modals = document.querySelectorAll('.staff-modal');
    modals.forEach(modal => {
        modal.classList.remove('active');
        modal.style.display = 'none';
    });
}

function closeStaffRequestReviewModal() {
    const modal = document.getElementById('staffRequestReviewModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
}

// Utility functions
function formatStaffStatus(status) {
    const statusMap = {
        'pending': 'Under Review',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'ready_for_pickup': 'Ready for Pickup'
    };
    return statusMap[status] || status;
}

function formatStaffDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showStaffMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.staff-auth-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create message element
    const messageEl = document.createElement('div');
    messageEl.className = `staff-auth-message ${type}`;
    messageEl.textContent = message;
    messageEl.style.display = 'block';
    
    // Find a container to show the message
    const container = document.querySelector('.staff-page:not([style*="display: none"]) .staff-page-header') || 
                     document.querySelector('.staff-auth-card') || 
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

// Demo account function for staff
function fillStaffDemoAccount() {
    const emailField = document.getElementById('staffEmail');
    const passwordField = document.getElementById('staffPassword');
    
    if (emailField) emailField.value = 'staff@barangay.gov.ph';
    if (passwordField) passwordField.value = 'password';
}