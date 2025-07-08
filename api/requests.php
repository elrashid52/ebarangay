<?php
session_start();
header('Content-Type: application/json');

// Check if resident is logged in
if(!isset($_SESSION['resident_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/database.php';
a
$database = new Database();
$db = $database->getConnection();
$request = new Request($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$resident_id = $_SESSION['resident_id'];

switch($action) {
    case 'get_all':
        error_log("Getting all requests for resident ID: " . $resident_id);
        $stmt = $request->getResidentRequests($resident_id);
        $requests = [];
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Ensure processing_fee is properly handled
            $processing_fee = 0;
            if (isset($row['processing_fee']) && $row['processing_fee'] !== null) {
                $processing_fee = floatval($row['processing_fee']);
            } elseif (isset($row['type_fee']) && $row['type_fee'] !== null) {
                $processing_fee = floatval($row['type_fee']);
            }
            
            $requests[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'purpose' => $row['purpose'],
                'status' => $row['status'],
                'request_details' => json_decode($row['request_details'], true),
                'admin_notes' => $row['admin_notes'],
                'processing_fee' => $processing_fee,
                'document_path' => $row['document_path'],
                'can_download' => intval($row['can_download'] ?? 0),
                'can_reupload' => intval($row['can_reupload'] ?? 0),
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'processed_at' => $row['processed_at']
            ];
        }
        
        error_log("Found " . count($requests) . " requests for resident");
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;
        
    case 'create':
        error_log("Creating new request for resident ID: " . $resident_id);
        $type = $_POST['type'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $details = $_POST['details'] ?? '{}';
        
        if(empty($type) || empty($purpose)) {
            error_log("Missing required fields: type=$type, purpose=$purpose");
            echo json_encode(['success' => false, 'message' => 'Type and purpose are required']);
            exit;
        }
        
        // Get processing fee from request types
        $feeQuery = "SELECT processing_fee FROM request_types WHERE name = :type";
        $feeStmt = $db->prepare($feeQuery);
        $feeStmt->bindParam(':type', $type);
        $feeStmt->execute();
        $feeResult = $feeStmt->fetch(PDO::FETCH_ASSOC);
        $processing_fee = $feeResult ? floatval($feeResult['processing_fee']) : 0;
        
        $request->type = $type;
        $request->purpose = $purpose;
        $request->resident_id = $resident_id;
        $request->request_details = $details;
        $request->processing_fee = $processing_fee;
        
        $request_id = $request->create();
        
        if($request_id) {
            error_log("Request created successfully with ID: " . $request_id);
            echo json_encode(['success' => true, 'message' => 'Request submitted successfully', 'request_id' => $request_id]);
        } else {
            error_log("Failed to create request");
            echo json_encode(['success' => false, 'message' => 'Failed to submit request']);
        }
        break;
        
    case 'create_certificate_request':
        // Add debug logging
        error_log("Certificate request received: " . print_r($_POST, true));
        error_log("Files received: " . print_r($_FILES, true));
        error_log("Resident ID: " . $resident_id);
        
        $certificate_type = $_POST['certificate_type'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $additional_notes = $_POST['additional_notes'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        $payment_reference = $_POST['payment_reference'] ?? '';
        
        if(empty($certificate_type) || empty($purpose)) {
            error_log("Missing required fields for certificate request");
            echo json_encode(['success' => false, 'message' => 'Certificate type and purpose are required']);
            exit;
        }
        
        // Handle document uploads
        $uploadedDocuments = [];
        $uploadDir = '../uploads/requests/';
        
        // Create directory if it doesn't exist
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Process uploaded files
        foreach($_FILES as $fieldName => $file) {
            if($file['error'] === UPLOAD_ERR_OK) {
                // Validate file
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                if(!in_array($file['type'], $allowedTypes)) {
                    error_log("Invalid file type: " . $file['type']);
                    echo json_encode(['success' => false, 'message' => 'Only PDF, JPG, and PNG files are allowed']);
                    exit;
                }
                
                if($file['size'] > 5 * 1024 * 1024) {
                    error_log("File too large: " . $file['size']);
                    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
                    exit;
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = $fieldName . '_' . $resident_id . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if(move_uploaded_file($file['tmp_name'], $filepath)) {
                    $uploadedDocuments[$fieldName] = $filename;
                    error_log("File uploaded successfully: " . $filename);
                }
            }
        }
        
        $requestDetails = [
            'payment_method' => $payment_method,
            'payment_reference' => $payment_reference,
            'additional_notes' => $additional_notes,
            'uploaded_documents' => $uploadedDocuments
        ];
        
        // Set processing fee based on certificate type
        $processing_fee = 0;
        switch($certificate_type) {
            case 'Barangay Clearance':
                $processing_fee = 50.00;
                break;
            case 'Certificate of Residency':
                $processing_fee = 30.00;
                break;
            case 'Certificate of Indigency':
                $processing_fee = 0.00;
                break;
            case 'Barangay Business Clearance':
                $processing_fee = 200.00;
                break;
            case 'Barangay ID':
                $processing_fee = 100.00;
                break;
            default:
                $processing_fee = 0.00;
        }
        
        $request->type = $certificate_type;
        $request->purpose = $purpose;
        $request->resident_id = $resident_id;
        $request->request_details = json_encode($requestDetails);
        $request->processing_fee = $processing_fee;
        
        $request_id = $request->create();
        
        if($request_id) {
            error_log("Certificate request created successfully with ID: " . $request_id);
            echo json_encode(['success' => true, 'message' => 'Certificate request submitted successfully', 'request_id' => $request_id]);
        } else {
            error_log("Failed to create certificate request");
            echo json_encode(['success' => false, 'message' => 'Failed to submit certificate request']);
        }
        break;
        
    case 'download_document':
        error_log("Download document request for resident ID: " . $resident_id);
        $request_id = $_GET['request_id'] ?? '';
        
        if(empty($request_id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        // Verify the request belongs to the current resident and is approved
        $query = "SELECT document_path, type, status, can_download FROM requests 
                  WHERE id = :request_id AND resident_id = :resident_id AND status = 'approved'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->execute();
        
        $requestData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$requestData) {
            error_log("Request not found or not approved for download");
            echo json_encode(['success' => false, 'message' => 'Request not found or not approved']);
            exit;
        }
        
        if(!$requestData['can_download']) {
            error_log("Document not available for download");
            echo json_encode(['success' => false, 'message' => 'This document is not available for download']);
            exit;
        }
        
        $filePath = '../uploads/certificates/' . $requestData['document_path'];
        
        if(!file_exists($filePath)) {
            error_log("Document file not found: " . $filePath);
            echo json_encode(['success' => false, 'message' => 'Document file not found']);
            exit;
        }
        
        // Set headers for file download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $requestData['type'] . '_' . date('Y-m-d') . '.pdf"');
        header('Content-Length: ' . filesize($filePath));
        
        // Output file
        readfile($filePath);
        exit;
        break;
        
    case 'view_document':
        error_log("View document request for resident ID: " . $resident_id);
        $request_id = $_GET['request_id'] ?? '';
        $document_type = $_GET['document_type'] ?? '';
        
        if(empty($request_id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        try {
            // Verify the request exists and get details
            $query = "SELECT request_details, resident_id FROM requests WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $request_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $details = json_decode($row['request_details'], true);
                
                if(isset($details['uploaded_documents'][$document_type])) {
                    $filename = $details['uploaded_documents'][$document_type];
                    $filepath = '../uploads/requests/' . $filename;
                    
                    if(file_exists($filepath)) {
                        // Determine content type
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $contentType = finfo_file($finfo, $filepath);
                        finfo_close($finfo);
                        
                        header('Content-Type: ' . $contentType);
                        header('Content-Disposition: inline; filename="' . $filename . '"');
                        readfile($filepath);
                        exit;
                    } else {
                        error_log("File not found on server: " . $filepath);
                        echo json_encode(['success' => false, 'message' => 'File not found on server']);
                        exit;
                    }
                } else {
                    error_log("Document type not found in request: " . $document_type);
                    echo json_encode(['success' => false, 'message' => 'Document type not found in request']);
                    exit;
                }
            } else {
                error_log("Request not found: " . $request_id);
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                exit;
            }
        } catch (Exception $e) {
            error_log("Error viewing document: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error viewing document: ' . $e->getMessage()]);
        }
        break;
        
    case 'reupload_request':
        error_log("Reupload request for resident ID: " . $resident_id);
        $request_id = $_POST['request_id'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $additional_notes = $_POST['additional_notes'] ?? '';
        
        if(empty($request_id) || empty($purpose)) {
            error_log("Missing required fields for reupload");
            echo json_encode(['success' => false, 'message' => 'Request ID and purpose are required']);
            exit;
        }
        
        // Verify the request belongs to the current resident and can be reuploaded
        $query = "SELECT can_reupload FROM requests 
                  WHERE id = :request_id AND resident_id = :resident_id AND status = 'rejected'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->execute();
        
        $requestData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$requestData || !$requestData['can_reupload']) {
            error_log("Request cannot be reuploaded");
            echo json_encode(['success' => false, 'message' => 'Request cannot be reuploaded']);
            exit;
        }
        
        // Handle document uploads for resubmission
        $uploadedDocuments = [];
        $uploadDir = '../uploads/requests/';
        
        // Create directory if it doesn't exist
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Process uploaded files
        foreach($_FILES as $fieldName => $file) {
            if($file['error'] === UPLOAD_ERR_OK) {
                // Validate file
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                if(!in_array($file['type'], $allowedTypes)) {
                    error_log("Invalid file type for reupload: " . $file['type']);
                    echo json_encode(['success' => false, 'message' => 'Only PDF, JPG, and PNG files are allowed']);
                    exit;
                }
                
                if($file['size'] > 5 * 1024 * 1024) {
                    error_log("File too large for reupload: " . $file['size']);
                    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
                    exit;
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = $fieldName . '_' . $resident_id . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if(move_uploaded_file($file['tmp_name'], $filepath)) {
                    $uploadedDocuments[$fieldName] = $filename;
                    error_log("File reuploaded successfully: " . $filename);
                }
            }
        }
        
        // Update request details with new documents
        $requestDetails = [
            'uploaded_documents' => $uploadedDocuments,
            'additional_notes' => $additional_notes,
            'resubmission_date' => date('Y-m-d H:i:s')
        ];
        
        // Update the request
        $updateQuery = "UPDATE requests SET purpose = :purpose, status = 'pending', 
                        admin_notes = NULL, updated_at = CURRENT_TIMESTAMP,
                        request_details = :request_details, can_reupload = 0
                        WHERE id = :request_id AND resident_id = :resident_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':purpose', $purpose);
        $updateStmt->bindParam(':request_details', json_encode($requestDetails));
        $updateStmt->bindParam(':request_id', $request_id);
        $updateStmt->bindParam(':resident_id', $resident_id);
        
        if($updateStmt->execute()) {
            error_log("Request resubmitted successfully");
            echo json_encode(['success' => true, 'message' => 'Request resubmitted successfully with new documents']);
        } else {
            error_log("Failed to resubmit request");
            echo json_encode(['success' => false, 'message' => 'Failed to resubmit request']);
        }
        break;
        
    case 'get_stats':
        error_log("Getting stats for resident ID: " . $resident_id);
        $stats = $request->getStats($resident_id);
        $blotter_count = $request->getBlotterCount($resident_id);
        $stats['blotter_reports'] = $blotter_count;
        error_log("Stats retrieved: " . print_r($stats, true));
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    case 'get_types':
        error_log("Getting request types");
        // Return updated certificate types with new requirements
        $types = [
            [
                'id' => 1,
                'name' => 'Barangay Clearance',
                'description' => 'Certificate of good moral character and residence',
                'required_documents' => [
                    'Valid Government-issued ID (with address)',
                    'Proof of Billing / Proof of Residency (if not on ID)'
                ],
                'processing_fee' => 50.00,
                'processing_days' => 3
            ],
            [
                'id' => 2,
                'name' => 'Certificate of Residency',
                'description' => 'Proof of residence in the barangay',
                'required_documents' => [
                    'Valid Government-issued ID with address',
                    'Proof of Residency (e.g., utility bill, lease contract)'
                ],
                'processing_fee' => 30.00,
                'processing_days' => 1
            ],
            [
                'id' => 3,
                'name' => 'Certificate of Indigency',
                'description' => 'Certificate for low-income residents',
                'required_documents' => [
                    'Valid ID',
                    'No income or proof of unemployment'
                ],
                'processing_fee' => 0.00,
                'processing_days' => 2
            ],
            [
                'id' => 4,
                'name' => 'Barangay Business Clearance',
                'description' => 'Permit to operate a business in the barangay',
                'required_documents' => [
                    'Business Name Registration (from DTI or SEC)',
                    'Business Permit Application Form',
                    'Valid ID of the business owner',
                    'Location sketch of business'
                ],
                'processing_fee' => 200.00,
                'processing_days' => 7
            ],
            [
                'id' => 5,
                'name' => 'Barangay ID',
                'description' => 'Official barangay identification card',
                'required_documents' => [
                    'Proof of Residency (utility bill, lease, or certification)',
                    'Passport-sized photo',
                    'Valid IDs'
                ],
                'processing_fee' => 100.00,
                'processing_days' => 7
            ]
        ];
        
        error_log("Returning " . count($types) . " request types");
        echo json_encode(['success' => true, 'types' => $types]);
        break;
        
    default:
        error_log("Invalid action: " . $action);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>