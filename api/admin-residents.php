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
            $query = "SELECT id, first_name, last_name, middle_name, email, mobile_number, 
                             civil_status, house_no, street, purok, barangay, city, 
                             status, created_at
                      FROM residents 
                      WHERE role = 'Resident' OR role IS NULL
                      ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $residents = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $residents[] = [
                    'id' => $row['id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'middle_name' => $row['middle_name'],
                    'email' => $row['email'],
                    'mobile_number' => $row['mobile_number'],
                    'civil_status' => $row['civil_status'],
                    'house_no' => $row['house_no'],
                    'street' => $row['street'],
                    'purok' => $row['purok'],
                    'barangay' => $row['barangay'],
                    'city' => $row['city'],
                    'status' => $row['status'] ?? 'Active',
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode(['success' => true, 'residents' => $residents]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching residents: ' . $e->getMessage()]);
        }
        break;
        
    case 'add':
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $civil_status = $_POST['civil_status'] ?? 'Single';
        $password = $_POST['password'] ?? 'resident123';
        
        if(empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
            exit;
        }
        
        try {
            // Check if email already exists
            $checkQuery = "SELECT id FROM residents WHERE email = :email";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            if($checkStmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }
            
            // Insert new resident
            $query = "INSERT INTO residents (first_name, last_name, email, mobile_number, birth_date, civil_status, password, status, role) 
                      VALUES (:first_name, :last_name, :email, :phone, :birth_date, :civil_status, :password, 'Active', 'Resident')";
            $stmt = $db->prepare($query);
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':birth_date', $birth_date);
            $stmt->bindParam(':civil_status', $civil_status);
            $stmt->bindParam(':password', $hashedPassword);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Resident added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add resident']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding resident: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
            exit;
        }
        
        try {
            // Check if resident has any requests
            $checkQuery = "SELECT COUNT(*) as request_count FROM requests WHERE resident_id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $requestCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['request_count'];
            
            if($requestCount > 0) {
                // Don't delete, just deactivate
                $query = "UPDATE residents SET status = 'Deactivated' WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Resident deactivated successfully (has existing requests)']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to deactivate resident']);
                }
            } else {
                // Safe to delete
                $query = "DELETE FROM residents WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Resident deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete resident']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting resident: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>