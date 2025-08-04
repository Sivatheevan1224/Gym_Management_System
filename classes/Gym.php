<?php
require_once 'BaseModel.php';

/**
 * Gym Model Class
 * Handles all gym-related database operations
 */
class Gym extends BaseModel {
    protected $table = 'gym';
    protected $primaryKey = 'gym_id';
    
    // Properties for OOP usage
    public $gym_id;
    public $gym_name;
    public $address;
    public $type;
    
    // Create new gym
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO gym (gym_id, gym_name, address, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", 
            $data['gym_id'], 
            $data['gym_name'], 
            $data['address'], 
            $data['type']
        );
        return $stmt->execute();
    }
    
    // Update gym
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE gym SET gym_name = ?, address = ?, type = ? WHERE gym_id = ?");
        $stmt->bind_param("ssss", 
            $data['gym_name'], 
            $data['address'], 
            $data['type'], 
            $id
        );
        return $stmt->execute();
    }
    
    // Save method for OOP style
    public function save() {
        if (isset($this->gym_id) && $this->findById($this->gym_id)) {
            // Update existing
            return $this->update($this->gym_id, [
                'gym_name' => $this->gym_name,
                'address' => $this->address,
                'type' => $this->type
            ]);
        } else {
            // Create new
            return $this->create([
                'gym_id' => $this->gym_id,
                'gym_name' => $this->gym_name,
                'address' => $this->address,
                'type' => $this->type
            ]);
        }
    }
    
    // Static method to find gym by ID and return Gym object
    public static function findGymById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM gym WHERE gym_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $gym = new self();
            $gym->gym_id = $row['gym_id'];
            $gym->gym_name = $row['gym_name'];
            $gym->address = $row['address'];
            $gym->type = $row['type'];
            return $gym;
        }
        
        return null;
    }
    
    // Count gyms with optional search
    public static function count($search = '') {
        $db = Database::getInstance();
        
        if (!empty($search)) {
            $searchParam = "%" . $search . "%";
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM gym WHERE gym_name LIKE ? OR gym_id LIKE ? OR address LIKE ?");
            $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM gym");
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    
    // Search gyms with pagination
    public static function search($searchTerm = '', $limit = null, $offset = null) {
        $db = Database::getInstance();
        
        $query = "SELECT * FROM gym";
        $params = [];
        $types = '';
        
        if (!empty($searchTerm)) {
            $query .= " WHERE gym_name LIKE ? OR gym_id LIKE ? OR address LIKE ?";
            $searchParam = "%" . $searchTerm . "%";
            $params = [$searchParam, $searchParam, $searchParam];
            $types = 'sss';
        }
        
        $query .= " ORDER BY gym_id";
        
        if ($limit !== null) {
            $query .= " LIMIT ?";
            $params[] = $limit;
            $types .= 'i';
            
            if ($offset !== null) {
                $query .= " OFFSET ?";
                $params[] = $offset;
                $types .= 'i';
            }
        }
        
        $stmt = $db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Delete gym with cascading - override parent method
    public function delete($id = null) {
        // Use provided ID or fall back to instance property
        $gym_id = $id ?? $this->gym_id;
        
        if (empty($gym_id)) {
            return false;
        }
        
        try {
            // Start transaction
            $this->db->getConnection()->autocommit(false);
            
            // Step 1: Get all payment IDs for this gym first
            $paymentStmt = $this->db->prepare("SELECT pay_id FROM payment WHERE gym_id = ?");
            $paymentStmt->bind_param("s", $gym_id);
            $paymentStmt->execute();
            $paymentResult = $paymentStmt->get_result();
            $paymentIds = [];
            while ($row = $paymentResult->fetch_assoc()) {
                $paymentIds[] = $row['pay_id'];
            }
            
            // Step 2: Update members to remove payment references that will be deleted
            if (!empty($paymentIds)) {
                $placeholders = str_repeat('?,', count($paymentIds) - 1) . '?';
                $memberPaymentStmt = $this->db->prepare("UPDATE member SET pay_id = NULL WHERE pay_id IN ($placeholders)");
                $memberPaymentStmt->bind_param(str_repeat('s', count($paymentIds)), ...$paymentIds);
                $memberPaymentStmt->execute();
                
                // Step 3: Update trainers to remove payment references that will be deleted
                $trainerPaymentStmt = $this->db->prepare("UPDATE trainer SET pay_id = NULL WHERE pay_id IN ($placeholders)");
                $trainerPaymentStmt->bind_param(str_repeat('s', count($paymentIds)), ...$paymentIds);
                $trainerPaymentStmt->execute();
            }
            
            // Step 4: Update members to remove references to this gym (set to NULL) - PRESERVES MEMBER DATA
            $memberGymStmt = $this->db->prepare("UPDATE member SET gym_id = NULL WHERE gym_id = ?");
            $memberGymStmt->bind_param("s", $gym_id);
            $memberGymStmt->execute();
            
            // Step 5: Delete payment plans associated with this gym
            $deletePaymentStmt = $this->db->prepare("DELETE FROM payment WHERE gym_id = ?");
            $deletePaymentStmt->bind_param("s", $gym_id);
            $deletePaymentStmt->execute();
            
            // Step 6: Finally delete the gym itself
            $gymStmt = $this->db->prepare("DELETE FROM gym WHERE gym_id = ?");
            $gymStmt->bind_param("s", $gym_id);
            $success = $gymStmt->execute();
            
            if ($success && $gymStmt->affected_rows > 0) {
                // Commit the transaction
                $this->db->getConnection()->commit();
                $this->db->getConnection()->autocommit(true);
                return true;
            } else {
                // Rollback if gym deletion failed
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

    // Validate gym data - implement abstract method from BaseModel
    public function validate($data) {
        $errors = [];
        
        if (empty($data['gym_id'])) {
            $errors[] = "Gym ID is required";
        }
        
        if (empty($data['gym_name'])) {
            $errors[] = "Gym name is required";
        }
        
        if (empty($data['address'])) {
            $errors[] = "Address is required";
        }
        
        if (empty($data['type']) || !in_array($data['type'], ['unisex', 'women', 'men'])) {
            $errors[] = "Valid gym type is required (unisex, women, or men)";
        }
        
        return $errors;
    }

    // Get gym's members
    public function getMembers($gymId) {
        $stmt = $this->db->prepare("SELECT * FROM member WHERE gym_id = ?");
        $stmt->bind_param("s", $gymId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Count gym's members
    public function getMemberCount($gymId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM member WHERE gym_id = ?");
        $stmt->bind_param("s", $gymId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    
    // Get gym's payments
    public function getPayments($gymId) {
        $stmt = $this->db->prepare("SELECT * FROM payment WHERE gym_id = ?");
        $stmt->bind_param("s", $gymId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>