<?php
require_once 'BaseModel.php';

/**
 * Payment Model Class
 * Handles all payment-related database operations
 */
class Payment extends BaseModel {
    protected $table = 'payment';
    protected $primaryKey = 'pay_id';
    
    // Create new payment
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO payment (pay_id, amount, gym_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", 
            $data['pay_id'], 
            $data['amount'], 
            $data['gym_id']
        );
        return $stmt->execute();
    }
    
    // Update payment
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE payment SET amount = ?, gym_id = ? WHERE pay_id = ?");
        $stmt->bind_param("dss", 
            $data['amount'], 
            $data['gym_id'], 
            $id
        );
        return $stmt->execute();
    }
    
    // Get all payments with gym details
    public function getAllWithDetails() {
        $query = "SELECT p.*, g.gym_name 
                  FROM payment p 
                  LEFT JOIN gym g ON p.gym_id = g.gym_id";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Search payments
    public function search($searchTerm) {
        $search = "%" . $searchTerm . "%";
        $stmt = $this->db->prepare("SELECT p.*, g.gym_name 
                                   FROM payment p 
                                   LEFT JOIN gym g ON p.gym_id = g.gym_id 
                                   WHERE p.pay_id LIKE ? OR g.gym_name LIKE ?");
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Get payment by ID with details
    public function findByIdWithDetails($id) {
        $stmt = $this->db->prepare("SELECT p.*, g.gym_name 
                                   FROM payment p 
                                   LEFT JOIN gym g ON p.gym_id = g.gym_id 
                                   WHERE p.pay_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Validate payment data
    public function validate($data) {
        $errors = [];
        
        if (empty($data['pay_id'])) {
            $errors[] = "Payment ID is required";
        }
        
        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors[] = "Valid amount is required";
        }
        
        if (empty($data['gym_id'])) {
            $errors[] = "Gym ID is required";
        }
        
        return $errors;
    }
    
    // Get payments by gym
    public function getByGym($gymId) {
        $stmt = $this->db->prepare("SELECT * FROM payment WHERE gym_id = ?");
        $stmt->bind_param("s", $gymId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Override delete method to handle foreign key constraints
    public function delete($id) {
        try {
            // Start transaction
            $this->db->getConnection()->autocommit(false);
            
            // Step 1: Update members to remove references to this payment plan (set to NULL)
            $memberStmt = $this->db->prepare("UPDATE member SET pay_id = NULL WHERE pay_id = ?");
            $memberStmt->bind_param("s", $id);
            $memberStmt->execute();
            
            // Step 2: Update trainers to remove references to this payment plan (set to NULL)
            $trainerStmt = $this->db->prepare("UPDATE trainer SET pay_id = NULL WHERE pay_id = ?");
            $trainerStmt->bind_param("s", $id);
            $trainerStmt->execute();
            
            // Step 3: Delete the payment plan itself
            $paymentStmt = $this->db->prepare("DELETE FROM payment WHERE pay_id = ?");
            $paymentStmt->bind_param("s", $id);
            $success = $paymentStmt->execute();
            
            if ($success && $paymentStmt->affected_rows > 0) {
                // Commit the transaction
                $this->db->getConnection()->commit();
                $this->db->getConnection()->autocommit(true);
                return true;
            } else {
                // Rollback if payment deletion failed
                $this->db->getConnection()->rollback();
                $this->db->getConnection()->autocommit(true);
                return false;
            }
            
        } catch (Exception $e) {
            // Rollback on any error
            $this->db->getConnection()->rollback();
            $this->db->getConnection()->autocommit(true);
            throw $e;
        }
    }
}
?>