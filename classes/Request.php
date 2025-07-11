<?php
class Request {
    private $conn;
    private $table_name = "requests";

    public $id;
    public $type;
    public $purpose;
    public $status;
    public $resident_id;
    public $request_details;
    public $processing_fee;
    public $document_path;
    public $can_download;
    public $can_reupload;
    public $admin_notes;
    public $created_at;
    public $updated_at;
    public $processed_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all requests for a resident
    public function getResidentRequests($resident_id) {
        error_log("Getting requests for resident ID: " . $resident_id);
        $query = "SELECT r.*, rt.processing_fee as type_fee 
                  FROM " . $this->table_name . " r
                  LEFT JOIN request_types rt ON r.type = rt.name
                  WHERE r.resident_id = :resident_id 
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->execute();

        error_log("Query executed, found " . $stmt->rowCount() . " requests");
        return $stmt;
    }

    // Create new request
    public function create() {
        error_log("Creating new request for resident ID: " . $this->resident_id);
        // Check if processing_fee column exists, if not, create without it
        $query = "INSERT INTO " . $this->table_name . " 
                  SET type=:type, purpose=:purpose, resident_id=:resident_id, 
                      request_details=:request_details";
        
        // Add processing_fee if it's set
        if (isset($this->processing_fee)) {
            $query .= ", processing_fee=:processing_fee";
        }

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->purpose = htmlspecialchars(strip_tags($this->purpose));

        // Bind values
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":purpose", $this->purpose);
        $stmt->bindParam(":resident_id", $this->resident_id);
        $stmt->bindParam(":request_details", $this->request_details);
        
        if (isset($this->processing_fee)) {
            $stmt->bindParam(":processing_fee", $this->processing_fee);
        }

        if($stmt->execute()) {
            $request_id = $this->conn->lastInsertId();
            error_log("Request created successfully with ID: " . $request_id);
            return $request_id;
        }

        error_log("Failed to create request");
        return false;
    }

    // Get request statistics
    public function getStats($resident_id) {
        error_log("Getting stats for resident ID: " . $resident_id);
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_for_pickup
                  FROM " . $this->table_name . " 
                  WHERE resident_id = :resident_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->execute();

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Stats retrieved: " . print_r($stats, true));
        return $stats;
    }

    // Get request types
    public function getRequestTypes() {
        error_log("Getting request types");
        $query = "SELECT * FROM request_types WHERE is_active = 1 ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get blotter reports count
    public function getBlotterCount($resident_id) {
        error_log("Getting blotter count for resident ID: " . $resident_id);
        // Check if blotter_reports table exists
        try {
            $query = "SELECT COUNT(*) as count FROM blotter_reports WHERE complainant_id = :resident_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':resident_id', $resident_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Blotter count: " . $result['count']);
            return $result['count'];
        } catch (Exception $e) {
            error_log("Blotter table doesn't exist, returning 0");
            // If table doesn't exist, return 0
            return 0;
        }
    }
}
?>