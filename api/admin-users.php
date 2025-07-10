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
            $search = $_GET['search'] ?? '';
            $roleFilter = $_GET['role_filter'] ?? '';
            $statusFilter = $_GET['status_filter'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            // Build WHERE conditions
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            if (!empty($roleFilter)) {
                $whereConditions[] = "role = :role";
                $params[':role'] = $roleFilter;
            }
            
            if (!empty($statusFilter)) {
                $whereConditions[] = "status = :status";
                $params[':status'] = $statusFilter;
            }
            
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM admin_users $whereClause";
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get admin users with pagination
            $query = "SELECT id, first_name, last_name, email, role, status, last_login, created_at
                      FROM admin_users 
                      $whereClause
                      ORDER BY created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $users = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = [
                    'id' => $row['id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'role' => $row['role'],
                    'status' => $row['status'],
                    'last_login' => $row['last_login'],
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode([
                'success' => true, 
                'users' => $users,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching admin users: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_by_id':
        $id = $_GET['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        try {
            $query = "SELECT id, first_name, last_name, email, role, status, created_at FROM admin_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching user: ' . $e->getMessage()]);
        }
        break;
        
    case 'add':
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? 'password';
        $role = $_POST['role'] ?? 'Admin';
        $status = $_POST['status'] ?? 'Active';
        
        if(empty($first_name) || empty($last_name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        try {
            // Check if email already exists
            $checkQuery = "SELECT id FROM admin_users WHERE email = :email";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            if($checkStmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }
            
            // Insert new admin user
            $query = "INSERT INTO admin_users (first_name, last_name, email, password, role, status, created_by) 
                      VALUES (:first_name, :last_name, :email, :password, :role, :status, :created_by)";
            
            $stmt = $db->prepare($query);
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':created_by', $_SESSION['admin_id']);
            
            if($stmt->execute()) {
                $newUserId = $db->lastInsertId();
                
                // Log activity
                logAdminActivity($_SESSION['admin_id'], 'create_admin_user', 'admin_user', $newUserId, "Created new admin user: $first_name $last_name ($role)");
                
                echo json_encode(['success' => true, 'message' => 'Admin user added successfully', 'user_id' => $newUserId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add admin user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding admin user: ' . $e->getMessage()]);
        }
        break;
        
    case 'update':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'Admin';
        $status = $_POST['status'] ?? 'Active';
        
        if(empty($first_name) || empty($last_name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        try {
            // Check if email already exists for other users
            $checkQuery = "SELECT id FROM admin_users WHERE email = :email AND id != :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if($checkStmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
                exit;
            }
            
            // Update admin user
            $query = "UPDATE admin_users SET 
                        first_name = :first_name, last_name = :last_name, email = :email,
                        role = :role, status = :status, updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log activity
                logAdminActivity($_SESSION['admin_id'], 'update_admin_user', 'admin_user', $id, "Updated admin user: $first_name $last_name ($role)");
                
                echo json_encode(['success' => true, 'message' => 'Admin user updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update admin user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating admin user: ' . $e->getMessage()]);
        }
        break;
        
    case 'reset_password':
        $id = $_POST['id'] ?? '';
        $new_password = $_POST['new_password'] ?? 'password';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        try {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE admin_users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log activity
                logAdminActivity($_SESSION['admin_id'], 'reset_password', 'admin_user', $id, "Reset password for admin user ID: $id");
                
                echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error resetting password: ' . $e->getMessage()]);
        }
        break;
        
    case 'toggle_status':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        try {
            // Get current status
            $query = "SELECT status, first_name, last_name FROM admin_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $newStatus = ($user['status'] === 'Active') ? 'Inactive' : 'Active';
                
                // Update status
                $updateQuery = "UPDATE admin_users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':status', $newStatus);
                $updateStmt->bindParam(':id', $id);
                
                if($updateStmt->execute()) {
                    // Log activity
                    logAdminActivity($_SESSION['admin_id'], 'toggle_status', 'admin_user', $id, "Changed status to $newStatus for {$user['first_name']} {$user['last_name']}");
                    
                    echo json_encode(['success' => true, 'message' => "User status changed to $newStatus", 'new_status' => $newStatus]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        // Prevent deleting yourself
        if($id == $_SESSION['admin_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
            exit;
        }
        
        try {
            // Get user info for logging
            $query = "SELECT first_name, last_name FROM admin_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Delete user
            $query = "DELETE FROM admin_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log activity
                logAdminActivity($_SESSION['admin_id'], 'delete_admin_user', 'admin_user', $id, "Deleted admin user: {$user['first_name']} {$user['last_name']}");
                
                echo json_encode(['success' => true, 'message' => 'Admin user deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete admin user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting admin user: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_statistics':
        try {
            $stats = [];
            
            // Total admin users
            $query = "SELECT COUNT(*) as total FROM admin_users";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active admin users
            $query = "SELECT COUNT(*) as active FROM admin_users WHERE status = 'Active'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
            
            // New admin users this month
            $query = "SELECT COUNT(*) as new_this_month FROM admin_users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['new_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_this_month'];
            
            // Role distribution
            $query = "SELECT 
                        COUNT(CASE WHEN role = 'Super Admin' THEN 1 END) as super_admins,
                        COUNT(CASE WHEN role = 'Admin' THEN 1 END) as admins,
                        COUNT(CASE WHEN role = 'Moderator' THEN 1 END) as moderators,
                        COUNT(CASE WHEN role = 'Staff' THEN 1 END) as staff
                      FROM admin_users";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $roleStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['role_distribution'] = $roleStats;
            
            echo json_encode(['success' => true, 'statistics' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching statistics: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper function to log admin activities
function logAdminActivity($adminId, $action, $targetType, $targetId, $details) {
    try {
        global $db;
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
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}
?>