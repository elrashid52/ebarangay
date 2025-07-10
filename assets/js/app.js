// E-Barangay Portal JavaScript - Unified Authentication
let currentUser = null;
let currentStep = 1;
let selectedCertificateType = null;
let uploadedDocuments = {};

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    checkSession();
    initializeEventListeners();
    initializeRegistrationForm();
    
    // Log page load activity for residents
    if (window.location.pathname.includes('index.php') || window.location.pathname.endsWith('/')) {
        logUserActivity('page_load', 'system', null, 'Resident portal accessed');
    }
});

// User activity logging function
async function logUserActivity(action, targetType = null, targetId = null, details = '') {
    try {
        // Only log if user is logged in as resident
        if (!currentUser || currentUser.type !== 'resident') {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'log_user_activity');
        formData.append('log_action', action);
        formData.append('user_type', 'resident');
        if (targetType) formData.append('target_type', targetType);
        if (targetId) formData.append('target_id', targetId);
        if (details) formData.append('details', details);
        
        await fetch('api/admin-activities.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Failed to log user activity:', error);
    }
}

// Check if user is logged in and redirect accordingly
async function checkSession() {
    try {
        console.log('Checking session...');
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_session'
        });
        
        const data = await response.json();
        console.log('Session check response:', data);
        
        if (data.success) {
            currentUser = data.user;
            console.log('Valid session found for user:', currentUser);
            
            // CRITICAL: Check user type and redirect accordingly
            if (currentUser.type === 'admin') {
                console.log('Admin user detected, redirecting to admin portal...');
                // If we're on the resident portal, redirect to admin
                if (window.location.pathname.includes('index.php') || window.location.pathname.endsWith('/')) {
                    window.location.href = 'admin.php';
                    return;
                }
                // If we're already on admin portal, show dashboard
                if (typeof showAdminDashboard === 'function') {
                    showAdminDashboard();
                }
            } else {
                console.log('Resident user detected');
                // If we're on the admin portal, redirect to resident
                if (window.location.pathname.includes('admin.php')) {
                    window.location.href = 'index.php';
                    return;
                }
                // If we're already on resident portal, show dashboard
                console.log('Showing resident dashboard...');
                
                // Log session check activity
                logUserActivity('session_check', 'system', null, 'Valid session found');
                showDashboard();
            }
        } else {
            console.log('No valid session found');
            // No session, show appropriate auth form
            if (window.location.pathname.includes('admin.php')) {
                // If on admin portal but no session, redirect to unified login
                console.log('No session on admin portal, redirecting to unified login...');
                window.location.href = 'index.php';
            } else {
                showAuth();
            }
        }
    } catch (error) {
        console.error('Session check failed:', error);
        console.log('Session check error, showing auth form');
        if (window.location.pathname.includes('admin.php')) {
            // If error on admin portal, redirect to unified login
            console.log('Session check error on admin portal, redirecting to unified login...');
            window.location.href = 'index.php';
        } else {
            showAuth();
        }
    }
}

// Initialize event listeners
function initializeEventListeners() {
    // Navigation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.nav-item')) {
            e.preventDefault();
            const page = e.target.getAttribute('data-page');
            if (page) {
                showPage(page);
            }
        }
        
        if (e.target.matches('.modal-close') || e.target.matches('.modal') && e.target === e.currentTarget) {
            closeModal();
        }
    });

    // Auth forms
    const signInForm = document.getElementById('signInForm');
    const signUpForm = document.getElementById('signUpForm');
    
    if (signInForm) {
        signInForm.addEventListener('submit', handleSignIn);
    }
    
    if (signUpForm) {
        signUpForm.addEventListener('submit', handleSignUp);
    }

    // Profile form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileUpdate);
    }

    // Certificate request form - FIXED
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'certificateRequestForm') {
            e.preventDefault();
            handleCertificateRequest(e);
        }
    });

    // Certificate type selection
    const certificateTypeSelect = document.getElementById('certificateType');
    if (certificateTypeSelect) {
        certificateTypeSelect.addEventListener('change', handleCertificateTypeChange);
    }

    // Payment method change
    document.addEventListener('change', function(e) {
        if (e.target.name === 'payment_method') {
            handlePaymentMethodChange(e);
        }
    });

    // Receipt upload
    const receiptUpload = document.getElementById('receiptUpload');
    if (receiptUpload) {
        receiptUpload.addEventListener('change', handleReceiptUpload);
    }

    // Auth switches
    const switchToRegister = document.getElementById('switchToRegister');
    const switchToLogin = document.getElementById('switchToLogin');
    const signOutBtn = document.getElementById('signOutBtn');
    
    if (switchToRegister) {
        switchToRegister.addEventListener('click', function(e) {
            e.preventDefault();
            showSignUpForm();
        });
    }
    
    if (switchToLogin) {
        switchToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            showSignInForm();
        });
    }
    
    if (signOutBtn) {
        signOutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            signOut();
        });
    }

    // Voter status change
    const voterStatusSelect = document.getElementById('voterStatusSelect');
    if (voterStatusSelect) {
        voterStatusSelect.addEventListener('change', function() {
            const voterIdGroup = document.getElementById('voterIdGroup');
            if (this.value === 'Registered') {
                voterIdGroup.style.display = 'block';
                document.getElementById('voterId').required = true;
            } else {
                voterIdGroup.style.display = 'none';
                document.getElementById('voterId').required = false;
                document.getElementById('voterId').value = '';
            }
        });
    }

    // Profile picture upload
    const profilePictureInput = document.getElementById('profilePictureInput');
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', handleProfilePictureUpload);
    }

    // Change password form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', handleChangePassword);
    }

    // Request form
    const requestForm = document.getElementById('requestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', handleRequestSubmit);
    }

    // Reupload form
    const resubmitForm = document.getElementById('resubmitForm');
    if (resubmitForm) {
        resubmitForm.addEventListener('submit', handleResubmitSubmit);
    }

    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterRequests);
    }
}

