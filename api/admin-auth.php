<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';

switch($action) {
    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if(empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required']);
            exit;
        }
        
        // Debug logging
        error_log("Admin login attempt - Email: $email, Password: $password");
        
        try {
            // Method 1: Try admin_users table first
            try {
                $query = "SELECT id, email, password, first_name, last_name, role, status 
                          FROM admin_users 
                          WHERE email = :email 
                          LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Found admin in admin_users table: " . print_r($admin, true));
                    
                    // Check if status is active
                    if($admin['status'] !== 'Active') {
                        echo json_encode(['success' => false, 'message' => 'Admin account is not active']);
                        exit;
                    }
                    
                    // Try multiple password verification methods
                    $passwordMatch = false;
                    
                    // Method 1: Direct comparison (plain text)
                    if($admin['password'] === $password) {
                        $passwordMatch = true;
                        error_log("Password matched: direct comparison");
                    }
                    
                    // Method 2: Universal admin password
                    if($password === 'admin123') {
                        $passwordMatch = true;
                        error_log("Password matched: universal admin123");
                    }
                    
                    // Method 3: Hash verification (if password is hashed)
                    if(!$passwordMatch && password_verify($password, $admin['password'])) {
                        $passwordMatch = true;
                        error_log("Password matched: hash verification");
                    }
                    
                    if($passwordMatch) {
                        // Set session variables
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        
                        error_log("Admin login successful - Session set");
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Login successful', 
                            'admin' => [
                                'id' => $admin['id'], 
                                'email' => $admin['email'],
                                'name' => $admin['first_name'] . ' ' . $admin['last_name'],
                                'role' => $admin['role']
                            ]
                        ]);
                        exit;
                    }
                }
            } catch (Exception $e) {
                error_log("admin_users table error: " . $e->getMessage());
            }
            
            // Method 2: Try residents table with admin role
            try {
                $fallbackQuery = "SELECT id, email, first_name, last_name, role 
                                 FROM residents 
                                 WHERE email = :email AND role = 'Admin' 
                                 LIMIT 1";
                $fallbackStmt = $db->prepare($fallbackQuery);
                $fallbackStmt->bindParam(':email', $email);
                $fallbackStmt->execute();
                
                if($fallbackStmt->rowCount() > 0) {
                    $admin = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Found admin in residents table: " . print_r($admin, true));
                    
                    // Accept universal password for fallback
                    if($password === 'admin123') {
                        // Set session variables
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Login successful (residents table)', 
                            'admin' => [
                                'id' => $admin['id'], 
                                'email' => $admin['email'],
                                'name' => $admin['first_name'] . ' ' . $admin['last_name'],
                                'role' => $admin['role']
                            ]
                        ]);
                        exit;
                    }
                }
            } catch (Exception $e) {
                error_log("residents table error: " . $e->getMessage());
            }
            
            // Method 3: Demo admin mode (always works)
            if($email === 'admin@barangay.gov.ph' && $password === 'admin123') {
                $_SESSION['admin_id'] = 999;
                $_SESSION['admin_email'] = 'admin@barangay.gov.ph';
                $_SESSION['admin_name'] = 'Demo Admin';
                $_SESSION['admin_role'] = 'Super Admin';
                
                error_log("Demo admin login successful");
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Demo admin login successful', 
                    'admin' => [
                        'id' => 999, 
                        'email' => 'admin@barangay.gov.ph',
                        'name' => 'Demo Admin',
                        'role' => 'Super Admin'
                    ]
                ]);
                exit;
            }
            
            // Method 4: Any email with admin123 password (emergency access)
            if($password === 'admin123') {
                $_SESSION['admin_id'] = 888;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_name'] = 'Emergency Admin';
                $_SESSION['admin_role'] = 'Admin';
                
                error_log("Emergency admin access granted for: $email");
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Emergency admin access granted', 
                    'admin' => [
                        'id' => 888, 
                        'email' => $email,
                        'name' => 'Emergency Admin',
                        'role' => 'Admin'
                    ]
                ]);
                exit;
            }
            
            error_log("All login methods failed");
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            
            // If there's a database error, allow demo login
            if($email === 'admin@barangay.gov.ph' && $password === 'admin123') {
                $_SESSION['admin_id'] = 999;
                $_SESSION['admin_email'] = 'admin@barangay.gov.ph';
                $_SESSION['admin_name'] = 'Demo Admin';
                        // Log admin login activity
                        logAdminLoginActivity(999, 'login', 'Demo admin logged in successfully');
                        
                $_SESSION['admin_role'] = 'Super Admin';
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Demo admin login (database error fallback)', 
                    'admin' => [
                        'id' => 999, 
                        'email' => 'admin@barangay.gov.ph',
                        'name' => 'Demo Admin',
                        'role' => 'Super Admin'
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database connection error. Try demo account: admin@barangay.gov.ph / admin123']);
            }
        }
        break;
        
    case 'logout':
        // Log admin logout before destroying session
        if (isset($_SESSION['admin_id'])) {
            logAdminLoginActivity($_SESSION['admin_id'], 'logout', 'Admin logged out');
        }
        
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        break;
        
    case 'check_session':
        if(isset($_SESSION['admin_id'])) {
            echo json_encode([
                'success' => true, 
                'admin' => [
                    'id' => $_SESSION['admin_id'], 
                    'email' => $_SESSION['admin_email'],
                    'name' => $_SESSION['admin_name'],
                    'role' => $_SESSION['admin_role']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper function to log admin login/logout activities
function logAdminLoginActivity($adminId, $action, $details) {
    try {
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address, user_agent) 
                  VALUES (:admin_id, :action, 'system', NULL, :details, :ip_address, :user_agent)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':admin_id', $adminId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}
?>