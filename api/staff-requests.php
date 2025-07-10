<?php
session_start();
header('Content-Type: application/json');

// Check if staff is logged in
if(!isset($_SESSION['staff_id'])) {
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
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        try {
            // Staff can only approve, not upload certificates
            $query = "UPDATE requests SET 
                      status = 'approved', 
                      processed_at = CURRENT_TIMESTAMP,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log staff activity
                logStaffActivity($_SESSION['staff_id'], 'approve_request', 'request', $id, "Approved request ID: $id");
                
                echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to approve request']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error approving request: ' . $e->getMessage()]);
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
                // Log staff activity
                logStaffActivity($_SESSION['staff_id'], 'reject_request', 'request', $id, "Rejected request ID: $id - Reason: $reason");
                
                echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reject request']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error rejecting request: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper function to log staff activities
function logStaffActivity($staffId, $action, $targetType, $targetId, $details) {
    try {
        global $db;
        $query = "INSERT INTO staff_activity_log (staff_id, action, target_type, target_id, details, ip_address, user_agent) 
                  VALUES (:staff_id, :action, :target_type, :target_id, :details, :ip_address, :user_agent)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':staff_id', $staffId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':target_type', $targetType);
        $stmt->bindParam(':target_id', $targetId);
        $stmt->bindParam(':details', $details);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to log staff activity: " . $e->getMessage());
    }
}
?>