// Initialize registration form
function initializeRegistrationForm() {
    // Auto-calculate age when birth date changes
    const birthDateInput = document.getElementById('regBirthDate');
    if (birthDateInput) {
        birthDateInput.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            // Show age validation message
            if (age < 18) {
                showMessage('You must be at least 18 years old to register', 'error');
            }
        });
    }
    
    // Validate mobile number format
    const mobileInput = document.getElementById('regMobileNumber');
    if (mobileInput) {
        mobileInput.addEventListener('input', function() {
            const value = this.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length <= 11) {
                this.value = value;
            }
            
            if (value.length === 11 && !value.startsWith('09')) {
                showMessage('Mobile number must start with 09', 'error');
            }
        });
    }
    
    // Validate emergency contact number
    const emergencyContactInput = document.getElementById('regEmergencyContactNumber');
    if (emergencyContactInput) {
        emergencyContactInput.addEventListener('input', function() {
            const value = this.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length <= 11) {
                this.value = value;
            }
        });
    }
    
    // Password confirmation validation
    const passwordInput = document.getElementById('regPassword');
    const confirmPasswordInput = document.getElementById('regConfirmPassword');
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput.value !== this.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
}

// Handle registration
async function handleRegistration(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'register');
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating Account...';
    
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Registration response:', data);
        
        if (data.success) {
            showMessage(data.message, 'success');
            // Clear form and switch to login
            e.target.reset();
            setTimeout(() => {
                showLoginForm();
            }, 2000);
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Registration failed:', error);
        showMessage('Registration failed. Please try again.', 'error');
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Show registration form
function showRegistrationForm() {
    document.getElementById('authContainer').style.display = 'none';
    document.getElementById('registrationContainer').style.display = 'flex';
    document.getElementById('dashboardContainer').style.display = 'none';
}

// Show login form
function showLoginForm() {
    document.getElementById('authContainer').style.display = 'flex';
    document.getElementById('registrationContainer').style.display = 'none';
    document.getElementById('dashboardContainer').style.display = 'none';
}

// Authentication functions
async function handleSignIn(e) {
    e.preventDefault();
    console.log('Sign in form submitted');
    
    const formData = new FormData(e.target);
    formData.append('action', 'login');
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    
    try {
        console.log('Sending login request...');
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Login response:', data);
        
        if (data.success) {
            currentUser = data.user;
            console.log('Login successful for user:', currentUser);
            showMessage('Login successful!', 'success');
            
            // CRITICAL: Handle redirect based on user type
            if (data.redirect === 'admin' || currentUser.type === 'admin') {
                console.log('Admin login detected, redirecting to admin portal...');
                setTimeout(() => {
                    window.location.href = 'admin.php';
                }, 2000); // 2 second delay to show success message
            } else {
                console.log('Resident login detected, showing dashboard...');
                // Set resident session variables for compatibility
                
                // Log successful login
                logUserActivity('login', 'system', null, 'Resident logged in successfully');
                if (currentUser.type === 'resident') {
                    // These are needed for the resident portal to work
                    console.log('Setting up resident session...');
                }
                showDashboard();
            }
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Login failed:', error);
        showMessage('Login failed. Please try again.', 'error');
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function handleSignUp(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Check password confirmation
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    
    if (password !== confirmPassword) {
        showMessage('Passwords do not match', 'error');
        return;
    }
    
    formData.append('action', 'register');
    
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Registration successful! Please sign in.', 'success');
            showSignInForm();
            
            // Log registration activity (will be logged after login)
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Registration failed:', error);
        showMessage('Registration failed. Please try again.', 'error');
    }
}

async function signOut() {
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
            currentUser = null;
            showMessage('Signed out successfully!', 'success');
            
            // Log logout activity before clearing session
            logUserActivity('logout', 'system', null, 'Resident logged out');
            
            // ALWAYS redirect to unified login page (index.php)
            console.log('Signing out, redirecting to unified login page...');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        }
    } catch (error) {
        console.error('Sign out failed:', error);
        // Even if sign out fails, redirect to login page
        window.location.href = 'index.php';
    }
}

// Page navigation
function showAuth() {
    document.getElementById('authContainer').style.display = 'flex';
    document.getElementById('appContainer').style.display = 'none';
    showSignInForm();
}

function showDashboard() {
    document.getElementById('authContainer').style.display = 'none';
    document.getElementById('appContainer').style.display = 'flex';
    showPage('dashboard');
    updateUserInfo();
}

function showSignInForm() {
    document.getElementById('signInForm').style.display = 'block';
    document.getElementById('signUpForm').style.display = 'none';
    document.getElementById('loginSwitch').style.display = 'block';
}

function showSignUpForm() {
    document.getElementById('signInForm').style.display = 'none';
    document.getElementById('signUpForm').style.display = 'block';
    document.getElementById('loginSwitch').style.display = 'none';
}

function showPage(page) {
    // Hide all pages
    const pages = document.querySelectorAll('.page');
    pages.forEach(p => p.style.display = 'none');
    
    // Remove active class from nav items
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => item.classList.remove('active'));
    
    // Show selected page
    const targetPage = document.getElementById(page + 'Page');
    if (targetPage) {
        targetPage.style.display = 'block';
    }
    
    // Add active class to nav item
    const activeNav = document.querySelector(`[data-page="${page}"]`);
    if (activeNav) {
        activeNav.classList.add('active');
    }
    
    // Load page-specific data
    switch(page) {
        case 'dashboard':
            loadDashboardData();
            logUserActivity('view_dashboard', 'page', null, 'Viewed dashboard page');
            break;
        case 'requests':
            loadRequestsData();
            logUserActivity('view_requests', 'page', null, 'Viewed requests page');
            break;
        case 'profile':
            loadProfileData();
            logUserActivity('view_profile', 'page', null, 'Viewed profile page');
            break;
        case 'certificate':
            loadCertificateTypes();
            resetCertificateForm();
            logUserActivity('view_certificate_form', 'page', null, 'Viewed certificate request form');
            break;
    }
}

function updateUserInfo() {
    if (currentUser) {
        console.log('Updating user info for:', currentUser);
        const userNameElements = document.querySelectorAll('.user-name');
        userNameElements.forEach(el => {
            el.textContent = currentUser.name;
        });
    } else {
        console.log('No current user to update info for');
    }
}

