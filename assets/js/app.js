@@ .. @@
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
    const resubmissionForm = document.getElementById('resubmissionForm');
    if (resubmissionForm) {
        resubmissionForm.addEventListener('submit', handleResubmissionSubmit);
     }
 }

+// Open resubmission modal
+async function openResubmissionModal(requestId) {
+    try {
+        const response = await fetch(`api/requests.php?action=get_rejection_details&id=${requestId}`);
+        const data = await response.json();
+        
+        if (data.success) {
+            showResubmissionModal(data.request);
+        } else {
+            showMessage(data.message || 'Failed to load request details', 'error');
+        }
+    } catch (error) {
+        console.error('Failed to load rejection details:', error);
+        showMessage('Failed to load request details', 'error');
+    }
+}
+
+// Show resubmission modal
+function showResubmissionModal(request) {
+    const modal = document.getElementById('resubmissionModal');
+    
+    // Populate modal with request data
+    document.getElementById('resubmissionRequestId').value = request.id;
+    document.getElementById('resubmissionCertificateType').textContent = request.type;
+    document.getElementById('resubmissionOriginalDate').textContent = formatDate(request.created_at);
+    document.getElementById('resubmissionRejectionReason').textContent = request.admin_notes || 'No specific reason provided';
+    document.getElementById('resubmissionPurpose').value = request.purpose;
+    
+    // Populate payment details if available
+    if (request.request_details && request.request_details.payment_method) {
+        document.getElementById('resubmissionPaymentMethod').value = request.request_details.payment_method;
+        document.getElementById('resubmissionPaymentReference').value = request.request_details.payment_reference || '';
+    }
+    
+    // Show existing documents
+    const documentsContainer = document.getElementById('resubmissionExistingDocuments');
+    documentsContainer.innerHTML = '';
+    
+    if (request.request_details && request.request_details.uploaded_documents) {
+        Object.keys(request.request_details.uploaded_documents).forEach(docType => {
+            const docItem = document.createElement('div');
+            docItem.className = 'existing-document-item';
+            docItem.innerHTML = `
+                <div class="document-info">
+                    <span class="document-name">${formatDocumentName(docType)}</span>
+                    <span class="document-status">✅ Previously uploaded</span>
+                </div>
+                <button type="button" class="btn-action view" onclick="viewUploadedDocument('${request.id}', '${docType}')">
+                    👁️ View
+                </button>
+            `;
+            documentsContainer.appendChild(docItem);
+        });
+    }
+    
+    modal.style.display = 'flex';
+}
+
+// Close resubmission modal
+function closeResubmissionModal() {
+    const modal = document.getElementById('resubmissionModal');
+    modal.style.display = 'none';
+    
+    // Reset form
+    const form = document.getElementById('resubmissionForm');
+    if (form) {
+        form.reset();
+    }
+    
+    // Clear file upload areas
+    const uploadAreas = document.querySelectorAll('.resubmission-upload-area');
+    uploadAreas.forEach(area => {
+        const placeholder = area.querySelector('.upload-placeholder');
+        const success = area.querySelector('.upload-success');
+        if (placeholder && success) {
+            placeholder.style.display = 'flex';
+            success.style.display = 'none';
+        }
+    });
+}
+
+// Handle resubmission form submission
+async function handleResubmissionSubmit(e) {
+    e.preventDefault();
+    
+    const formData = new FormData(e.target);
+    formData.append('action', 'resubmit_request');
+    
+    // Validate required fields
+    const purpose = formData.get('purpose');
+    const resubmissionComments = formData.get('resubmission_comments');
+    
+    if (!purpose.trim()) {
+        showMessage('Purpose is required', 'error');
+        return;
+    }
+    
+    if (!resubmissionComments.trim()) {
+        showMessage('Please explain what changes you made', 'error');
+        return;
+    }
+    
+    // Disable submit button
+    const submitBtn = e.target.querySelector('button[type="submit"]');
+    const originalText = submitBtn.textContent;
+    submitBtn.disabled = true;
+    submitBtn.textContent = 'Resubmitting...';
+    
+    try {
+        const response = await fetch('api/requests.php', {
+            method: 'POST',
+            body: formData
+        });
+        
+        const data = await response.json();
+        
+        if (data.success) {
+            showMessage('Request resubmitted successfully!', 'success');
+            closeResubmissionModal();
+            loadRequestsData(); // Refresh the requests list
+        } else {
+            showMessage(data.message || 'Failed to resubmit request', 'error');
+        }
+    } catch (error) {
+        console.error('Resubmission failed:', error);
+        showMessage('Failed to resubmit request. Please try again.', 'error');
+    } finally {
+        // Re-enable submit button
+        submitBtn.disabled = false;
+        submitBtn.textContent = originalText;
+    }
+}
+
+// Handle file upload in resubmission modal
+function handleResubmissionFileUpload(input, documentType) {
+    const file = input.files[0];
+    if (!file) return;
+    
+    // Validate file
+    if (file.size > 5 * 1024 * 1024) {
+        showMessage('File size must be less than 5MB', 'error');
+        input.value = '';
+        return;
+    }
+    
+    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
+    if (!allowedTypes.includes(file.type)) {
+        showMessage('Only PDF, JPG, and PNG files are allowed', 'error');
+        input.value = '';
+        return;
+    }
+    
+    // Update UI
+    const uploadArea = input.closest('.resubmission-upload-area');
+    const placeholder = uploadArea.querySelector('.upload-placeholder');
+    const success = uploadArea.querySelector('.upload-success');
+    const filename = success.querySelector('.success-filename');
+    
+    placeholder.style.display = 'none';
+    success.style.display = 'flex';
+    filename.textContent = file.name;
+}
+
+// Format document name for display
+function formatDocumentName(docType) {
+    const nameMap = {
+        'valid_id': 'Valid ID',
+        'proof_of_billing': 'Proof of Billing',
+        'cedula': 'Cedula',
+        'proof_of_residency': 'Proof of Residency',
+        'passport_photo': 'Passport Photo',
+        'proof_of_unemployment': 'Proof of Unemployment'
+    };
+    return nameMap[docType] || docType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
+}
+
+// View uploaded document
+async function viewUploadedDocument(requestId, documentType) {
+    try {
+        window.open(`api/admin-requests.php?action=view_document&request_id=${requestId}&document_type=${documentType}`, '_blank');
+    } catch (error) {
+        console.error('Failed to view document:', error);
+        showMessage('Failed to view document', 'error');
+    }
+}
+
 function showRequestDetailsModal(request) {