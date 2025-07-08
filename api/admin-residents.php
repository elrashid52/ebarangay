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
            $statusFilter = $_GET['status_filter'] ?? '';
            $roleFilter = $_GET['role_filter'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            // Build WHERE conditions
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR mobile_number LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            if (!empty($statusFilter)) {
                $whereConditions[] = "status = :status";
                $params[':status'] = $statusFilter;
            }
            
            if (!empty($roleFilter)) {
                $whereConditions[] = "role = :role";
                $params[':role'] = $roleFilter;
            }
            
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM residents $whereClause";
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get residents with pagination
            $query = "SELECT id, first_name, last_name, middle_name, email, mobile_number, 
                             civil_status, house_no, street, purok, barangay, city, 
                             status, role, voter_status, employment_status, created_at,
                             birth_date, age, valid_id_type, emergency_contact_name
                      FROM residents 
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
                    'role' => $row['role'] ?? 'Resident',
                    'voter_status' => $row['voter_status'],
                    'employment_status' => $row['employment_status'],
                    'created_at' => $row['created_at'],
                    'birth_date' => $row['birth_date'],
                    'age' => $row['age'],
                    'valid_id_type' => $row['valid_id_type'],
                    'emergency_contact_name' => $row['emergency_contact_name']
                ];
            }
            
            echo json_encode([
                'success' => true, 
                'residents' => $residents,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching residents: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_by_id':
        $id = $_GET['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
            exit;
        }
        
        try {
            $query = "SELECT * FROM residents WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $resident = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'resident' => $resident]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Resident not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching resident: ' . $e->getMessage()]);
        }
        break;
        
    case 'add':
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $password = $_POST['password'] ?? 'resident123';
        $birth_date = $_POST['birth_date'] ?? null;
        $sex = $_POST['sex'] ?? 'Male';
        $civil_status = $_POST['civil_status'] ?? 'Single';
        $citizenship = trim($_POST['citizenship'] ?? 'Filipino');
        $house_no = trim($_POST['house_no'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $purok = trim($_POST['purok'] ?? '');
        $barangay = trim($_POST['barangay'] ?? 'Sample Barangay');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $voter_status = $_POST['voter_status'] ?? 'Not Registered';
        $voter_id = trim($_POST['voter_id'] ?? '');
        $valid_id_type = trim($_POST['valid_id_type'] ?? '');
        $valid_id_number = trim($_POST['valid_id_number'] ?? '');
        $employment_status = $_POST['employment_status'] ?? 'Unemployed';
        $occupation = trim($_POST['occupation'] ?? '');
        $monthly_income_range = trim($_POST['monthly_income_range'] ?? '');
        $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
        $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');
        $role = $_POST['role'] ?? 'Resident';
        $status = $_POST['status'] ?? 'Active';
        
        if(empty($first_name) || empty($last_name) || empty($email) || empty($mobile_number)) {
            echo json_encode(['success' => false, 'message' => 'First name, last name, email, and mobile number are required']);
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
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
            
            // Calculate age from birth_date
            $age = null;
            if ($birth_date) {
                $birthDate = new DateTime($birth_date);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;
            }
            
            // Clear voter_id if not registered
            if ($voter_status !== 'Registered') {
                $voter_id = '';
            }
            
            // Insert new resident
            $query = "INSERT INTO residents (
                        first_name, last_name, middle_name, email, mobile_number, password, 
                        birth_date, age, sex, civil_status, citizenship, house_no, street, 
                        purok, barangay, city, province, zip_code, voter_status, voter_id,
                        valid_id_type, valid_id_number, employment_status, occupation, 
                        monthly_income_range, emergency_contact_name, emergency_contact_number,
                        role, status
                      ) VALUES (
                        :first_name, :last_name, :middle_name, :email, :mobile_number, :password,
                        :birth_date, :age, :sex, :civil_status, :citizenship, :house_no, :street,
                        :purok, :barangay, :city, :province, :zip_code, :voter_status, :voter_id,
                        :valid_id_type, :valid_id_number, :employment_status, :occupation,
                        :monthly_income_range, :emergency_contact_name, :emergency_contact_number,
                        :role, :status
                      )";
            
            $stmt = $db->prepare($query);
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':middle_name', $middle_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mobile_number', $mobile_number);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':birth_date', $birth_date);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':sex', $sex);
            $stmt->bindParam(':civil_status', $civil_status);
            $stmt->bindParam(':citizenship', $citizenship);
            $stmt->bindParam(':house_no', $house_no);
            $stmt->bindParam(':street', $street);
            $stmt->bindParam(':purok', $purok);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':zip_code', $zip_code);
            $stmt->bindParam(':voter_status', $voter_status);
            $stmt->bindParam(':voter_id', $voter_id);
            $stmt->bindParam(':valid_id_type', $valid_id_type);
            $stmt->bindParam(':valid_id_number', $valid_id_number);
            $stmt->bindParam(':employment_status', $employment_status);
            $stmt->bindParam(':occupation', $occupation);
            $stmt->bindParam(':monthly_income_range', $monthly_income_range);
            $stmt->bindParam(':emergency_contact_name', $emergency_contact_name);
            $stmt->bindParam(':emergency_contact_number', $emergency_contact_number);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            
            if($stmt->execute()) {
                $newResidentId = $db->lastInsertId();
                
                // Log activity
                logAdminActivity($_SESSION['admin_id'], 'create_resident', 'resident', $newResidentId, "Created new resident: $first_name $last_name");
                
                echo json_encode(['success' => true, 'message' => 'Resident added successfully', 'resident_id' => $newResidentId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add resident']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding resident: ' . $e->getMessage()]);
        }
        break;
        
    case 'update':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
            exit;
        }
        
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $birth_date = $_POST['birth_date'] ?? null;
        $sex = $_POST['sex'] ?? 'Male';
        $civil_status = $_POST['civil_status'] ?? 'Single';
        $citizenship = trim($_POST['citizenship'] ?? 'Filipino');
        $house_no = trim($_POST['house_no'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $purok = trim($_POST['purok'] ?? '');
        $barangay = trim($_POST['barangay'] ?? 'Sample Barangay');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $voter_status = $_POST['voter_status'] ?? 'Not Registered';
        $voter_id = trim($_POST['voter_id'] ?? '');
        $valid_id_type = trim($_POST['valid_id_type'] ?? '');
        $valid_id_number = trim($_POST['valid_id_number'] ?? '');
        $employment_status = $_POST['employment_status'] ?? 'Unemployed';
        $occupation = trim($_POST['occupation'] ?? '');
        $monthly_income_range = trim($_POST['monthly_income_range'] ?? '');
        $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
        $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');
        $role = $_POST['role'] ?? 'Resident';
        $status = $_POST['status'] ?? 'Active';
        
        if(empty($first_name) || empty($last_name) || empty($email) || empty($mobile_number)) {
            echo json_encode(['success' => false, 'message' => 'First name, last name, email, and mobile number are required']);
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        try {
            // Check if email already exists for other residents
            $checkQuery = "SELECT id FROM residents WHERE email = :email AND id != :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if($checkStmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists for another resident']);
                exit;
            }
            
            // Calculate age from birth_date
            $age = null;
            if ($birth_date) {
                $birthDate = new DateTime($birth_date);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;
            }
            
            // Clear voter_id if not registered
            if ($voter_status !== 'Registered') {
                $voter_id = '';
            }
            
            // Update resident
            $query = "UPDATE residents SET 
                        first_name = :first_name, last_name = :last_name, middle_name = :middle_name,
                        email = :email, mobile_number = :mobile_number, birth_date = :birth_date,
                        age = :age, sex = :sex, civil_status = :civil_status, citizenship = :citizenship,
                        house_no = :house_no, street = :street, purok = :purok, barangay = :barangay,
                        city = :city, province = :province, zip_code = :zip_code, voter_status = :voter_status,
                        voter_id = :voter_id, valid_id_type = :valid_id_type, valid_id_number = :valid_id_number,
                        employment_status = :employment_status, occupation = :occupation,
                        monthly_income_range = :monthly_income_range, emergency_contact_name = :emergency_contact_name,
                        emergency_contact_number = :emergency_contact_number, role = :role, status = :status,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':middle_name', $middle_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mobile_number', $mobile_number);
            $stmt->bindParam(':birth_date', $birth_date);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':sex', $sex);
            $stmt->bindParam(':civil_status', $civil_status);
            $stmt->bindParam(':citizenship', $citizenship);
            $stmt->bindParam(':house_no', $house_no);
            $stmt->bindParam(':street', $street);
            $stmt->bindParam(':purok', $purok);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':zip_code', $zip_code);
            $stmt->bindParam(':voter_status', $voter_status);
            $stmt->bindParam(':voter_id', $voter_id);
            $stmt->bindParam(':valid_id_type', $valid_id_type);
            $stmt->bindParam(':valid_id_number', $valid_id_number);
            $stmt->bindParam(':employment_status', $employment_status);
            $stmt->bindParam(':occupation', $occupation);
            $stmt->bindParam(':monthly_income_range', $monthly_income_range);
            $stmt->bindParam(':emergency_contact_name', $emergency_contact_name);
            $stmt->bindParam(':emergency_contact_number', $emergency_contact_number);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log activity
                logAdminActivity($_SESSION['admin_id'], 'update_resident', 'resident', $id, "Updated resident: $first_name $last_name");
                
                echo json_encode(['success' => true, 'message' => 'Resident updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update resident']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating resident: ' . $e->getMessage()]);
        }
        break;
        
    case 'reset_password':
        $id = $_POST['id'] ?? '';
        $new_password = $_POST['new_password'] ?? 'resident123';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
            exit;
        }
        
        try {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE residents SET password = :password WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                // Log activity
                logAdminActivity($_SESSION['admin_id'], 'reset_password', 'resident', $id, "Reset password for resident ID: $id");
                
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
            echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
            exit;
        }
        
        try {
            // Get current status
            $query = "SELECT status, first_name, last_name FROM residents WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $resident = $stmt->fetch(PDO::FETCH_ASSOC);
                $newStatus = ($resident['status'] === 'Active') ? 'Deactivated' : 'Active';
                
                // Update status
                $updateQuery = "UPDATE residents SET status = :status WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':status', $newStatus);
                $updateStmt->bindParam(':id', $id);
                
                if($updateStmt->execute()) {
                    // Log activity
                    logAdminActivity($_SESSION['admin_id'], 'toggle_status', 'resident', $id, "Changed status to $newStatus for {$resident['first_name']} {$resident['last_name']}");
                    
                    echo json_encode(['success' => true, 'message' => "Resident status changed to $newStatus", 'new_status' => $newStatus]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Resident not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
            exit;
        }
        
        try {
            // Get resident info for logging
            $query = "SELECT first_name, last_name FROM residents WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $resident = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resident) {
                echo json_encode(['success' => false, 'message' => 'Resident not found']);
                exit;
            }
            
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
                    // Log activity
                    logAdminActivity($_SESSION['admin_id'], 'deactivate_resident', 'resident', $id, "Deactivated resident with existing requests: {$resident['first_name']} {$resident['last_name']}");
                    
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
                    // Log activity
                    logAdminActivity($_SESSION['admin_id'], 'delete_resident', 'resident', $id, "Deleted resident: {$resident['first_name']} {$resident['last_name']}");
                    
                    echo json_encode(['success' => true, 'message' => 'Resident deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete resident']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting resident: ' . $e->getMessage()]);
        }
        break;
        
    case 'bulk_action':
        $action_type = $_POST['action_type'] ?? '';
        $resident_ids = $_POST['resident_ids'] ?? [];
        
        if(empty($action_type) || empty($resident_ids)) {
            echo json_encode(['success' => false, 'message' => 'Action type and resident IDs are required']);
            exit;
        }
        
        try {
            $processed = 0;
            $errors = [];
            
            foreach($resident_ids as $id) {
                switch($action_type) {
                    case 'activate':
                        $query = "UPDATE residents SET status = 'Active' WHERE id = :id";
                        break;
                    case 'deactivate':
                        $query = "UPDATE residents SET status = 'Deactivated' WHERE id = :id";
                        break;
                    case 'delete':
                        // Check for requests first
                        $checkQuery = "SELECT COUNT(*) as request_count FROM requests WHERE resident_id = :id";
                        $checkStmt = $db->prepare($checkQuery);
                        $checkStmt->bindParam(':id', $id);
                        $checkStmt->execute();
                        $requestCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['request_count'];
                        
                        if($requestCount > 0) {
                            $query = "UPDATE residents SET status = 'Deactivated' WHERE id = :id";
                        } else {
                            $query = "DELETE FROM residents WHERE id = :id";
                        }
                        break;
                    default:
                        $errors[] = "Invalid action type for resident ID: $id";
                        continue 2;
                }
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if($stmt->execute()) {
                    $processed++;
                } else {
                    $errors[] = "Failed to process resident ID: $id";
                }
            }
            
            // Log bulk activity
            logAdminActivity($_SESSION['admin_id'], 'bulk_action', 'resident', null, "Bulk $action_type on $processed residents");
            
            $message = "Processed $processed residents successfully";
            if(!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }
            
            echo json_encode(['success' => true, 'message' => $message, 'processed' => $processed, 'errors' => $errors]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error processing bulk action: ' . $e->getMessage()]);
        }
        break;
        
    case 'export':
        try {
            $format = $_GET['format'] ?? 'csv';
            
            $query = "SELECT first_name, last_name, middle_name, email, mobile_number, 
                             birth_date, age, sex, civil_status, citizenship, house_no, street, 
                             purok, barangay, city, province, zip_code, voter_status, voter_id,
                             valid_id_type, valid_id_number, employment_status, occupation,
                             monthly_income_range, emergency_contact_name, emergency_contact_number,
                             role, status, created_at
                      FROM residents 
                      ORDER BY created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="residents_export_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // CSV headers
                $headers = array_keys($residents[0] ?? []);
                fputcsv($output, $headers);
                
                // CSV data
                foreach($residents as $resident) {
                    fputcsv($output, $resident);
                }
                
                fclose($output);
                exit;
            }
            
            echo json_encode(['success' => true, 'residents' => $residents]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error exporting residents: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_statistics':
        try {
            $stats = [];
            
            // Total residents
            $query = "SELECT COUNT(*) as total FROM residents";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active residents
            $query = "SELECT COUNT(*) as active FROM residents WHERE status = 'Active'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
            
            // New residents this month
            $query = "SELECT COUNT(*) as new_this_month FROM residents WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['new_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_this_month'];
            
            // Voters
            $query = "SELECT COUNT(*) as voters FROM residents WHERE voter_status = 'Registered'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['voters'] = $stmt->fetch(PDO::FETCH_ASSOC)['voters'];
            
            // Age distribution
            $query = "SELECT 
                        COUNT(CASE WHEN age BETWEEN 18 AND 30 THEN 1 END) as age_18_30,
                        COUNT(CASE WHEN age BETWEEN 31 AND 50 THEN 1 END) as age_31_50,
                        COUNT(CASE WHEN age BETWEEN 51 AND 70 THEN 1 END) as age_51_70,
                        COUNT(CASE WHEN age > 70 THEN 1 END) as age_over_70
                      FROM residents WHERE age IS NOT NULL";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $ageStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['age_distribution'] = $ageStats;
            
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