// Dashboard functions
async function loadDashboardData() {
    console.log('Loading dashboard data...');
    try {
        console.log('Loading dashboard data...');
        const response = await fetch('api/requests.php?action=get_stats');
        console.log('Stats response status:', response.status);
        const data = await response.json();
        console.log('Stats data received:', data);
        
        console.log('Dashboard stats response:', data);
        
        if (data.success) {
            console.log('Dashboard stats loaded:', data.stats);
            updateDashboardStats(data.stats);
        } else {
            console.error('Failed to load stats:', data.message);
            showMessage('Failed to load dashboard statistics', 'error');
        }
        
        // Load recent requests
        loadRecentRequests();
        
        // Log dashboard data load
        logUserActivity('load_dashboard_data', 'data', null, 'Dashboard statistics loaded');
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
        showMessage('Failed to load dashboard data', 'error');
    }
}

function updateDashboardStats(stats) {
    console.log('Updating dashboard stats:', stats);
    console.log('Updating dashboard stats:', stats);
    document.getElementById('totalRequests').textContent = stats.total || 0;
    document.getElementById('pendingRequests').textContent = stats.pending || 0;
    document.getElementById('approvedRequests').textContent = stats.approved || 0;
    document.getElementById('rejectedRequests').textContent = stats.rejected || 0;
    document.getElementById('blotterReports').textContent = stats.blotter_reports || 0;
}

async function loadRecentRequests() {
    console.log('Loading recent requests...');
    try {
        console.log('Loading recent requests...');
        const response = await fetch('api/requests.php?action=get_all');
        const data = await response.json();
        
        console.log('Recent requests response status:', response.status);
        console.log('Recent requests response:', data);
        console.log('Recent requests data:', data);
        
        if (data.success) {
            console.log('Recent requests loaded:', data.requests.length, 'requests');
            displayRecentRequests(data.requests.slice(0, 5));
        } else {
            console.error('Failed to load recent requests:', data.message);
            showMessage('Failed to load recent requests', 'error');
        }
    } catch (error) {
        console.error('Failed to load recent requests:', error);
        showMessage('Error loading recent requests', 'error');
    }
}

function displayRecentRequests(requests) {
    console.log('Displaying recent requests:', requests);
    const tbody = document.getElementById('recentRequestsBody');
    if (!tbody) return;
    
    console.log('Displaying recent requests:', requests);
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #64748b;">No requests found</td></tr>';
        return;
    }
    
    tbody.innerHTML = requests.map(request => `
        <tr>
            <td>${request.type}</td>
            <td>${request.purpose}</td>
            <td><span class="status-badge ${request.status}">${formatStatus(request.status)}</span></td>
            <td>${formatDate(request.created_at)}</td>
        </tr>
    `).join('');
}

