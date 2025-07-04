@@ .. @@
     </div>

    <!-- Resubmission Modal -->
    <div id="resubmissionModal" class="modal">
        <div class="modal-content resubmission-modal">
            <div class="modal-header">
                <h2>🔄 Resubmit Request</h2>
                <button class="modal-close" onclick="closeResubmissionModal()">✕</button>
            </div>
            <div class="resubmission-content">
                <!-- Request Information -->
                <div class="resubmission-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Certificate Type</label>
                            <span id="resubmissionCertificateType">-</span>
                        </div>
                        <div class="info-item">
                            <label>Original Request Date</label>
                            <span id="resubmissionOriginalDate">-</span>
                        </div>
                    </div>
                    
                    <div class="rejection-reason">
                        <h4>❌ Rejection Reason</h4>
                        <div class="rejection-message" id="resubmissionRejectionReason">-</div>
                    </div>
                </div>
                
                <!-- Resubmission Form -->
                <form id="resubmissionForm" class="resubmission-form">
                    <input type="hidden" id="resubmissionRequestId" name="request_id">
                    
                    <div class="form-section">
                        <h4>📝 Update Request Information</h4>
                        
                        <div class="form-group">
                            <label for="resubmissionPurpose">Purpose *</label>
                            <textarea id="resubmissionPurpose" name="purpose" rows="3" required 
                                      placeholder="Please specify the purpose of this certificate request..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="resubmissionComments">What changes did you make? *</label>
                            <textarea id="resubmissionComments" name="resubmission_comments" rows="3" required 
                                      placeholder="Explain what you corrected or updated in this resubmission..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>💳 Payment Information</h4>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="resubmissionPaymentMethod">Payment Method</label>
                                <select id="resubmissionPaymentMethod" name="payment_method">
                                    <option value="gcash">GCash</option>
                                    <option value="paymaya">PayMaya</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="resubmissionPaymentReference">Payment Reference</label>
                                <input type="text" id="resubmissionPaymentReference" name="payment_reference" 
                                       placeholder="Enter payment reference number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>📋 Previously Uploaded Documents</h4>
                        <div id="resubmissionExistingDocuments" class="existing-documents">
                            <!-- Existing documents will be populated here -->
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>📤 Upload New/Updated Documents</h4>
                        <p class="upload-note">Only upload documents that need to be replaced or corrected.</p>
                        
                        <div class="resubmission-uploads">
                            <div class="upload-group">
                                <label>Valid ID (if updating)</label>
                                <div class="resubmission-upload-area" onclick="document.getElementById('resubmissionValidId').click()">
                                    <div class="upload-placeholder">
                                        <div class="upload-icon">📁</div>
                                        <div class="upload-text">
                                            <div class="upload-title">Click to upload new Valid ID</div>
                                            <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
                                        </div>
                                    </div>
                                    <div class="upload-success" style="display: none;">
                                        <div class="success-icon">✅</div>
                                        <div class="success-text">
                                            <div class="success-title">File uploaded</div>
                                            <div class="success-filename"></div>
                                        </div>
                                    </div>
                                </div>
                                <input type="file" id="resubmissionValidId" name="valid_id" accept=".pdf,.jpg,.jpeg,.png" 
                                       style="display: none;" onchange="handleResubmissionFileUpload(this, 'valid_id')">
                            </div>
                            
                            <div class="upload-group">
                                <label>Proof of Billing/Residency (if updating)</label>
                                <div class="resubmission-upload-area" onclick="document.getElementById('resubmissionProofBilling').click()">
                                    <div class="upload-placeholder">
                                        <div class="upload-icon">📁</div>
                                        <div class="upload-text">
                                            <div class="upload-title">Click to upload new Proof of Billing</div>
                                            <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
                                        </div>
                                    </div>
                                    <div class="upload-success" style="display: none;">
                                        <div class="success-icon">✅</div>
                                        <div class="success-text">
                                            <div class="success-title">File uploaded</div>
                                            <div class="success-filename"></div>
                                        </div>
                                    </div>
                                </div>
                                <input type="file" id="resubmissionProofBilling" name="proof_of_billing" accept=".pdf,.jpg,.jpeg,.png" 
                                       style="display: none;" onchange="handleResubmissionFileUpload(this, 'proof_of_billing')">
                            </div>
                            
                            <div class="upload-group">
                                <label>Additional Documents (if required)</label>
                                <div class="resubmission-upload-area" onclick="document.getElementById('resubmissionAdditional').click()">
                                    <div class="upload-placeholder">
                                        <div class="upload-icon">📁</div>
                                        <div class="upload-text">
                                            <div class="upload-title">Click to upload additional documents</div>
                                            <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
                                        </div>
                                    </div>
                                    <div class="upload-success" style="display: none;">
                                        <div class="success-icon">✅</div>
                                        <div class="success-text">
                                            <div class="success-title">File uploaded</div>
                                            <div class="success-filename"></div>
                                        </div>
                                    </div>
                                </div>
                                <input type="file" id="resubmissionAdditional" name="additional_documents" accept=".pdf,.jpg,.jpeg,.png" 
                                       style="display: none;" onchange="handleResubmissionFileUpload(this, 'additional_documents')">
                            </div>
                        </div>
                    </div>
                    
                    <div class="resubmission-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeResubmissionModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">🚀 Resubmit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

