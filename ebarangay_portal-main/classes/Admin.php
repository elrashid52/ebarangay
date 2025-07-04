<?php
class Admin {
    private $conn;
    private $table_name = "admin_users";

    public $id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $role;
    public $status;
    public $last_login;
    public $created_at;
    public $updated_at;
    public $created_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new admin user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET email=:email, password=:password, first_name=:first_name, 
                      last_name=:last_name, role=:role, status=:status, created_by=:created_by";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":created_by", $this->created_by);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    // Get admin by ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all admins
    public function getAll() {
        $query = "SELECT id, email, first_name, last_name, role, status, last_login, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Update admin
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET first_name=:first_name, last_name=:last_name, role=:role, status=:status
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Bind values
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Change password
    public function changePassword($currentPassword, $newPassword) {
        // First verify current password
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$row || !password_verify($currentPassword, $row['password'])) {
            return false;
        }
        
        // Update with new password
        $query = "UPDATE " . $this->table_name . " 
                  SET password=:password 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Delete admin
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Log activity
    public function logActivity($action, $target_type = null, $target_id = null, $details = null) {
        $query = "INSERT INTO admin_activity_log 
                  (admin_id, action, target_type, target_id, details, ip_address) 
                  VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip_address)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':admin_id', $this->id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':target_type', $target_type);
        $stmt->bindParam(':target_id', $target_id);
        $stmt->bindParam(':details', $details);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');

        return $stmt->execute();
    }

    // Get activity log
    public function getActivityLog($limit = 50) {
        $query = "SELECT al.*, au.first_name, au.last_name 
                  FROM admin_activity_log al
                  JOIN admin_users au ON al.admin_id = au.id
                  ORDER BY al.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }
}
?>