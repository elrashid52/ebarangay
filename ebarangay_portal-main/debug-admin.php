<?php
// Debug file to check admin login issues
// Access this file directly: http://localhost/your-project/debug-admin.php

echo "<h1>Admin Login Debug Information</h1>";

// Check if database connection works
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if admin_users table exists
    try {
        $query = "DESCRIBE admin_users";
        $stmt = $db->prepare($query);
        $stmt->execute();
        echo "<p style='color: green;'>✅ admin_users table exists</p>";
        
        // Show admin users
        $query = "SELECT id, email, password, first_name, last_name, role, status FROM admin_users";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Admin Users in Database:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Password</th><th>Name</th><th>Role</th><th>Status</th></tr>";
        foreach($admins as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['password']}</td>";
            echo "<td>{$admin['first_name']} {$admin['last_name']}</td>";
            echo "<td>{$admin['role']}</td>";
            echo "<td>{$admin['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ admin_users table doesn't exist: " . $e->getMessage() . "</p>";
        
        // Check residents table for admin role
        try {
            $query = "SELECT id, email, first_name, last_name, role FROM residents WHERE role = 'Admin'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Admin Users in Residents Table:</h3>";
            if(count($admins) > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th></tr>";
                foreach($admins as $admin) {
                    echo "<tr>";
                    echo "<td>{$admin['id']}</td>";
                    echo "<td>{$admin['email']}</td>";
                    echo "<td>{$admin['first_name']} {$admin['last_name']}</td>";
                    echo "<td>{$admin['role']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No admin users found in residents table</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error checking residents table: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test admin login directly
echo "<h3>Test Admin Login:</h3>";
echo "<form method='POST' style='margin: 20px 0;'>";
echo "<input type='text' name='test_email' placeholder='Email' value='admin@barangay.gov.ph' style='margin: 5px; padding: 10px;'><br>";
echo "<input type='text' name='test_password' placeholder='Password' value='admin123' style='margin: 5px; padding: 10px;'><br>";
echo "<button type='submit' name='test_login' style='margin: 5px; padding: 10px; background: #4f46e5; color: white; border: none; border-radius: 5px;'>Test Login</button>";
echo "</form>";

if(isset($_POST['test_login'])) {
    $email = $_POST['test_email'];
    $password = $_POST['test_password'];
    
    echo "<h4>Testing login for: $email / $password</h4>";
    
    // Simulate the login process
    try {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Try admin_users table
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
                echo "<p style='color: blue;'>Found admin in admin_users table:</p>";
                echo "<pre>" . print_r($admin, true) . "</pre>";
                
                if($admin['status'] !== 'Active') {
                    echo "<p style='color: red;'>❌ Admin account is not active</p>";
                } else {
                    // Test password
                    if($admin['password'] === $password) {
                        echo "<p style='color: green;'>✅ Password matched (direct comparison)</p>";
                    } elseif($password === 'admin123') {
                        echo "<p style='color: green;'>✅ Universal password accepted</p>";
                    } elseif(password_verify($password, $admin['password'])) {
                        echo "<p style='color: green;'>✅ Password matched (hash verification)</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Password did not match</p>";
                    }
                }
            } else {
                echo "<p style='color: orange;'>⚠️ No admin found in admin_users table</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error with admin_users table: " . $e->getMessage() . "</p>";
        }
        
        // Demo mode test
        if($email === 'admin@barangay.gov.ph' && $password === 'admin123') {
            echo "<p style='color: green;'>✅ Demo admin credentials would work</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test failed: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Recommendations:</h3>";
echo "<ol>";
echo "<li>Try using the demo credentials: <strong>admin@barangay.gov.ph</strong> / <strong>admin123</strong></li>";
echo "<li>Check the browser console for JavaScript errors</li>";
echo "<li>Make sure you're accessing the admin portal at: <strong>admin.php</strong></li>";
echo "<li>Clear your browser cache and cookies</li>";
echo "<li>Check if the admin-auth.php file exists and is accessible</li>";
echo "</ol>";

echo "<h3>Quick Links:</h3>";
echo "<p><a href='admin.php' style='color: #4f46e5;'>Go to Admin Portal</a></p>";
echo "<p><a href='index.php' style='color: #4f46e5;'>Go to Resident Portal</a></p>";
?>