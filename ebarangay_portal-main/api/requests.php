<?php
session_start();
header('Content-Type: application/json');

// Check if resident is logged in
if(!isset($_SESSION['resident_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/database.php';
require_once '../classes/Request.php';

$database = new Database();
$db = $database->getConnection();
$request = new Request($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$resident_id = $_SESSION['resident_id'];

switch($action) {
    case 'get_all':
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
        
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;
        
    case 'create':
        $type = $_POST['type'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $details = $_POST['details'] ?? '{}';
        
        if(empty($type) || empty($purpose)) {
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
            echo json_encode(['success' => true, 'message' => 'Request submitted successfully', 'request_id' => $request_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit request']);
        }
        break;
        
    case 'create_certificate_request':
        // Add debug logging
        error_log("Certificate request received: " . print_r($_POST, true));
        
        $certificate_type = $_POST['certificate_type'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $additional_notes = $_POST['additional_notes'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        $payment_reference = $_POST['payment_reference'] ?? '';
        
        if(empty($certificate_type) || empty($purpose)) {
            echo json_encode(['success' => false, 'message' => 'Certificate type and purpose are required']);
            exit;
        }
        
        // For now, let's use a simple approach without complex file uploads
        // We'll store the request details as JSON
        $requestDetails = [
            'payment_method' => $payment_method,
            'payment_reference' => $payment_reference,
            'additional_notes' => $additional_notes,
            'uploaded_documents' => [] // We'll handle file uploads later
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
            echo json_encode(['success' => true, 'message' => 'Certificate request submitted successfully', 'request_id' => $request_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit certificate request']);
        }
        break;
        
    case 'download_document':
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
            echo json_encode(['success' => false, 'message' => 'Request not found or not approved']);
            exit;
        }
        
        if(!$requestData['can_download']) {
            echo json_encode(['success' => false, 'message' => 'This document is not available for download']);
            exit;
        }
        
        $filePath = '../uploads/certificates/' . $requestData['document_path'];
        
        if(!file_exists($filePath)) {
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
        $request_id = $_GET['request_id'] ?? '';
        
        if(empty($request_id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit;
        }
        
        // Verify the request belongs to the current resident and is ready for viewing
        $query = "SELECT document_path, type, status FROM requests 
                  WHERE id = :request_id AND resident_id = :resident_id AND status IN ('approved', 'ready_for_pickup')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->execute();
        
        $requestData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$requestData) {
            echo json_encode(['success' => false, 'message' => 'Request not found or not ready']);
            exit;
        }
        
        // For Barangay ID, return image for viewing
        if(stripos($requestData['type'], 'barangay id') !== false) {
            $imagePath = '../uploads/barangay_ids/' . $requestData['document_path'];
            
            if(!file_exists($imagePath)) {
                // Return error if file doesn't exist
                echo json_encode(['success' => false, 'message' => 'Barangay ID image not found']);
                exit;
            }
            
            // Set headers for image viewing
            $imageInfo = getimagesize($imagePath);
            header('Content-Type: ' . $imageInfo['mime']);
            header('Content-Disposition: inline; filename="barangay_id_' . $request_id . '.jpg"');
            
            // Output image
            readfile($imagePath);
            exit;
        } else {
            // For other documents, handle as PDF
            $filePath = '../uploads/certificates/' . $requestData['document_path'];
            
            if(!file_exists($filePath)) {
                echo json_encode(['success' => false, 'message' => 'Document file not found']);
                exit;
            }
            
            // Set headers for file viewing
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $requestData['type'] . '_' . date('Y-m-d') . '.pdf"');
            
            // Output file
            readfile($filePath);
            exit;
        }
        break;
        
    case 'reupload_request':
        $request_id = $_POST['request_id'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        
        if(empty($request_id) || empty($purpose)) {
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
            echo json_encode(['success' => false, 'message' => 'Request cannot be reuploaded']);
            exit;
        }
        
        // Update the request
        $updateQuery = "UPDATE requests SET purpose = :purpose, status = 'pending', 
                        admin_notes = NULL, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = :request_id AND resident_id = :resident_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':purpose', $purpose);
        $updateStmt->bindParam(':request_id', $request_id);
        $updateStmt->bindParam(':resident_id', $resident_id);
        
        if($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request resubmitted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to resubmit request']);
        }
        break;
        
    case 'get_stats':
        $stats = $request->getStats($resident_id);
        $blotter_count = $request->getBlotterCount($resident_id);
        $stats['blotter_reports'] = $blotter_count;
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    case 'get_types':
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
        
        echo json_encode(['success' => true, 'types' => $types]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>