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
    case 'get_all':
        try {
            $search = $_GET['search'] ?? '';
            $statusFilter = $_GET['status_filter'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            // Build WHERE conditions
            $whereConditions = ["(role = 'Resident' OR role IS NULL)"];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR mobile_number LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            if (!empty($statusFilter)) {
                $whereConditions[] = "status = :status";
                $params[':status'] = $statusFilter;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM residents $whereClause";
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get residents with pagination (read-only for staff)
            $query = "SELECT id, first_name, last_name, middle_name, email, mobile_number, 
                             civil_status, house_no, street, purok, barangay, city, 
                             status, created_at
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
                    'created_at' => $row['created_at']
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
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action or insufficient permissions']);
}
?>