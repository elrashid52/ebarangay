// E-Barangay Portal JavaScript - Unified Authentication
let currentUser = null;
let currentStep = 1;
let selectedCertificateType = null;
let uploadedDocuments = {};

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    checkSession();
    initializeEventListeners();
});

// Check if user is logged in and redirect accordingly
async function checkSession() {
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
        
        if (data.success) {
            currentUser = data.user;
            
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
                showDashboard();
            }
        } else {
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
    const reuploadForm = document.getElementById('reuploadForm');
    if (reuploadForm) {
        reuploadForm.addEventListener('submit', handleReuploadSubmit);
    }

    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterRequests);
    }
}

// Authentication functions
async function handleSignIn(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'login');
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Login response:', data);
        
        if (data.success) {
            currentUser = data.user;
            showMessage('Login successful!', 'success');
            
            // CRITICAL: Handle redirect based on user type
            if (data.redirect === 'admin' || currentUser.type === 'admin') {
                console.log('Admin login detected, redirecting to admin portal...');
                setTimeout(() => {
                    window.location.href = 'admin.php';
                }, 2000); // 2 second delay to show success message
            } else {
                console.log('Resident login detected, showing dashboard...');
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
            break;
        case 'requests':
            loadRequestsData();
            break;
        case 'profile':
            loadProfileData();
            break;
        case 'certificate':
            loadCertificateTypes();
            resetCertificateForm();
            break;
    }
}

function updateUserInfo() {
    if (currentUser) {
        const userNameElements = document.querySelectorAll('.user-name');
        userNameElements.forEach(el => {
            el.textContent = currentUser.name;
        });
    }
}

// Dashboard functions
async function loadDashboardData() {
    try {
        const response = await fetch('api/requests.php?action=get_stats');
        const data = await response.json();
        
        if (data.success) {
            updateDashboardStats(data.stats);
        }
        
        // Load recent requests
        loadRecentRequests();
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
}

function updateDashboardStats(stats) {
    document.getElementById('totalRequests').textContent = stats.total || 0;
    document.getElementById('pendingRequests').textContent = stats.pending || 0;
    document.getElementById('approvedRequests').textContent = stats.approved || 0;
    document.getElementById('rejectedRequests').textContent = stats.rejected || 0;
    document.getElementById('blotterReports').textContent = stats.blotter_reports || 0;
}

async function loadRecentRequests() {
    try {
        const response = await fetch('api/requests.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayRecentRequests(data.requests.slice(0, 5));
        }
    } catch (error) {
        console.error('Failed to load recent requests:', error);
    }
}

function displayRecentRequests(requests) {
    const tbody = document.getElementById('recentRequestsBody');
    if (!tbody) return;
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #64748b;">No recent requests</td></tr>';
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
    try {
        const response = await fetch('api/requests.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayRequestsTable(data.requests);
        }
    } catch (error) {
        console.error('Failed to load requests:', error);
    }
}

function displayRequestsTable(requests) {
    const tbody = document.getElementById('requestsDataGridBody');
    const emptyState = document.getElementById('requestsEmptyState');
    
    if (!tbody) return;
    
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
                    <div class="certificate-badge ${request.type.toLowerCase().includes('barangay id') ? 'pickup-only' : 'downloadable'}">
                        ${request.type.toLowerCase().includes('barangay id') ? '📍 Pickup Only' : '📥 Downloadable'}
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
                    ${request.status === 'ready_for_pickup' ? 
                        `<button class="action-btn view" onclick="viewDocument(${request.id})" title="View">👁️</button>` : ''}
                    ${request.status === 'rejected' && request.can_reupload ? 
                        `<button class="action-btn reupload" onclick="openReuploadModal(${request.id})" title="Resubmit">🔄</button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// Certificate request functions
async function loadCertificateTypes() {
    try {
        const response = await fetch('api/requests.php?action=get_types');
        const data = await response.json();
        
        if (data.success) {
            populateCertificateTypes(data.types);
        }
    } catch (error) {
        console.error('Failed to load certificate types:', error);
    }
}

function populateCertificateTypes(types) {
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
    try {
        const response = await fetch('api/profile.php?action=get_profile');
        const data = await response.json();
        
        if (data.success) {
            populateProfileForm(data.profile);
            updateProfileHeader(data.profile);
        }
    } catch (error) {
        console.error('Failed to load profile:', error);
    }
}

function populateProfileForm(profile) {
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

function openReuploadModal(requestId) {
    document.getElementById('reuploadRequestId').value = requestId;
    document.getElementById('reuploadModal').style.display = 'flex';
}

function closeReuploadModal() {
    document.getElementById('reuploadModal').style.display = 'none';
    document.getElementById('reuploadForm').reset();
}

async function handleReuploadSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'reupload_request');
    
    try {
        const response = await fetch('api/requests.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Request resubmitted successfully!', 'success');
            closeReuploadModal();
            loadRequestsData();
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Reupload failed:', error);
        showMessage('Failed to resubmit request', 'error');
    }
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
    document.getElementById('detailDeliveryMethod').textContent = request.type.toLowerCase().includes('barangay id') ? '📍 Pickup Only' : '📥 Downloadable PDF';
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
        rejectionSection.style.display = 'block';
    } else {
        rejectionSection.style.display = 'none';
    }
    
    // Handle Barangay ID section
    const barangayIdSection = document.getElementById('detailBarangayIdSection');
    if (request.type.toLowerCase().includes('barangay id') && request.status === 'ready_for_pickup') {
        document.getElementById('barangayIdImage').src = 'https://via.placeholder.com/400x250/4f46e5/ffffff?text=BARANGAY+ID+SAMPLE';
        barangayIdSection.style.display = 'block';
    } else {
        barangayIdSection.style.display = 'none';
    }
    
    // Handle download button
    const downloadBtn = document.getElementById('detailDownloadBtn');
    if (request.status === 'approved' && request.can_download) {
        downloadBtn.style.display = 'inline-block';
        downloadBtn.onclick = () => downloadDocument(request.id);
    } else {
        downloadBtn.style.display = 'none';
    }
    
    modal.style.display = 'flex';
}

function closeRequestDetailsModal() {
    document.getElementById('requestDetailsModal').style.display = 'none';
}

// Document actions
async function downloadDocument(requestId) {
    try {
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

// Demo account function for residents
function fillDemoAccount(email, password) {
    const emailField = document.getElementById('email') || document.getElementById('loginEmail');
    const passwordField = document.getElementById('password') || document.getElementById('loginPassword');
    
    if (emailField) emailField.value = email;
    if (passwordField) passwordField.value = password;
}