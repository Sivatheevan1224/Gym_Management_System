<?php
require_once 'BaseModel.php';

/**
 * Trainer Model Class
 * Handles all trainer-related database operations
 */
class Trainer extends BaseModel {
    protected $table = 'trainer';
    protected $primaryKey = 'trainer_id';
    
    // Create new trainer
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO trainer (trainer_id, name, time, mobileno, pay_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", 
            $data['trainer_id'], 
            $data['name'], 
            $data['time'], 
            $data['mobileno'], 
            $data['pay_id']
        );
        return $stmt->execute();
    }
    
    // Update trainer
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE trainer SET name = ?, time = ?, mobileno = ?, pay_id = ? WHERE trainer_id = ?");
        $stmt->bind_param("sssss", 
            $data['name'], 
            $data['time'], 
            $data['mobileno'], 
            $data['pay_id'], 
            $id
        );
        return $stmt->execute();
    }
    
    // Get all trainers with payment details
    public function getAllWithDetails() {
        $query = "SELECT t.*, p.amount 
                  FROM trainer t 
                  LEFT JOIN payment p ON t.pay_id = p.pay_id";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Search trainers
    public function search($searchTerm) {
        $search = "%" . $searchTerm . "%";
        $stmt = $this->db->prepare("SELECT t.*, p.amount 
                                   FROM trainer t 
                                   LEFT JOIN payment p ON t.pay_id = p.pay_id 
                                   WHERE t.name LIKE ? OR t.trainer_id LIKE ?");
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Get trainer by ID with details
    public function findByIdWithDetails($id) {
        $stmt = $this->db->prepare("SELECT t.*, p.amount 
                                   FROM trainer t 
                                   LEFT JOIN payment p ON t.pay_id = p.pay_id 
                                   WHERE t.trainer_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Validate trainer data
    public function validate($data) {
        $errors = [];
        
        if (empty($data['trainer_id'])) {
            $errors[] = "Trainer ID is required";
        }
        
        if (empty($data['name'])) {
            $errors[] = "Name is required";
        }
        
        if (empty($data['time'])) {
            $errors[] = "Training time is required";
        }
        
        if (empty($data['mobileno']) || !preg_match('/^\d{10}$/', $data['mobileno'])) {
            $errors[] = "Valid 10-digit mobile number is required";
        }
        
        return $errors;
    }
    
    // Get trainer's members
    public function getMembers($trainerId) {
        $stmt = $this->db->prepare("SELECT * FROM member WHERE trainer_id = ?");
        $stmt->bind_param("s", $trainerId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Count trainer's members
    public function getMemberCount($trainerId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM member WHERE trainer_id = ?");
        $stmt->bind_param("s", $trainerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    
    // Override delete method to handle foreign key constraints - OPTIMIZED
    public function delete($id) {
        try {
            // Start transaction with proper isolation level
            $this->db->getConnection()->autocommit(false);
            
            // Check if trainer exists first to avoid unnecessary operations
            $checkStmt = $this->db->prepare("SELECT trainer_id FROM trainer WHERE trainer_id = ? LIMIT 1");
            $checkStmt->bind_param("s", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->db->getConnection()->rollback();
                $this->db->getConnection()->autocommit(true);
                return false;
            }
            
            // Step 1: Update members to remove references to this trainer (set to NULL) - OPTIMIZED
            $memberStmt = $this->db->prepare("UPDATE member SET trainer_id = NULL WHERE trainer_id = ?");
            $memberStmt->bind_param("s", $id);
            $memberStmt->execute();
            
            // Step 2: Delete the trainer itself
            $trainerStmt = $this->db->prepare("DELETE FROM trainer WHERE trainer_id = ?");
            $trainerStmt->bind_param("s", $id);
            $success = $trainerStmt->execute();
            
            if ($success && $trainerStmt->affected_rows > 0) {
                // Commit the transaction quickly
                $this->db->getConnection()->commit();
                $this->db->getConnection()->autocommit(true);
                return true;
            } else {
                // Rollback if trainer deletion failed
                $this->db->getConnection()->rollback();
                $this->db->getConnection()->autocommit(true);
                return false;
            }
            
        } catch (Exception $e) {
            // Rollback on any error quickly
            $this->db->getConnection()->rollback();
            $this->db->getConnection()->autocommit(true);
            throw $e;
        }
    }
}
?>