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
            // Create announcements table if it doesn't exist
            $createTableQuery = "CREATE TABLE IF NOT EXISTS announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                type ENUM('general', 'urgent', 'event', 'service') DEFAULT 'general',
                priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
                is_active BOOLEAN DEFAULT TRUE,
                expiry_date DATE NULL,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $db->exec($createTableQuery);
            
            $query = "SELECT * FROM announcements ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $announcements = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $announcements[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'type' => $row['type'],
                    'priority' => $row['priority'],
                    'is_active' => $row['is_active'],
                    'expiry_date' => $row['expiry_date'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
            
            echo json_encode(['success' => true, 'announcements' => $announcements]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching announcements: ' . $e->getMessage()]);
        }
        break;
        
    case 'create':
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'general';
        $priority = $_POST['priority'] ?? 'normal';
        $expiry_date = $_POST['expiry_date'] ?? null;
        
        if(empty($title) || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Title and content are required']);
            exit;
        }
        
        try {
            // Create announcements table if it doesn't exist
            $createTableQuery = "CREATE TABLE IF NOT EXISTS announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                type ENUM('general', 'urgent', 'event', 'service') DEFAULT 'general',
                priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
                is_active BOOLEAN DEFAULT TRUE,
                expiry_date DATE NULL,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $db->exec($createTableQuery);
            
            $query = "INSERT INTO announcements (title, content, type, priority, expiry_date, created_by) 
                      VALUES (:title, :content, :type, :priority, :expiry_date, :created_by)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':expiry_date', $expiry_date);
            $stmt->bindParam(':created_by', $_SESSION['staff_id']);
            
            if($stmt->execute()) {
                $announcementId = $db->lastInsertId();
                
                // Log staff activity
                logStaffActivity($_SESSION['staff_id'], 'create_announcement', 'announcement', $announcementId, "Created announcement: $title");
                
                echo json_encode(['success' => true, 'message' => 'Announcement created successfully', 'announcement_id' => $announcementId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create announcement']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error creating announcement: ' . $e->getMessage()]);
        }
        break;
        
    case 'update':
        $id = $_POST['id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'general';
        $priority = $_POST['priority'] ?? 'normal';
        $expiry_date = $_POST['expiry_date'] ?? null;
        $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
        
        if(empty($id) || empty($title) || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'ID, title and content are required']);
            exit;
        }
        
        try {
            $query = "UPDATE announcements SET 
                      title = :title, content = :content, type = :type, 
                      priority = :priority, expiry_date = :expiry_date, is_active = :is_active,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':expiry_date', $expiry_date);
            $stmt->bindParam(':is_active', $is_active);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log staff activity
                logStaffActivity($_SESSION['staff_id'], 'update_announcement', 'announcement', $id, "Updated announcement: $title");
                
                echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update announcement']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating announcement: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Announcement ID is required']);
            exit;
        }
        
        try {
            $query = "DELETE FROM announcements WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log staff activity
                logStaffActivity($_SESSION['staff_id'], 'delete_announcement', 'announcement', $id, "Deleted announcement ID: $id");
                
                echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete announcement']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting announcement: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper function to log staff activities
function logStaffActivity($staffId, $action, $targetType, $targetId, $details) {
    try {
        global $db;
        
        // Create staff_activity_log table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS staff_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50),
            target_id INT,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_staff_id (staff_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        )";
        $db->exec($createTableQuery);
        
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