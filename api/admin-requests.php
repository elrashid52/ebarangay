<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'get_all':
        try {
            $query = "SELECT r.*, res.first_name, res.last_name, res.email as resident_email
                      FROM requests r
                      JOIN residents res ON r.resident_id = res.id
                      ORDER BY r.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $requests = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $requests[] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'purpose' => $row['purpose'],
                    'status' => $row['status'],
                    'processing_fee' => $row['processing_fee'] ?? 0,
                    'created_at' => $row['created_at'],
                    'processed_at' => $row['processed_at'],
                    'admin_notes' => $row['admin_notes'],
                    'request_details' => $row['request_details'],
                    'document_path' => $row['document_path'],
                    'can_download' => $row['can_download'] ?? 0,
                    'resident_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'resident_email' => $row['resident_email']
                ];
            }
            
            echo json_encode(['success' => true, 'requests' => $requests]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching requests: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_recent':
        try {
            $query = "SELECT r.*, res.first_name, res.last_name
                      FROM requests r
                      JOIN residents res ON r.resident_id = res.id
                      ORDER BY r.created_at DESC
                      LIMIT 5";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $requests = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $requests[] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'resident_name' => $row['first_name'] . ' ' . $row['last_name']
                ];
            }
            
            echo json_encode(['success' => true, 'requests' => $requests]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching recent requests: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_details':
        $id = $_GET['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        try {
            $query = "SELECT r.*, res.first_name, res.last_name, res.email as resident_email
                      FROM requests r
                      JOIN residents res ON r.resident_id = res.id
                      WHERE r.id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $request = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'purpose' => $row['purpose'],
                    'status' => $row['status'],
                    'processing_fee' => $row['processing_fee'] ?? 0,
                    'created_at' => $row['created_at'],
                    'processed_at' => $row['processed_at'],
                    'admin_notes' => $row['admin_notes'],
                    'request_details' => $row['request_details'],
                    'document_path' => $row['document_path'],
                    'can_download' => $row['can_download'] ?? 0,
                    'resident_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'resident_email' => $row['resident_email']
                ];
                
                echo json_encode(['success' => true, 'request' => $request]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching request details: ' . $e->getMessage()]);
        }
        break;
        
    case 'approve':
        $id = $_POST['id'] ?? '';
        $status = $_POST['status'] ?? 'approved';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        try {
            // Handle certificate upload if provided
            $documentPath = null;
            if(isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/certificates/';
                
                // Create directory if it doesn't exist
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $file = $_FILES['certificate'];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'certificate_' . $id . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                // Validate file
                if($file['type'] !== 'application/pdf') {
                    echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
                    exit;
                }
                
                if($file['size'] > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
                    exit;
                }
                
                if(move_uploaded_file($file['tmp_name'], $filepath)) {
                    $documentPath = $filename;
                }
            }
            
            // Update request status
            $query = "UPDATE requests SET 
                      status = :status, 
                      processed_at = CURRENT_TIMESTAMP,
                      updated_at = CURRENT_TIMESTAMP,
                      can_download = 1";
            
            if($documentPath) {
                $query .= ", document_path = :document_path";
            }
            
            $query .= " WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            
            if($documentPath) {
                $stmt->bindParam(':document_path', $documentPath);
            }
            
            if($stmt->execute()) {
                $message = $status === 'approved' ? 'Request approved successfully' : 'Request status updated successfully';
                if($documentPath) {
                    $message .= ' and certificate uploaded';
                }
                
                // If rejecting, enable reupload capability
                if($status === 'rejected') {
                    $reuploadQuery = "UPDATE requests SET can_reupload = 1 WHERE id = :id";
                    $reuploadStmt = $db->prepare($reuploadQuery);
                    $reuploadStmt->bindParam(':id', $id);
                    $reuploadStmt->execute();
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update request']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating request: ' . $e->getMessage()]);
        }
        break;
        
    case 'reject':
        $id = $_POST['id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        if(empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
            exit;
        }
        
        try {
            $query = "UPDATE requests SET 
                      status = 'rejected', 
                      processed_at = CURRENT_TIMESTAMP,
                      updated_at = CURRENT_TIMESTAMP,
                      admin_notes = :reason,
                      can_reupload = 1
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':reason', $reason);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reject request']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error rejecting request: ' . $e->getMessage()]);
        }
        break;
        
    case 'view_document':
        $requestId = $_GET['request_id'] ?? '';
        $documentType = $_GET['document_type'] ?? '';
        
        if(empty($requestId)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        try {
            // Get request details
            $query = "SELECT request_details FROM requests WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $requestId);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $details = json_decode($row['request_details'], true);
                
                if(isset($details['uploaded_documents'][$documentType])) {
                    $filename = $details['uploaded_documents'][$documentType];
                    $filepath = '../uploads/requests/' . $filename;
                    
                    if(file_exists($filepath)) {
                        // Determine content type
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $contentType = finfo_file($finfo, $filepath);
                        finfo_close($finfo);
                        
                        header('Content-Type: ' . $contentType);
                        header('Content-Disposition: inline; filename="' . $filename . '"');
                        readfile($filepath);
                        exit;
                    }
                }
            }
            
            // If document not found, return placeholder
            echo json_encode(['success' => false, 'message' => 'Document not found']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error viewing document: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>