// Requests page functions
async function loadRequestsData() {
    console.log('Loading requests data...');
    try {
        console.log('Loading requests data...');
        const response = await fetch('api/requests.php?action=get_all');
        console.log('Requests response status:', response.status);
        const data = await response.json();
        console.log('Requests data received:', data);
        
        console.log('Requests data response:', data);
        
        if (data.success) {
            console.log('Requests data loaded:', data.requests.length, 'requests');
            displayRequestsTable(data.requests);
            
            // Log requests data load
            logUserActivity('load_requests_data', 'data', null, `Loaded ${data.requests.length} requests`);
        } else {
            console.error('Failed to load requests data:', data.message);
            // Show error message to user
            showMessage('Failed to load requests: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to load requests:', error);
        showMessage('Failed to load requests. Please try again.', 'error');
    }
}

function displayRequestsTable(requests) {
    console.log('Displaying requests table:', requests);
    const tbody = document.getElementById('requestsDataGridBody');
    const emptyState = document.getElementById('requestsEmptyState');
    
    if (!tbody) return;
    
    console.log('Displaying requests table:', requests);
    
    if (requests.length === 0) {
        tbody.innerHTML = '';
        if (emptyState) emptyState.style.display = 'block';
        return;
    }
    
    if (emptyState) emptyState.style.display = 'none';
    
    tbody.innerHTML = requests.map(request => `
        <tr>
            <td class="col-certificate-type">
                <div class="certificate-type-cell">
                    <div class="certificate-name">${request.type}</div>
                    <div class="certificate-badge ${request.type.toLowerCase().includes('barangay id') ? 'delivery' : 'downloadable'}">
                        ${request.type.toLowerCase().includes('barangay id') ? '🚚 Home Delivery' : '📥 Downloadable'}
                    </div>
                </div>
            </td>
            <td class="col-purpose">
                <div class="purpose-cell" title="${request.purpose}">${request.purpose}</div>
            </td>
            <td class="col-status">
                <span class="status-badge ${request.status}">${formatStatus(request.status)}</span>
                ${request.status === 'rejected' && request.can_reupload ? 
                    '<div class="reupload-notice">🔄 Can resubmit</div>' : ''}
            </td>
            <td class="col-fee">
                <div class="fee-cell">₱${parseFloat(request.processing_fee || 0).toFixed(2)}</div>
            </td>
            <td class="col-date">
                <div class="date-cell">${formatDate(request.created_at)}</div>
            </td>
            <td class="col-actions">
                <div class="actions-cell">
                    <button class="action-btn view" onclick="viewRequestDetails(${request.id})" title="View Details">
                        📋
                    </button>
                    ${request.status === 'approved' && request.can_download ? 
                        `<button class="action-btn download" onclick="downloadDocument(${request.id})" title="Download">📥</button>` : ''}
                    ${request.status === 'approved' && request.type.toLowerCase().includes('barangay id') ? 
                        `<button class="action-btn delivery" onclick="viewDeliveryInfo(${request.id})" title="Delivery Info">🚚</button>` : ''}
                    ${request.status === 'rejected' && request.can_reupload ? 
                        `<button class="action-btn reupload" onclick="openResubmitModal(${request.id})" title="Resubmit">🔄</button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// Certificate request functions
async function loadCertificateTypes() {
    console.log('Loading certificate types...');
    try {
        const response = await fetch('api/requests.php?action=get_types');
        console.log('Certificate types response status:', response.status);
        const data = await response.json();
        console.log('Certificate types data:', data);
        
        if (data.success) {
            console.log('Certificate types loaded:', data.types.length, 'types');
            populateCertificateTypes(data.types);
        } else {
            console.error('Failed to load certificate types:', data.message);
            showMessage('Failed to load certificate types', 'error');
        }
    } catch (error) {
        console.error('Failed to load certificate types:', error);
        showMessage('Error loading certificate types', 'error');
    }
}

function populateCertificateTypes(types) {
    console.log('Populating certificate types:', types);
    const select = document.getElementById('certificateType');
    if (!select) return;
    
    select.innerHTML = '<option value="">Select certificate type</option>';
    
    types.forEach(type => {
        const option = document.createElement('option');
        option.value = type.name;
        option.textContent = type.name;
        option.dataset.fee = type.processing_fee;
        option.dataset.days = type.processing_days;
        option.dataset.description = type.description;
        option.dataset.requirements = JSON.stringify(type.required_documents);
        select.appendChild(option);
    });
}

function handleCertificateTypeChange(e) {
    const selectedOption = e.target.selectedOptions[0];
    const nextBtn = document.getElementById('step1NextBtn');
    
    if (!selectedOption || !selectedOption.value) {
        hideCertificateInfo();
        nextBtn.disabled = true;
        return;
    }
    
    selectedCertificateType = {
        name: selectedOption.value,
        fee: parseFloat(selectedOption.dataset.fee || 0),
        days: parseInt(selectedOption.dataset.days || 1),
        description: selectedOption.dataset.description || '',
        requirements: JSON.parse(selectedOption.dataset.requirements || '[]')
    };
    
    showCertificateInfo(selectedCertificateType);
    nextBtn.disabled = false;
}

function showCertificateInfo(certType) {
    const infoCard = document.getElementById('certificateInfo');
    if (!infoCard) return;
    
    const isPickupOnly = certType.name.toLowerCase().includes('barangay id');
    
    document.getElementById('certificateInfoTitle').textContent = `${certType.name} - Information`;
    document.getElementById('certificateFee').textContent = `₱${certType.fee.toFixed(2)}`;
    document.getElementById('certificateProcessingTime').textContent = `${certType.days} business days`;
    document.getElementById('certificateDelivery').textContent = isPickupOnly ? 'Pickup Only' : 'Downloadable PDF';
    document.getElementById('certificatePurpose').textContent = certType.description;
    
    infoCard.style.display = 'block';
}

function hideCertificateInfo() {
    const infoCard = document.getElementById('certificateInfo');
    if (infoCard) {
        infoCard.style.display = 'none';
    }
}

function updateDocumentUploads(certType) {
    const container = document.getElementById('documentUploads');
    if (!container) return;
    
    container.innerHTML = '';
    uploadedDocuments = {};
    
    if (!certType.requirements || certType.requirements.length === 0) {
        container.innerHTML = '<p>No documents required for this certificate type.</p>';
        return;
    }
    
    certType.requirements.forEach((requirement, index) => {
        const documentId = requirement.toLowerCase().replace(/[^a-z0-9]/g, '_');
        const uploadGroup = createDocumentUploadGroup(requirement, documentId);
        container.appendChild(uploadGroup);
    });
}

function createDocumentUploadGroup(requirement, documentId) {
    const group = document.createElement('div');
    group.className = 'document-upload-group';
    group.id = `upload-group-${documentId}`;
    
    group.innerHTML = `
        <label for="document_${documentId}">${requirement} *</label>
        <div class="file-upload-area" onclick="document.getElementById('document_${documentId}').click()">
            <div class="upload-placeholder">
                <div class="upload-icon">📁</div>
                <div class="upload-text">
                    <div class="upload-title">Click to upload ${requirement}</div>
                    <div class="upload-subtitle">or drag and drop</div>
                </div>
            </div>
        </div>
        <input type="file" id="document_${documentId}" name="document_${documentId}" 
               accept=".pdf,.jpg,.jpeg,.png" style="display: none;" 
               onchange="handleFileUpload(this, '${documentId}', '${requirement}')">
        <div class="upload-formats">Accepted formats: PDF, JPG, PNG (Max 5MB)</div>
    `;
    
    return group;
}

function handleFileUpload(input, documentId, requirement) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showMessage('File size must be less than 5MB', 'error');
        input.value = '';
        return;
    }
    
    // Validate file type
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('Only PDF, JPG, and PNG files are allowed', 'error');
        input.value = '';
        return;
    }
    
    // Update UI to show uploaded file
    const uploadGroup = document.getElementById(`upload-group-${documentId}`);
    const uploadArea = uploadGroup.querySelector('.file-upload-area');
    
    uploadArea.innerHTML = `
        <div class="upload-success">
            <div class="success-icon">✅</div>
            <div class="success-text">
                <div class="success-title">${requirement} uploaded</div>
                <div class="success-filename">${file.name}</div>
            </div>
        </div>
    `;
    
    uploadGroup.classList.add('uploaded');
    uploadedDocuments[documentId] = file;
    
    // Check if all required documents are uploaded
    checkAllDocumentsUploaded();
}

function checkAllDocumentsUploaded() {
    if (!selectedCertificateType || !selectedCertificateType.requirements) return;
    
    const requiredCount = selectedCertificateType.requirements.length;
    const uploadedCount = Object.keys(uploadedDocuments).length;
    
    const nextBtn = document.getElementById('step2NextBtn');
    if (nextBtn) {
        nextBtn.disabled = uploadedCount < requiredCount;
    }
}

function handlePaymentMethodChange(e) {
    const method = e.target.value;
    const receiptUploadGroup = document.getElementById('receiptUploadGroup');
    const paymentReferenceGroup = document.getElementById('paymentReferenceGroup');
    const paymentReferenceLabel = paymentReferenceGroup.querySelector('label');
    
    if (method === 'bank_transfer') {
        receiptUploadGroup.style.display = 'block';
        paymentReferenceLabel.textContent = 'Bank Transfer Reference Number *';
    } else {
        receiptUploadGroup.style.display = 'none';
        paymentReferenceLabel.textContent = 'GCash/PayMaya Reference Number *';
    }
}

function handleReceiptUpload(e) {
    const file = e.target.files[0];
    const uploadArea = e.target.closest('.file-upload-area');
    const placeholder = uploadArea.querySelector('.upload-placeholder');
    const success = uploadArea.querySelector('.upload-success');
    
    if (file) {
        placeholder.style.display = 'none';
        success.style.display = 'flex';
        success.querySelector('.success-filename').textContent = file.name;
    } else {
        placeholder.style.display = 'flex';
        success.style.display = 'none';
    }
}

// Step navigation
function nextStep() {
    if (currentStep === 1) {
        if (!selectedCertificateType) {
            showMessage('Please select a certificate type', 'error');
            return;
        }
        updateDocumentUploads(selectedCertificateType);
        showStep(2);
    } else if (currentStep === 2) {
        if (!validateDocumentUploads()) {
            showMessage('Please upload all required documents', 'error');
            return;
        }
        showStep(3);
    }
}

function prevStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    
    // Show target step
    const targetStep = document.getElementById(`step${step}`);
    if (targetStep) {
        targetStep.classList.add('active');
        currentStep = step;
    }
}

function validateDocumentUploads() {
    if (!selectedCertificateType || !selectedCertificateType.requirements) return true;
    
    const requiredCount = selectedCertificateType.requirements.length;
    const uploadedCount = Object.keys(uploadedDocuments).length;
    
    return uploadedCount >= requiredCount;
}

async function handleCertificateRequest(e) {
    e.preventDefault();
    
    console.log('Certificate request form submitted!'); // Debug log
    
    // Validate required fields
    const purpose = document.getElementById('requestPurpose').value.trim();
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    const paymentReference = document.getElementById('paymentReference').value.trim();
    
    if (!selectedCertificateType) {
        showMessage('Please select a certificate type', 'error');
        return;
    }
    
    if (!purpose) {
        showMessage('Please enter the purpose for this request', 'error');
        return;
    }
    
    if (!paymentMethod) {
        showMessage('Please select a payment method', 'error');
        return;
    }
    
    if (!paymentReference) {
        showMessage('Please enter payment reference number', 'error');
        return;
    }
    
    // Validate documents are uploaded
    if (!validateDocumentUploads()) {
        showMessage('Please upload all required documents', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_certificate_request');
    formData.append('certificate_type', selectedCertificateType.name);
    formData.append('purpose', purpose);
    formData.append('additional_notes', document.getElementById('additionalNotes').value || '');
    formData.append('payment_method', paymentMethod.value);
    formData.append('payment_reference', paymentReference);
    
    // Add uploaded documents
    Object.keys(uploadedDocuments).forEach(key => {
        formData.append(`document_${key}`, uploadedDocuments[key]);
    });
    
    // Add payment receipt if bank transfer
    const paymentReceipt = document.getElementById('receiptUpload');
    if (paymentReceipt && paymentReceipt.files[0]) {
        formData.append('payment_receipt', paymentReceipt.files[0]);
    }
    
    // Debug: Log what we're sending
    console.log('Submitting certificate request with documents:', Object.keys(uploadedDocuments));
    for (let pair of formData.entries()) {
        console.log(pair[0], pair[1]);
    }
    
    // Disable submit button to prevent double submission
    const submitBtn = document.getElementById('submitRequestBtn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    try {
        const response = await fetch('api/requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Certificate request submitted successfully!', 'success');
            resetCertificateForm();
            showPage('requests');
            
            // Log certificate request submission
            logUserActivity('submit_certificate_request', 'request', data.request_id, `Submitted ${selectedCertificateType.name} request`);
        } else {
            showMessage(data.message || 'Failed to submit request', 'error');
        }
    } catch (error) {
        console.error('Request submission failed:', error);
        showMessage('Failed to submit request. Please try again.', 'error');
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

function resetCertificateForm() {
    currentStep = 1;
    selectedCertificateType = null;
    uploadedDocuments = {};
    
    // Reset form
    const form = document.getElementById('certificateRequestForm');
    if (form) {
        form.reset();
    }
    
    // Hide certificate info
    hideCertificateInfo();
    
    // Clear document uploads
    const container = document.getElementById('documentUploads');
    if (container) {
        container.innerHTML = '';
    }
    
    // Show first step
    showStep(1);
    
    // Reset buttons
    const step1NextBtn = document.getElementById('step1NextBtn');
    const step2NextBtn = document.getElementById('step2NextBtn');
    if (step1NextBtn) step1NextBtn.disabled = true;
    if (step2NextBtn) step2NextBtn.disabled = true;
}

// Profile functions
async function loadProfileData() {
    console.log('Loading profile data...');
    try {
        const response = await fetch('api/profile.php?action=get_profile');
        console.log('Profile response status:', response.status);
        const data = await response.json();
        console.log('Profile data received:', data);
        
        if (data.success) {
            console.log('Profile data loaded');
            populateProfileForm(data.profile);
            updateProfileHeader(data.profile);
            
            // Log profile data load
            logUserActivity('load_profile_data', 'profile', null, 'Profile information loaded');
        } else {
            console.error('Failed to load profile:', data.message);
            showMessage('Failed to load profile: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to load profile:', error);
        showMessage('Failed to load profile. Please try again.', 'error');
    }
}

function populateProfileForm(profile) {
    console.log('Populating profile form:', profile);
    const form = document.getElementById('profileForm');
    if (!form) return;
    
    // Populate all form fields
    Object.keys(profile).forEach(key => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field && profile[key] !== null) {
            field.value = profile[key];
        }
    });
    
    // Handle voter status display
    const voterStatusSelect = document.getElementById('voterStatusSelect');
    const voterIdGroup = document.getElementById('voterIdGroup');
    if (voterStatusSelect && voterIdGroup) {
        if (voterStatusSelect.value === 'Registered') {
            voterIdGroup.style.display = 'block';
        } else {
            voterIdGroup.style.display = 'none';
        }
    }
}

function updateProfileHeader(profile) {
    const profileName = document.getElementById('profileName');
    const profileEmail = document.getElementById('profileEmail');
    const voterStatus = document.getElementById('voterStatus');
    
    if (profileName) {
        profileName.textContent = `${profile.first_name || ''} ${profile.last_name || ''}`.trim() || 'Loading...';
    }
    
    if (profileEmail) {
        profileEmail.textContent = profile.email || 'Loading...';
    }
    
    if (voterStatus) {
        voterStatus.textContent = profile.voter_status || 'Not Registered';
        voterStatus.className = `status-badge ${profile.voter_status === 'Registered' ? 'approved' : 'pending'}`;
    }
    
    // Update profile picture if exists
    if (profile.profile_picture) {
        const img = document.getElementById('profilePicture');
        if (img) {
            img.src = profile.profile_picture;
        }
    }
}

async function handleProfileUpdate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'update_profile');
    
    try {
        const response = await fetch('api/profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Profile updated successfully!', 'success');
            loadProfileData(); // Reload to show updated data
            
            // Log profile update
            logUserActivity('update_profile', 'profile', null, 'Profile information updated');
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Profile update failed:', error);
        showMessage('Failed to update profile', 'error');
    }
}

