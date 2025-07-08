<?php
// Debug file to check upload system
// Access this at: http://localhost/ebarangay-portal/debug-uploads.php

echo "<h1>Document Upload System Debug</h1>";

// Check if uploads directory exists
$uploadDir = 'uploads/requests/';
echo "<h3>Directory Check:</h3>";
if (is_dir($uploadDir)) {
    echo "<p style='color: green;'>✅ Upload directory exists: $uploadDir</p>";
    
    // List files in directory
    $files = scandir($uploadDir);
    echo "<h4>Files in upload directory:</h4>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $uploadDir . $file;
            $fileSize = filesize($filePath);
            echo "<li>$file (Size: " . number_format($fileSize) . " bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Upload directory does not exist: $uploadDir</p>";
    echo "<p>Creating directory...</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Directory created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create directory</p>";
    }
}

// Check database connection and recent requests
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Get recent requests with document details
    $query = "SELECT id, type, resident_id, request_details, created_at FROM requests ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Requests with Document Details:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Type</th><th>Resident ID</th><th>Document Details</th><th>Created</th></tr>";
    
    foreach ($requests as $request) {
        $details = json_decode($request['request_details'], true);
        $documents = isset($details['uploaded_documents']) ? $details['uploaded_documents'] : [];
        
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>{$request['type']}</td>";
        echo "<td>{$request['resident_id']}</td>";
        echo "<td>";
        if (!empty($documents)) {
            echo "<ul>";
            foreach ($documents as $docType => $filename) {
                $filePath = $uploadDir . $filename;
                $exists = file_exists($filePath) ? "✅" : "❌";
                echo "<li>$exists $docType: $filename</li>";
            }
            echo "</ul>";
        } else {
            echo "No documents";
        }
        echo "</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Check PHP upload settings
echo "<h3>PHP Upload Settings:</h3>";
echo "<ul>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>max_file_uploads: " . ini_get('max_file_uploads') . "</li>";
echo "<li>file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</li>";
echo "</ul>";

echo "<h3>Test Document View URL:</h3>";
echo "<p>Try this URL format for viewing documents:</p>";
echo "<code>api/admin-requests.php?action=view_document&request_id=1&document_type=valid_id</code>";
?>