-    <!-- Request Details Modal -->
+    <!-- Resubmission Modal -->
+    <div id="resubmissionModal" class="modal">
+        <div class="modal-content resubmission-modal">
+            <div class="modal-header">
+                <h2>🔄 Resubmit Request</h2>
+                <button class="modal-close" onclick="closeResubmissionModal()">✕</button>
+            </div>
+            <div class="resubmission-content">
+                <!-- Request Information -->
+                <div class="resubmission-info">
+                    <div class="info-grid">
+                        <div class="info-item">
+                            <label>Certificate Type</label>
+                            <span id="resubmissionCertificateType">-</span>
+                        </div>
+                        <div class="info-item">
+                            <label>Original Request Date</label>
+                            <span id="resubmissionOriginalDate">-</span>
+                        </div>
+                    </div>
+                    
+                    <div class="rejection-reason">
+                        <h4>❌ Rejection Reason</h4>
+                        <div class="rejection-message" id="resubmissionRejectionReason">-</div>
+                    </div>
+                </div>
+                
+                <!-- Resubmission Form -->
+                <form id="resubmissionForm" class="resubmission-form">
+                    <input type="hidden" id="resubmissionRequestId" name="request_id">
+                    
+                    <div class="form-section">
+                        <h4>📝 Update Request Information</h4>
+                        
+                        <div class="form-group">
+                            <label for="resubmissionPurpose">Purpose *</label>
+                            <textarea id="resubmissionPurpose" name="purpose" rows="3" required 
+                                      placeholder="Please specify the purpose of this certificate request..."></textarea>
+                        </div>
+                        
+                        <div class="form-group">
+                            <label for="resubmissionComments">What changes did you make? *</label>
+                            <textarea id="resubmissionComments" name="resubmission_comments" rows="3" required 
+                                      placeholder="Explain what you corrected or updated in this resubmission..."></textarea>
+                        </div>
+                    </div>
+                    
+                    <div class="form-section">
+                        <h4>💳 Payment Information</h4>
+                        
+                        <div class="form-grid">
+                            <div class="form-group">
+                                <label for="resubmissionPaymentMethod">Payment Method</label>
+                                <select id="resubmissionPaymentMethod" name="payment_method">
+                                    <option value="gcash">GCash</option>
+                                    <option value="paymaya">PayMaya</option>
+                                    <option value="bank_transfer">Bank Transfer</option>
+                                </select>
+                            </div>
+                            
+                            <div class="form-group">
+                                <label for="resubmissionPaymentReference">Payment Reference</label>
+                                <input type="text" id="resubmissionPaymentReference" name="payment_reference" 
+                                       placeholder="Enter payment reference number">
+                            </div>
+                        </div>
+                    </div>
+                    
+                    <div class="form-section">
+                        <h4>📋 Previously Uploaded Documents</h4>
+                        <div id="resubmissionExistingDocuments" class="existing-documents">
+                            <!-- Existing documents will be populated here -->
+                        </div>
+                    </div>
+                    
+                    <div class="form-section">
+                        <h4>📤 Upload New/Updated Documents</h4>
+                        <p class="upload-note">Only upload documents that need to be replaced or corrected.</p>
+                        
+                        <div class="resubmission-uploads">
+                            <div class="upload-group">
+                                <label>Valid ID (if updating)</label>
+                                <div class="resubmission-upload-area" onclick="document.getElementById('resubmissionValidId').click()">
+                                    <div class="upload-placeholder">
+                                        <div class="upload-icon">📁</div>
+                                        <div class="upload-text">
+                                            <div class="upload-title">Click to upload new Valid ID</div>
+                                            <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
+                                        </div>
+                                    </div>
+                                    <div class="upload-success" style="display: none;">
+                                        <div class="success-icon">✅</div>
+                                        <div class="success-text">
+                                            <div class="success-title">File uploaded</div>
+                                            <div class="success-filename"></div>
+                                        </div>
+                                    </div>
+                                </div>
+                                <input type="file" id="resubmissionValidId" name="valid_id" accept=".pdf,.jpg,.jpeg,.png" 
+                                       style="display: none;" onchange="handleResubmissionFileUpload(this, 'valid_id')">
+                            </div>
+                            
+                            <div class="upload-group">
+                                <label>Proof of Billing/Residency (if updating)</label>
+                                <div class="resubmission-upload-area" onclick="document.getElementById('resubmissionProofBilling').click()">
+                                    <div class="upload-placeholder">
+                                        <div class="upload-icon">📁</div>
+                                        <div class="upload-text">
+                                            <div class="upload-title">Click to upload new Proof of Billing</div>
+                                            <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
+                                        </div>
+                                    </div>
+                                    <div class="upload-success" style="display: none;">
+                                        <div class="success-icon">✅</div>
+                                        <div class="success-text">
+                                            <div class="success-title">File uploaded</div>
+                                            <div class="success-filename"></div>
+                                        </div>
+                                    </div>
+                                </div>
+                                <input type="file" id="resubmissionProofBilling" name="proof_of_billing" accept=".pdf,.jpg,.jpeg,.png" 
+                                       style="display: none;" onchange="handleResubmissionFileUpload(this, 'proof_of_billing')">
+                            </div>
+                            
+                            <div class="upload-group">
+                                <label>Additional Documents (if required)</label>
+                                <div class="resubmission-upload-area" onclick="document.getElementById('resubmissionAdditional').click()">
+                                    <div class="upload-placeholder">
+                                        <div class="upload-icon">📁</div>
+                                        <div class="upload-text">
+                                            <div class="upload-title">Click to upload additional documents</div>
+                                            <div class="upload-subtitle">PDF, JPG, PNG (Max 5MB)</div>
+                                        </div>
+                                    </div>
+                                    <div class="upload-success" style="display: none;">
+                                        <div class="success-icon">✅</div>
+                                        <div class="success-text">
+                                            <div class="success-title">File uploaded</div>
+                                            <div class="success-filename"></div>
+                                        </div>
+                                    </div>
+                                </div>
+                                <input type="file" id="resubmissionAdditional" name="additional_documents" accept=".pdf,.jpg,.jpeg,.png" 
+                                       style="display: none;" onchange="handleResubmissionFileUpload(this, 'additional_documents')">
+                            </div>
+                        </div>
+                    </div>
+                    
+                    <div class="resubmission-actions">
+                        <button type="button" class="btn btn-secondary" onclick="closeResubmissionModal()">Cancel</button>
+                        <button type="submit" class="btn btn-primary">🚀 Resubmit Request</button>
+                    </div>
+                </form>
+            </div>
+        </div>
+    </div>
+
+    <!-- Request Details Modal -->