async function handleProfilePictureUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('action', 'upload_profile_picture');
    formData.append('profile_picture', file);
    
    try {
        const response = await fetch('api/profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Profile picture updated successfully!', 'success');
            document.getElementById('profilePicture').src = data.path;
            
            // Log profile picture update
            logUserActivity('update_profile_picture', 'profile', null, 'Profile picture updated');
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Profile picture upload failed:', error);
        showMessage('Failed to upload profile picture', 'error');
    }
}

// Modal functions
function openChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.add('modal');
    document.getElementById('changePasswordModal').style.display = 'flex';
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
    document.getElementById('changePasswordForm').reset();
}

async function handleChangePassword(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'change_password');
    
    try {
        const response = await fetch('api/profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Password changed successfully!', 'success');
            closeChangePasswordModal();
            
            // Log password change
            logUserActivity('change_password', 'security', null, 'Password changed successfully');
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Password change failed:', error);
        showMessage('Failed to change password', 'error');
    }
}

function openCertificateRequestModal() {
    showPage('certificate');
}

// Resubmit modal functions
function openResubmitModal(requestId) {
    document.getElementById('resubmitRequestId').value = requestId;
    document.getElementById('resubmitModal').style.display = 'flex';
    
    // Reset form
    document.getElementById('resubmitForm').reset();
    resetResubmitUploads();
}

function closeResubmitModal() {
    document.getElementById('resubmitModal').style.display = 'none';
    document.getElementById('resubmitForm').reset();
    resetResubmitUploads();
}

function resetResubmitUploads() {
    const uploadAreas = document.querySelectorAll('#resubmitDocuments .file-upload-area');
    uploadAreas.forEach(area => {
        const placeholder = area.querySelector('.upload-placeholder');
        const success = area.querySelector('.upload-success');
        
        if (placeholder) placeholder.style.display = 'flex';
        if (success) success.style.display = 'none';
        
        area.parentElement.classList.remove('uploaded');
    });
}

function handleResubmitFileUpload(input, documentType) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showMessage('File size must be less than 5MB', 'error');
        input.value = '';
        return;
    }
    
    // Validate file type
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('Only PDF, JPG, and PNG files are allowed', 'error');
        input.value = '';
        return;
    }
    
    // Update UI to show uploaded file
    const uploadGroup = input.closest('.document-upload-group');
    const uploadArea = uploadGroup.querySelector('.file-upload-area');
    
    uploadArea.innerHTML = `
        <div class="upload-success">
            <div class="success-icon">✅</div>
            <div class="success-text">
                <div class="success-title">Document uploaded</div>
                <div class="success-filename">${file.name}</div>
            </div>
        </div>
    `;
    
    uploadGroup.classList.add('uploaded');
}

