@@ .. @@
         break;
         
+    case 'get_rejection_details':
+        $id = $_GET['id'] ?? '';
+        
+        if(empty($id)) {
+            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
+            exit;
+        }
+        
+        try {
+            $query = "SELECT r.*, res.first_name, res.last_name, res.email as resident_email
+                      FROM requests r
+                      JOIN residents res ON r.resident_id = res.id
+                      WHERE r.id = :id AND r.resident_id = :resident_id AND r.status = 'rejected'";
+            $stmt = $db->prepare($query);
+            $stmt->bindParam(':id', $id);
+            $stmt->bindParam(':resident_id', $resident_id);
+            $stmt->execute();
+            
+            if($stmt->rowCount() > 0) {
+                $row = $stmt->fetch(PDO::FETCH_ASSOC);
+                $request = [
+                    'id' => $row['id'],
+                    'type' => $row['type'],
+                    'purpose' => $row['purpose'],
+                    'status' => $row['status'],
+                    'processing_fee' => $row['processing_fee'] ?? 0,
+                    'created_at' => $row['created_at'],
+                    'processed_at' => $row['processed_at'],
+                    'admin_notes' => $row['admin_notes'],
+                    'request_details' => json_decode($row['request_details'], true),
+                    'can_reupload' => intval($row['can_reupload'] ?? 0),
+                    'resident_name' => $row['first_name'] . ' ' . $row['last_name'],
+                    'resident_email' => $row['resident_email']
+                ];
+                
+                echo json_encode(['success' => true, 'request' => $request]);
+            } else {
+                echo json_encode(['success' => false, 'message' => 'Request not found or cannot be resubmitted']);
+            }
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => 'Error fetching request details: ' . $e->getMessage()]);
+        }
+        break;
+        
+    case 'resubmit_request':
+        $requestId = $_POST['request_id'] ?? '';
+        $purpose = $_POST['purpose'] ?? '';
+        $additionalNotes = $_POST['additional_notes'] ?? '';
+        $paymentMethod = $_POST['payment_method'] ?? '';
+        $paymentReference = $_POST['payment_reference'] ?? '';
+        $resubmissionComments = $_POST['resubmission_comments'] ?? '';
+        
+        if(empty($requestId) || empty($purpose)) {
+            echo json_encode(['success' => false, 'message' => 'Request ID and purpose are required']);
+            exit;
+        }
+        
+        try {
+            // Verify the request belongs to the current resident and can be resubmitted
+            $query = "SELECT id, type, status, can_reupload, request_details FROM requests 
+                      WHERE id = :request_id AND resident_id = :resident_id AND status = 'rejected'";
+            $stmt = $db->prepare($query);
+            $stmt->bindParam(':request_id', $requestId);
+            $stmt->bindParam(':resident_id', $resident_id);
+            $stmt->execute();
+            
+            $requestData = $stmt->fetch(PDO::FETCH_ASSOC);
+            
+            if(!$requestData || !$requestData['can_reupload']) {
+                echo json_encode(['success' => false, 'message' => 'Request cannot be resubmitted']);
+                exit;
+            }
+            
+            // Handle file uploads for resubmission
+            $uploadedDocuments = [];
+            $uploadDir = '../uploads/requests/';
+            
+            if(!is_dir($uploadDir)) {
+                mkdir($uploadDir, 0755, true);
+            }
+            
+            foreach($_FILES as $key => $file) {
+                if($file['error'] === UPLOAD_ERR_OK) {
+                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
+                    $filename = $key . '_' . $resident_id . '_' . time() . '.' . $extension;
+                    $filepath = $uploadDir . $filename;
+                    
+                    if(move_uploaded_file($file['tmp_name'], $filepath)) {
+                        $uploadedDocuments[$key] = $filename;
+                    }
+                }
+            }
+            
+            // Merge with existing documents if any
+            $existingDetails = json_decode($requestData['request_details'], true) ?? [];
+            $existingDocuments = $existingDetails['uploaded_documents'] ?? [];
+            $mergedDocuments = array_merge($existingDocuments, $uploadedDocuments);
+            
+            // Create resubmission details
+            $resubmissionDetails = [
+                'payment_method' => $paymentMethod,
+                'payment_reference' => $paymentReference,
+                'additional_notes' => $additionalNotes,
+                'uploaded_documents' => $mergedDocuments,
+                'resubmission_comments' => $resubmissionComments,
+                'resubmitted_at' => date('Y-m-d H:i:s'),
+                'original_submission' => $existingDetails
+            ];
+            
+            // Update the request
+            $updateQuery = "UPDATE requests SET 
+                            purpose = :purpose, 
+                            status = 'pending', 
+                            request_details = :request_details,
+                            admin_notes = NULL, 
+                            can_reupload = 0,
+                            updated_at = CURRENT_TIMESTAMP 
+                            WHERE id = :request_id AND resident_id = :resident_id";
+            $updateStmt = $db->prepare($updateQuery);
+            $updateStmt->bindParam(':purpose', $purpose);
+            $updateStmt->bindParam(':request_details', json_encode($resubmissionDetails));
+            $updateStmt->bindParam(':request_id', $requestId);
+            $updateStmt->bindParam(':resident_id', $resident_id);
+            
+            if($updateStmt->execute()) {
+                // Log the resubmission activity
+                try {
+                    $logQuery = "INSERT INTO user_activity_log (user_id, user_type, action, target_type, target_id, details, ip_address) 
+                                VALUES (:user_id, 'resident', 'resubmit_request', 'request', :target_id, :details, :ip_address)";
+                    $logStmt = $db->prepare($logQuery);
+                    $logStmt->bindParam(':user_id', $resident_id);
+                    $logStmt->bindParam(':target_id', $requestId);
+                    $logStmt->bindValue(':details', "Resubmitted {$requestData['type']} request with corrections");
+                    $logStmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
+                    $logStmt->execute();
+                } catch (Exception $e) {
+                    // Log error but don't fail the request
+                    error_log("Failed to log resubmission activity: " . $e->getMessage());
+                }
+                
+                echo json_encode(['success' => true, 'message' => 'Request resubmitted successfully']);
+            } else {
+                echo json_encode(['success' => false, 'message' => 'Failed to resubmit request']);
+            }
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => 'Error resubmitting request: ' . $e->getMessage()]);
+        }
+        break;
+        
     case 'get_stats':