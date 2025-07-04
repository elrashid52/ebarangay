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
    case 'get_activities':
        try {
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;
            
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            $adminFilter = $_GET['admin_filter'] ?? '';
            $actionFilter = $_GET['action_filter'] ?? '';
            $activityType = $_GET['activity_type'] ?? 'all'; // 'admin', 'user', 'all'
            
            // Build WHERE conditions
            $whereConditions = [];
            $params = [];
            
            if ($dateFrom) {
                $whereConditions[] = "DATE(created_at) >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereConditions[] = "DATE(created_at) <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            if ($actionFilter) {
                $whereConditions[] = "action LIKE :action_filter";
                $params[':action_filter'] = "%$actionFilter%";
            }
            
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            $activities = [];
            
            // Get admin activities
            if ($activityType === 'all' || $activityType === 'admin') {
                $adminWhere = $whereClause;
                if ($adminFilter) {
                    $adminWhere .= ($whereClause ? ' AND ' : 'WHERE ') . "(r.first_name LIKE :admin_filter OR r.last_name LIKE :admin_filter OR r.email LIKE :admin_filter)";
                    $params[':admin_filter'] = "%$adminFilter%";
                }
                
                $query = "SELECT 
                            al.id,
                            al.action,
                            al.target_type,
                            al.target_id,
                            al.details,
                            al.ip_address,
                            al.created_at,
                            COALESCE(CONCAT(au.first_name, ' ', au.last_name), CONCAT(r.first_name, ' ', r.last_name), 'Unknown Admin') as admin_name,
                            COALESCE(au.email, r.email, 'unknown@admin.com') as admin_email,
                            'admin' as activity_type
                          FROM admin_activity_log al
                          LEFT JOIN admin_users au ON al.admin_id = au.id
                          LEFT JOIN residents r ON al.admin_id = r.id AND r.role IN ('Admin', 'Super Admin')
                          $adminWhere
                          ORDER BY al.created_at DESC";
                
                $stmt = $db->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $activities[] = $row;
                }
            }
            
            // Get user activities
            if ($activityType === 'all' || $activityType === 'user') {
                $userWhere = $whereClause;
                if ($adminFilter) {
                    $userWhere .= ($whereClause ? ' AND ' : 'WHERE ') . "(r.first_name LIKE :admin_filter OR r.last_name LIKE :admin_filter OR r.email LIKE :admin_filter)";
                    if (!isset($params[':admin_filter'])) {
                        $params[':admin_filter'] = "%$adminFilter%";
                    }
                }
                
                $query = "SELECT 
                            ul.id,
                            ul.action,
                            ul.target_type,
                            ul.target_id,
                            ul.details,
                            ul.ip_address,
                            ul.created_at,
                            CONCAT(r.first_name, ' ', r.last_name) as admin_name,
                            r.email as admin_email,
                            CONCAT('user (', ul.user_type, ')') as activity_type
                          FROM user_activity_log ul
                          JOIN residents r ON ul.user_id = r.id
                          $userWhere
                          ORDER BY ul.created_at DESC";
                
                $stmt = $db->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $activities[] = $row;
                }
            }
            
            // Sort all activities by date
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Apply pagination
            $totalActivities = count($activities);
            $activities = array_slice($activities, $offset, $limit);
            
            echo json_encode([
                'success' => true, 
                'activities' => $activities,
                'total' => $totalActivities,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalActivities / $limit)
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching activities: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_activity_stats':
        try {
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            
            // Admin activities stats
            $query = "SELECT 
                        COUNT(*) as total_admin_activities,
                        COUNT(CASE WHEN action = 'login' THEN 1 END) as admin_logins,
                        COUNT(CASE WHEN action LIKE '%approve%' THEN 1 END) as approvals,
                        COUNT(CASE WHEN action LIKE '%reject%' THEN 1 END) as rejections
                      FROM admin_activity_log 
                      WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':date_from', $dateFrom);
            $stmt->bindParam(':date_to', $dateTo);
            $stmt->execute();
            $adminStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // User activities stats
            $query = "SELECT 
                        COUNT(*) as total_user_activities,
                        COUNT(CASE WHEN action = 'login' THEN 1 END) as user_logins,
                        COUNT(CASE WHEN action LIKE '%request%' THEN 1 END) as requests_submitted,
                        COUNT(CASE WHEN action LIKE '%profile%' THEN 1 END) as profile_updates
                      FROM user_activity_log 
                      WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':date_from', $dateFrom);
            $stmt->bindParam(':date_to', $dateTo);
            $stmt->execute();
            $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'admin_stats' => $adminStats,
                'user_stats' => $userStats
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching stats: ' . $e->getMessage()]);
        }
        break;
        
    case 'log_activity':
        try {
            $adminId = $_SESSION['admin_id'];
            $action = $_POST['log_action'] ?? '';
            $targetType = $_POST['target_type'] ?? null;
            $targetId = $_POST['target_id'] ?? null;
            $details = $_POST['details'] ?? '';
            
            if (empty($action)) {
                echo json_encode(['success' => false, 'message' => 'Action is required']);
                exit;
            }
            
            $query = "INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address, user_agent) 
                      VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip_address, :user_agent)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':target_type', $targetType);
            $stmt->bindParam(':target_id', $targetId);
            $stmt->bindParam(':details', $details);
            $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to log activity']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error logging activity: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>