async function handleResubmitSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'reupload_request');
    
    // Validate required fields
    const purpose = formData.get('purpose');
    if (!purpose.trim()) {
        showMessage('Purpose is required', 'error');
        return;
    }
    
    // Check if at least one document is uploaded
    const validId = formData.get('valid_id');
    const proofBilling = formData.get('proof_billing');
    
    if (!validId || !validId.size || !proofBilling || !proofBilling.size) {
        showMessage('Please upload all required documents', 'error');
        return;
    }
    
    // Disable submit button
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Resubmitting...';
    
    try {
        const response = await fetch('api/requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Request resubmitted successfully!', 'success');
            closeResubmitModal();
            loadRequestsData(); // Refresh the requests table
            
            // Log request resubmission
            const requestId = formData.get('request_id');
            logUserActivity('resubmit_request', 'request', requestId, 'Request resubmitted with new documents');
        } else {
            showMessage(data.message || 'Failed to resubmit request', 'error');
        }
    } catch (error) {
        console.error('Resubmit failed:', error);
        showMessage('Failed to resubmit request. Please try again.', 'error');
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

function openReuploadModal(requestId) {
    openResubmitModal(requestId);
}

function closeReuploadModal() {
    closeResubmitModal();
}

async function handleReuploadSubmit(e) {
    return handleResubmitSubmit(e);
}

// Request details modal
async function viewRequestDetails(requestId) {
    try {
        const response = await fetch(`api/requests.php?action=get_all`);
        const data = await response.json();
        
        if (data.success) {
            const request = data.requests.find(r => r.id == requestId);
            if (request) {
                showRequestDetailsModal(request);
            }
            
            // Log request details view
            logUserActivity('view_request_details', 'request', requestId, 'Viewed request details');
        }
    } catch (error) {
        console.error('Failed to load request details:', error);
    }
}

function showRequestDetailsModal(request) {
    const modal = document.getElementById('requestDetailsModal');
    
    // Update modal content
    document.getElementById('requestDetailsTitle').textContent = `📄 Request Details - ${request.type}`;
    document.getElementById('detailCertificateType').textContent = request.type;
    document.getElementById('detailStatus').textContent = formatStatus(request.status);
    document.getElementById('detailStatus').className = `status-badge ${request.status}`;
    document.getElementById('detailFee').textContent = `₱${parseFloat(request.processing_fee || 0).toFixed(2)}`;
    document.getElementById('detailDeliveryMethod').textContent = request.type.toLowerCase().includes('barangay id') ? '🚚 Home Delivery (3-5 business days)' : '📥 Downloadable PDF';
    document.getElementById('detailRequestDate').textContent = formatDate(request.created_at);
    document.getElementById('detailPurpose').textContent = request.purpose;
    
    // Handle approved date
    const approvedDateItem = document.getElementById('detailApprovedDateItem');
    if (request.processed_at && request.status === 'approved') {
        document.getElementById('detailApprovedDate').textContent = formatDate(request.processed_at);
        approvedDateItem.style.display = 'block';
    } else {
        approvedDateItem.style.display = 'none';
    }
    
    // Handle payment information
    const paymentSection = document.getElementById('detailPaymentSection');
    if (request.request_details && request.request_details.payment_method) {
        document.getElementById('detailPaymentRef').textContent = `Ref: ${request.request_details.payment_reference || 'N/A'}`;
        paymentSection.style.display = 'block';
    } else {
        paymentSection.style.display = 'none';
    }
    
    // Handle rejection section
    const rejectionSection = document.getElementById('detailRejectionSection');
    if (request.status === 'rejected') {
        document.getElementById('detailRejectionMessage').textContent = request.admin_notes || 'No specific reason provided.';
        
        // Add resubmit button if allowed
        const existingResubmitBtn = rejectionSection.querySelector('.resubmit-btn');
        if (existingResubmitBtn) {
            existingResubmitBtn.remove();
        }
        
        if (request.can_reupload) {
            const resubmitBtn = document.createElement('button');
            resubmitBtn.className = 'resubmit-btn';
            resubmitBtn.innerHTML = '🔄 Resubmit Request';
            resubmitBtn.onclick = () => {
                closeRequestDetailsModal();
                openResubmitModal(request.id);
            };
            rejectionSection.appendChild(resubmitBtn);
        }
        
        rejectionSection.style.display = 'block';
    } else {
        rejectionSection.style.display = 'none';
    }
    
    // Handle Barangay ID section
    const barangayIdSection = document.getElementById('detailBarangayIdSection');
    if (request.type.toLowerCase().includes('barangay id') && request.status === 'approved') {
        // Update the delivery information
        const deliveryInfo = document.getElementById('barangayIdDeliveryInfo');
        if (deliveryInfo) {
            deliveryInfo.innerHTML = `
                <div class="delivery-status">
                    <div class="delivery-icon">🚚</div>
                    <div class="delivery-details">
                        <h4>Your Barangay ID is being prepared for delivery</h4>
                        <p><strong>Estimated Delivery:</strong> 3-5 business days from approval</p>
                        <p><strong>Delivery Address:</strong> Your registered address</p>
                        <p><strong>Delivery Fee:</strong> Free of charge</p>
                        <p><strong>Contact:</strong> Please ensure someone is available to receive the ID</p>
                    </div>
                </div>
                <div class="delivery-note">
                    <p><strong>Note:</strong> Your Barangay ID will be delivered to your registered address within 3-5 business days. Please ensure that the delivery address in your profile is correct and up-to-date.</p>
                </div>
            `;
        }
        barangayIdSection.style.display = 'block';
    } else {
        barangayIdSection.style.display = 'none';
    }
    
    // Handle download button
    const downloadBtn = document.getElementById('detailDownloadBtn');
    if (request.status === 'approved' && request.can_download && !request.type.toLowerCase().includes('barangay id')) {
        downloadBtn.style.display = 'inline-block';
        downloadBtn.onclick = () => downloadDocument(request.id);
    } else {
        downloadBtn.style.display = 'none';
    }
    
    modal.style.display = 'flex';
}

// New function to show delivery information for Barangay ID
function viewDeliveryInfo(requestId) {
    // Find the request data
    fetch(`api/requests.php?action=get_all`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const request = data.requests.find(r => r.id == requestId);
                if (request && request.type.toLowerCase().includes('barangay id')) {
                    showDeliveryInfoModal(request);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching request data:', error);
            showMessage('Failed to load delivery information', 'error');
        });
}

function showDeliveryInfoModal(request) {
    // Create and show delivery info modal
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    
    modal.innerHTML = `
        <div class="modal-content delivery-info-modal">
            <div class="modal-header">
                <h2>🚚 Barangay ID Delivery Information</h2>
                <button class="modal-close" onclick="this.closest('.modal').remove()">✕</button>
            </div>
            <div class="delivery-info-content">
                <div class="delivery-status-card">
                    <div class="delivery-icon-large">🆔</div>
                    <h3>Your Barangay ID is Ready for Delivery!</h3>
                    <p class="delivery-status-text">Status: <span class="status-badge approved">Approved & Processing</span></p>
                </div>
                
                <div class="delivery-details-grid">
                    <div class="delivery-detail-item">
                        <div class="detail-icon">📅</div>
                        <div class="detail-content">
                            <strong>Estimated Delivery</strong>
                            <p>3-5 business days from approval date</p>
                        </div>
                    </div>
                    
                    <div class="delivery-detail-item">
                        <div class="detail-icon">📍</div>
                        <div class="detail-content">
                            <strong>Delivery Address</strong>
                            <p>Your registered home address</p>
                        </div>
                    </div>
                    
                    <div class="delivery-detail-item">
                        <div class="detail-icon">💰</div>
                        <div class="detail-content">
                            <strong>Delivery Fee</strong>
                            <p>Free of charge</p>
                        </div>
                    </div>
                    
                    <div class="delivery-detail-item">
                        <div class="detail-icon">📞</div>
                        <div class="detail-content">
                            <strong>Contact Required</strong>
                            <p>Someone must be available to receive</p>
                        </div>
                    </div>
                </div>
                
                <div class="delivery-timeline">
                    <h4>📋 Delivery Process</h4>
                    <div class="timeline-steps">
                        <div class="timeline-step completed">
                            <div class="step-icon">✅</div>
                            <div class="step-content">
                                <strong>Application Approved</strong>
                                <p>Your Barangay ID request has been approved</p>
                            </div>
                        </div>
                        <div class="timeline-step active">
                            <div class="step-icon">🏭</div>
                            <div class="step-content">
                                <strong>ID Production</strong>
                                <p>Your ID is being printed and prepared</p>
                            </div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-icon">🚚</div>
                            <div class="step-content">
                                <strong>Out for Delivery</strong>
                                <p>ID will be delivered to your address</p>
                            </div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-icon">📬</div>
                            <div class="step-content">
                                <strong>Delivered</strong>
                                <p>ID successfully delivered and received</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="delivery-important-notes">
                    <h4>📌 Important Notes</h4>
                    <ul>
                        <li><strong>Valid ID Required:</strong> Please have a valid government-issued ID ready for verification upon delivery</li>
                        <li><strong>Personal Receipt:</strong> Only you or an authorized representative can receive the ID</li>
                        <li><strong>Address Verification:</strong> Ensure your registered address is correct and accessible</li>
                        <li><strong>Contact Information:</strong> Keep your mobile number active for delivery coordination</li>
                        <li><strong>Delivery Hours:</strong> Monday to Friday, 8:00 AM to 5:00 PM</li>
                    </ul>
                </div>
                
                <div class="delivery-contact-info">
                    <h4>📞 Need Help?</h4>
                    <p>For delivery inquiries, contact the Barangay Office:</p>
                    <div class="contact-details">
                        <p><strong>Phone:</strong> (02) 123-4567</p>
                        <p><strong>Mobile:</strong> 0917-123-4567</p>
                        <p><strong>Email:</strong> delivery@barangay.gov.ph</p>
                        <p><strong>Office Hours:</strong> Monday to Friday, 8:00 AM to 5:00 PM</p>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-primary" onclick="this.closest('.modal').remove()">
                    Got it, Thanks!
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeRequestDetailsModal() {
    document.getElementById('requestDetailsModal').style.display = 'none';
}

// Document actions
async function downloadDocument(requestId) {
    try {
        // Log download attempt
        logUserActivity('download_document', 'request', requestId, 'Downloaded certificate document');
        
        const response = await fetch(`api/requests.php?action=download_document&request_id=${requestId}`);
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `certificate_${requestId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            const data = await response.json();
            showMessage(data.message || 'Download failed', 'error');
        }
    } catch (error) {
        console.error('Download failed:', error);
        showMessage('Download failed. Please try again.', 'error');
    }
}

