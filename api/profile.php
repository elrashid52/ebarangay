<?php
session_start();
header('Content-Type: application/json');

// Check if resident is logged in
if(!isset($_SESSION['resident_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/database.php';
require_once '../classes/Resident.php';

$database = new Database();
$db = $database->getConnection();
$resident = new Resident($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$resident_id = $_SESSION['resident_id'];

switch($action) {
    case 'get_profile':
        error_log("Getting profile for resident ID: " . $resident_id);
        $profile = $resident->getProfile($resident_id);
        if($profile) {
            // Calculate age if birth_date exists
            if($profile['birth_date']) {
                $birthDate = new DateTime($profile['birth_date']);
                $today = new DateTime();
                $profile['calculated_age'] = $today->diff($birthDate)->y;
            }
            error_log("Profile retrieved successfully");
            echo json_encode(['success' => true, 'profile' => $profile]);
        } else {
            error_log("Profile not found for resident ID: " . $resident_id);
            echo json_encode(['success' => false, 'message' => 'Profile not found']);
        }
        break;
        
    case 'update_profile':
        error_log("Updating profile for resident ID: " . $resident_id);
        // Validate required fields
        if(empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['mobile_number'])) {
            error_log("Missing required fields for profile update");
            echo json_encode(['success' => false, 'message' => 'First name, last name, and mobile number are required']);
            exit;
        }
        
        // Get all form data with proper validation and trimming
        $profileData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'middle_name' => trim($_POST['middle_name'] ?? ''),
            'sex' => trim($_POST['sex'] ?? 'Male'),
            'birth_date' => !empty($_POST['birth_date']) ? trim($_POST['birth_date']) : null,
            'civil_status' => trim($_POST['civil_status'] ?? 'Single'),
            'citizenship' => trim($_POST['citizenship'] ?? 'Filipino'),
            'house_no' => trim($_POST['house_no'] ?? ''),
            'lot' => trim($_POST['lot'] ?? ''),
            'street' => trim($_POST['street'] ?? ''),
            'purok' => trim($_POST['purok'] ?? ''),
            'barangay' => trim($_POST['barangay'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'zip_code' => trim($_POST['zip_code'] ?? ''),
            'years_of_residency' => !empty($_POST['years_of_residency']) ? (int)$_POST['years_of_residency'] : null,
            'mobile_number' => trim($_POST['mobile_number'] ?? ''),
            'landline_number' => trim($_POST['landline_number'] ?? ''),
            'voter_status' => trim($_POST['voter_status'] ?? 'Not Registered'),
            'voter_id' => trim($_POST['voter_id'] ?? ''),
            'valid_id_type' => trim($_POST['valid_id_type'] ?? ''),
            'valid_id_number' => trim($_POST['valid_id_number'] ?? ''),
            'barangay_id_number' => trim($_POST['barangay_id_number'] ?? ''),
            'cedula_number' => trim($_POST['cedula_number'] ?? ''),
            'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_relationship' => trim($_POST['emergency_contact_relationship'] ?? ''),
            'emergency_contact_number' => trim($_POST['emergency_contact_number'] ?? ''),
            'emergency_contact_address' => trim($_POST['emergency_contact_address'] ?? ''),
            'employment_status' => trim($_POST['employment_status'] ?? 'Unemployed'),
            'occupation' => trim($_POST['occupation'] ?? ''),
            'place_of_work' => trim($_POST['place_of_work'] ?? ''),
            'monthly_income_range' => trim($_POST['monthly_income_range'] ?? '')
        ];
        
        // Calculate age from birth_date
        if($profileData['birth_date']) {
            try {
                $birthDate = new DateTime($profileData['birth_date']);
                $today = new DateTime();
                $profileData['age'] = $today->diff($birthDate)->y;
            } catch (Exception $e) {
                $profileData['age'] = null;
            }
        } else {
            $profileData['age'] = null;
        }
        
        // Clear voter_id if not registered
        if($profileData['voter_status'] !== 'Registered') {
            $profileData['voter_id'] = '';
        }
        
        // Special handling for Driver's License - normalize the apostrophe
        if($profileData['valid_id_type'] === "Driver's License" || $profileData['valid_id_type'] === "Driver's License") {
            $profileData['valid_id_type'] = "Driver's License"; // Use standard apostrophe
        }
        
        // Debug logging - remove in production
        error_log("Profile update attempt for resident ID: " . $resident_id);
        error_log("Valid ID Type received: '" . $_POST['valid_id_type'] . "'");
        error_log("Valid ID Type being saved: '" . $profileData['valid_id_type'] . "'");
        error_log("Valid ID Type length: " . strlen($profileData['valid_id_type']));
        error_log("Valid ID Type hex: " . bin2hex($profileData['valid_id_type']));
        
        try {
            if($resident->updateProfile($resident_id, $profileData)) {
                error_log("Profile updated successfully");
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                error_log("Failed to update profile - database error");
                echo json_encode(['success' => false, 'message' => 'Failed to update profile - database error']);
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'upload_profile_picture':
        error_log("Uploading profile picture for resident ID: " . $resident_id);
        if(!isset($_FILES['profile_picture'])) {
            error_log("No file uploaded");
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit;
        }
        
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if(!in_array($file['type'], $allowedTypes)) {
            error_log("Invalid file type: " . $file['type']);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF allowed.']);
            exit;
        }
        
        if($file['size'] > $maxSize) {
            error_log("File too large: " . $file['size']);
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
            exit;
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = '../uploads/profile_pictures/';
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $resident_id . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if(move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database with new profile picture path
            $relativePath = 'uploads/profile_pictures/' . $filename;
            if($resident->updateProfilePicture($resident_id, $relativePath)) {
                error_log("Profile picture updated successfully");
                echo json_encode(['success' => true, 'message' => 'Profile picture updated', 'path' => $relativePath]);
            } else {
                error_log("Failed to update database with profile picture");
                echo json_encode(['success' => false, 'message' => 'Failed to update database']);
            }
        } else {
            error_log("Failed to upload file");
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
        break;
        
    case 'change_password':
        error_log("Changing password for resident ID: " . $resident_id);
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if(empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            error_log("Missing password fields");
            echo json_encode(['success' => false, 'message' => 'All password fields are required']);
            exit;
        }
        
        if($newPassword !== $confirmPassword) {
            error_log("New passwords do not match");
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }
        
        if(strlen($newPassword) < 6) {
            error_log("New password too short");
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }
        
        if($resident->changePassword($resident_id, $currentPassword, $newPassword)) {
            error_log("Password changed successfully");
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            error_log("Current password is incorrect");
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        }
        break;
        
    case 'debug_table_structure':
        error_log("Debug: Checking table structure");
        // Debug endpoint to check table structure
        try {
            $query = "DESCRIBE residents";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'table_structure' => $columns]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error checking table structure: ' . $e->getMessage()]);
        }
        break;
        
    case 'debug_valid_id_types':
        error_log("Debug: Checking valid ID types");
        // Debug endpoint to check what valid ID types are in the database
        try {
            $query = "SELECT DISTINCT valid_id_type FROM residents WHERE valid_id_type IS NOT NULL AND valid_id_type != ''";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'existing_types' => $types]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error checking valid ID types: ' . $e->getMessage()]);
        }
        break;
        
    default:
        error_log("Invalid action: " . $action);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>