@@ .. @@
         break;
         
+    case 'update_resubmission_status':
+        $id = $_POST['id'] ?? '';
+        $status = $_POST['status'] ?? '';
+        $adminNotes = $_POST['admin_notes'] ?? '';
+        
+        if(empty($id) || empty($status)) {
+            echo json_encode(['success' => false, 'message' => 'Request ID and status are required']);
+            exit;
+        }
+        
+        try {
+            $query = "UPDATE requests SET 
+                      status = :status, 
+                      processed_at = CURRENT_TIMESTAMP,
+                      updated_at = CURRENT_TIMESTAMP,
+                      admin_notes = :admin_notes,
+                      can_reupload = CASE WHEN :status = 'rejected' THEN 1 ELSE 0 END
+                      WHERE id = :id";
+            $stmt = $db->prepare($query);
+            $stmt->bindParam(':status', $status);
+            $stmt->bindParam(':admin_notes', $adminNotes);
+            $stmt->bindParam(':id', $id);
+            
+            if($stmt->execute()) {
+                // Log admin activity
+                try {
+                    $logQuery = "INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address) 
+                                VALUES (:admin_id, :action, 'request', :target_id, :details, :ip_address)";
+                    $logStmt = $db->prepare($logQuery);
+                    $logStmt->bindValue(':admin_id', $_SESSION['admin_id']);
+                    $logStmt->bindValue(':action', $status === 'approved' ? 'approve_resubmission' : 'reject_resubmission');
+                    $logStmt->bindParam(':target_id', $id);
+                    $logStmt->bindValue(':details', "Updated resubmitted request status to: $status");
+                    $logStmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
+                    $logStmt->execute();
+                } catch (Exception $e) {
+                    error_log("Failed to log admin activity: " . $e->getMessage());
+                }
+                
+                echo json_encode(['success' => true, 'message' => 'Request status updated successfully']);
+            } else {
+                echo json_encode(['success' => false, 'message' => 'Failed to update request status']);
+            }
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => 'Error updating request: ' . $e->getMessage()]);
+        }
+        break;
+        
     default: