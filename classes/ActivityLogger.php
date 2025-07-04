<?php
class ActivityLogger {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Log admin activity
    public function logAdminActivity($adminId, $action, $targetType = null, $targetId = null, $details = null) {
        try {
            $query = "INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address, user_agent) 
                      VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip_address, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':target_type', $targetType);
            $stmt->bindParam(':target_id', $targetId);
            $stmt->bindParam(':details', $details);
            $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log admin activity: " . $e->getMessage());
            return false;
        }
    }
    
    // Log user activity
    public function logUserActivity($userId, $userType, $action, $targetType = null, $targetId = null, $details = null) {
        try {
            $query = "INSERT INTO user_activity_log (user_id, user_type, action, target_type, target_id, details, ip_address, user_agent) 
                      VALUES (:user_id, :user_type, :action, :target_type, :target_id, :details, :ip_address, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':user_type', $userType);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':target_type', $targetType);
            $stmt->bindParam(':target_id', $targetId);
            $stmt->bindParam(':details', $details);
            $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log user activity: " . $e->getMessage());
            return false;
        }
    }
    
    // Get activity statistics
    public function getActivityStats($dateFrom = null, $dateTo = null) {
        try {
            if (!$dateFrom) $dateFrom = date('Y-m-d', strtotime('-30 days'));
            if (!$dateTo) $dateTo = date('Y-m-d');
            
            $stats = [];
            
            // Admin activities
            $query = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN action = 'login' THEN 1 END) as logins,
                        COUNT(CASE WHEN action LIKE '%approve%' THEN 1 END) as approvals,
                        COUNT(CASE WHEN action LIKE '%reject%' THEN 1 END) as rejections
                      FROM admin_activity_log 
                      WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':date_from', $dateFrom);
            $stmt->bindParam(':date_to', $dateTo);
            $stmt->execute();
            $stats['admin'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // User activities
            $query = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN action = 'login' THEN 1 END) as logins,
                        COUNT(CASE WHEN action LIKE '%request%' THEN 1 END) as requests,
                        COUNT(CASE WHEN action LIKE '%profile%' THEN 1 END) as profile_updates
                      FROM user_activity_log 
                      WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':date_from', $dateFrom);
            $stmt->bindParam(':date_to', $dateTo);
            $stmt->execute();
            $stats['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Failed to get activity stats: " . $e->getMessage());
            return false;
        }
    }
}
?>