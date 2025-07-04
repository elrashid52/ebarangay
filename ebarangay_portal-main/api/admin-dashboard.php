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

$action = $_GET['action'] ?? '';

switch($action) {
    case 'get_stats':
        try {
            // Get total residents
            $query = "SELECT COUNT(*) as total_residents FROM residents";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $totalResidents = $stmt->fetch(PDO::FETCH_ASSOC)['total_residents'];
            
            // Get pending requests
            $query = "SELECT COUNT(*) as pending_requests FROM requests WHERE status = 'pending'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $pendingRequests = $stmt->fetch(PDO::FETCH_ASSOC)['pending_requests'];
            
            // Get approved today
            $query = "SELECT COUNT(*) as approved_today FROM requests 
                      WHERE status = 'approved' AND DATE(updated_at) = CURDATE()";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $approvedToday = $stmt->fetch(PDO::FETCH_ASSOC)['approved_today'];
            
            // Get total requests
            $query = "SELECT COUNT(*) as total_requests FROM requests";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $totalRequests = $stmt->fetch(PDO::FETCH_ASSOC)['total_requests'];
            
            // Get rejected requests
            $query = "SELECT COUNT(*) as rejected FROM requests WHERE status = 'rejected'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $rejected = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
            
            // Open blotters (placeholder - table might not exist)
            $openBlotters = 0;
            try {
                $query = "SELECT COUNT(*) as open_blotters FROM blotter_reports WHERE status IN ('filed', 'under_investigation')";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $openBlotters = $stmt->fetch(PDO::FETCH_ASSOC)['open_blotters'];
            } catch (Exception $e) {
                // Table doesn't exist, keep as 0
            }
            
            $stats = [
                'total_residents' => $totalResidents,
                'pending_requests' => $pendingRequests,
                'approved_today' => $approvedToday,
                'open_blotters' => $openBlotters,
                'total_requests' => $totalRequests,
                'rejected' => $rejected
            ];
            
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching stats: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>