async function viewDocument(requestId) {
    try {
        // Log document view
        logUserActivity('view_document', 'request', requestId, 'Viewed document in browser');
        
        window.open(`api/requests.php?action=view_document&request_id=${requestId}`, '_blank');
    } catch (error) {
        console.error('View failed:', error);
        showMessage('Failed to view document', 'error');
    }
}

// Legacy request functions
async function loadRequestTypes() {
    try {
        const response = await fetch('api/requests.php?action=get_types');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('requestType');
            if (select) {
                select.innerHTML = '<option value="">Select request type</option>';
                data.types.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.name;
                    option.textContent = type.name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Failed to load request types:', error);
    }
}

function openModal() {
    loadRequestTypes();
    document.getElementById('requestModal').style.display = 'flex';
}

function closeModal() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
    
    // Reset forms
    const forms = document.querySelectorAll('.modal form');
    forms.forEach(form => form.reset());
}

async function handleRequestSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'create');
    
    try {
        const response = await fetch('api/requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Request submitted successfully!', 'success');
            closeModal();
            loadRequestsData();
            loadDashboardData();
            
            // Log legacy request submission
            logUserActivity('submit_legacy_request', 'request', data.request_id, `Submitted ${formData.get('type')} request`);
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Request submission failed:', error);
        showMessage('Failed to submit request', 'error');
    }
}

// Filter functions
function filterRequests() {
    const filter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#requestsDataGridBody tr');
    
    // Log filter usage
    logUserActivity('filter_requests', 'ui', null, `Filtered requests by status: ${filter || 'all'}`);
    rows.forEach(row => {
        if (!filter) {
            row.style.display = '';
        } else {
            const statusBadge = row.querySelector('.status-badge');
            if (statusBadge && statusBadge.classList.contains(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

// Utility functions
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

function showMessage(message, type) {
    // Remove existing messages
    console.log('Showing message:', message, 'Type:', type);
    const existingMessages = document.querySelectorAll('.auth-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create message element
    const messageEl = document.createElement('div');
    messageEl.className = `auth-message ${type}`;
    messageEl.textContent = message;
    messageEl.style.display = 'block';
    
    // Find a container to show the message
    const container = document.querySelector('.page:not([style*="display: none"]) .container') || 
                     document.querySelector('.auth-card') || 
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

// Add debug function to check API connectivity
async function debugAPIConnectivity() {
    console.log('=== API Connectivity Debug ===');
    
    // Test session check
    try {
        const sessionResponse = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=check_session'
        });
        console.log('Session check status:', sessionResponse.status);
        const sessionData = await sessionResponse.json();
        console.log('Session data:', sessionData);
    } catch (error) {
        console.error('Session check failed:', error);
    }
    
    // Test stats API
    try {
        const statsResponse = await fetch('api/requests.php?action=get_stats');
        console.log('Stats API status:', statsResponse.status);
        const statsData = await statsResponse.json();
        console.log('Stats data:', statsData);
    } catch (error) {
        console.error('Stats API failed:', error);
    }
    
    // Test requests API
    try {
        const requestsResponse = await fetch('api/requests.php?action=get_all');
        console.log('Requests API status:', requestsResponse.status);
        const requestsData = await requestsResponse.json();
        console.log('Requests data:', requestsData);
    } catch (error) {
        console.error('Requests API failed:', error);
    }
    
    console.log('=== End Debug ===');
}

// Run debug on page load if needed
if (window.location.search.includes('debug=1')) {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(debugAPIConnectivity, 1000);
    });
}

// Demo account function for residents
async function fillDemoAccount(email, password) {
    const emailField = document.getElementById('email') || document.getElementById('loginEmail');
    const passwordField = document.getElementById('password') || document.getElementById('loginPassword');
    
    if (emailField) emailField.value = email;
    if (passwordField) passwordField.value = password;
    
    // Automatically submit the login form
    const loginForm = document.getElementById('signInForm');
    if (loginForm) {
        // Trigger the login process
        await handleSignIn({ 
    
    // Registration form
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', handleRegistration);
    }
            preventDefault: () => {},
            target: loginForm
        });
    }
}