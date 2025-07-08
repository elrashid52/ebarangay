<?php
// Debug file to check user portal functionality
// Access this file directly: http://localhost/your-project/debug-user-portal.php

echo "<h1>User Portal Debug Information</h1>";

// Start session to check current state
session_start();

echo "<h3>Session Information:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if database connection works
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check residents table
    try {
        $query = "SELECT COUNT(*) as count FROM residents";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Residents table accessible - {$result['count']} residents found</p>";
        
        // Show sample residents
        $query = "SELECT id, email, first_name, last_name, role FROM residents LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Sample Residents:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th></tr>";
        foreach($residents as $resident) {
            echo "<tr>";
            echo "<td>{$resident['id']}</td>";
            echo "<td>{$resident['email']}</td>";
            echo "<td>{$resident['first_name']} {$resident['last_name']}</td>";
            echo "<td>{$resident['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error accessing residents table: " . $e->getMessage() . "</p>";
    }
    
    // Check requests table
    try {
        $query = "SELECT COUNT(*) as count FROM requests";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Requests table accessible - {$result['count']} requests found</p>";
        
        // Show sample requests
        $query = "SELECT r.id, r.type, r.status, r.created_at, res.first_name, res.last_name 
                  FROM requests r 
                  JOIN residents res ON r.resident_id = res.id 
                  ORDER BY r.created_at DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Sample Requests:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Type</th><th>Resident</th><th>Status</th><th>Date</th></tr>";
        foreach($requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>{$request['type']}</td>";
            echo "<td>{$request['first_name']} {$request['last_name']}</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error accessing requests table: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test API endpoints
echo "<h3>API Endpoint Tests:</h3>";

// Test auth API
echo "<h4>Testing auth.php:</h4>";
$testData = [
    'action' => 'check_session'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($testData)
    ]
]);

try {
    $result = file_get_contents('api/auth.php', false, $context);
    echo "<p style='color: green;'>✅ auth.php accessible</p>";
    echo "<pre>Response: " . htmlspecialchars($result) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ auth.php error: " . $e->getMessage() . "</p>";
}

// Test requests API
echo "<h4>Testing requests.php:</h4>";
try {
    $result = file_get_contents('api/requests.php?action=get_types');
    echo "<p style='color: green;'>✅ requests.php accessible</p>";
    echo "<pre>Response: " . htmlspecialchars($result) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ requests.php error: " . $e->getMessage() . "</p>";
}

// Test profile API
echo "<h4>Testing profile.php:</h4>";
try {
    $result = file_get_contents('api/profile.php?action=debug_table_structure');
    echo "<p style='color: green;'>✅ profile.php accessible</p>";
    echo "<pre>Response: " . htmlspecialchars($result) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ profile.php error: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Login:</h3>";
echo "<form method='POST' style='margin: 20px 0;'>";
echo "<input type='text' name='test_email' placeholder='Email' value='john.doe@email.com' style='margin: 5px; padding: 10px;'><br>";
echo "<input type='text' name='test_password' placeholder='Password' value='resident123' style='margin: 5px; padding: 10px;'><br>";
echo "<button type='submit' name='test_login' style='margin: 5px; padding: 10px; background: #667eea; color: white; border: none; border-radius: 5px;'>Test Login</button>";
echo "</form>";

if(isset($_POST['test_login'])) {
    $email = $_POST['test_email'];
    $password = $_POST['test_password'];
    
    echo "<h4>Testing login for: $email</h4>";
    
    // Simulate the login process
    try {
        require_once 'config/database.php';
        require_once 'classes/Resident.php';
        
        $database = new Database();
        $db = $database->getConnection();
        $resident = new Resident($db);
        
        $resident->email = $email;
        $resident->password = $password;
        
        if($resident->login()) {
            echo "<p style='color: green;'>✅ Login successful!</p>";
            echo "<p>Resident ID: {$resident->id}</p>";
            echo "<p>Name: {$resident->first_name} {$resident->last_name}</p>";
        } else {
            echo "<p style='color: red;'>❌ Login failed</p>";
            
            // Check if user exists
            $query = "SELECT id, email, first_name, last_name FROM residents WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p style='color: orange;'>⚠️ User exists but password is incorrect</p>";
                echo "<p>Found user: {$user['first_name']} {$user['last_name']}</p>";
            } else {
                echo "<p style='color: red;'>❌ User not found</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test failed: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Recommendations:</h3>";
echo "<ol>";
echo "<li>Try logging in with: <strong>john.doe@email.com</strong> / <strong>resident123</strong></li>";
echo "<li>Check the browser console for JavaScript errors</li>";
echo "<li>Make sure you're accessing the resident portal at: <strong>index.php</strong></li>";
echo "<li>Clear your browser cache and cookies</li>";
echo "<li>Check if the API files exist and are accessible</li>";
echo "</ol>";

echo "<h3>Quick Links:</h3>";
echo "<p><a href='index.php' style='color: #667eea;'>Go to Resident Portal</a></p>";
echo "<p><a href='admin.php' style='color: #667eea;'>Go to Admin Portal</a></p>";
echo "<p><a href='index.php?debug=1' style='color: #667eea;'>Resident Portal with Debug</a></p>";
?>