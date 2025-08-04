<?php
/**
 * Base Model Class
 * Provides common database operations for all models
 */
abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all records
    public function getAll() {
        $query = "SELECT * FROM {$this->table}";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Find record by ID
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Delete record by ID
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }
    
    // Check if record exists
    public function exists($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }
    
    // Get last insert ID
    public function getLastInsertId() {
        return $this->db->getConnection()->insert_id;
    }
    
    // Abstract methods to be implemented by child classes
    abstract public function create($data);
    abstract public function update($id, $data);
    abstract public function validate($data);
}
?>