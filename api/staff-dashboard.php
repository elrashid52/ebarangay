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

$action = $_GET['action'] ?? '';

switch($action) {
    case 'get_stats':
        try {
            // Get pending requests
            $query = "SELECT COUNT(*) as pending_requests FROM requests WHERE status = 'pending'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $pendingRequests = $stmt->fetch(PDO::FETCH_ASSOC)['pending_requests'];
            
            // Get processed today (approved or rejected today)
            $query = "SELECT COUNT(*) as processed_today FROM requests 
                      WHERE (status = 'approved' OR status = 'rejected') 
                      AND DATE(updated_at) = CURDATE()";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $processedToday = $stmt->fetch(PDO::FETCH_ASSOC)['processed_today'];
            
            // Get total residents
            $query = "SELECT COUNT(*) as total_residents FROM residents WHERE role = 'Resident' OR role IS NULL";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $totalResidents = $stmt->fetch(PDO::FETCH_ASSOC)['total_residents'];
            
            // Get active announcements (placeholder)
            $activeAnnouncements = 0;
            try {
                $query = "SELECT COUNT(*) as active_announcements FROM announcements WHERE is_active = 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $activeAnnouncements = $stmt->fetch(PDO::FETCH_ASSOC)['active_announcements'];
            } catch (Exception $e) {
                // Table doesn't exist, keep as 0
            }
            
            $stats = [
                'pending_requests' => $pendingRequests,
                'processed_today' => $processedToday,
                'total_residents' => $totalResidents,
                'active_announcements' => $activeAnnouncements
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