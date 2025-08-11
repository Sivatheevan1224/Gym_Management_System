<?php
require_once 'BaseModel.php';

/**
 * Member Model Class
 * Handles all member-related database operations
 */
class Member extends BaseModel {
    protected $table = 'member';
    protected $primaryKey = 'mem_id';
    
    // Create new member
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO member (mem_id, name, age, dob, mobileno, pay_id, trainer_id, gym_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssss", 
            $data['mem_id'], 
            $data['name'], 
            $data['age'], 
            $data['dob'], 
            $data['mobileno'], 
            $data['pay_id'], 
            $data['trainer_id'], 
            $data['gym_id']
        );
        return $stmt->execute();
    }
    
    // Update member
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE member SET name = ?, age = ?, dob = ?, mobileno = ?, pay_id = ?, trainer_id = ?, gym_id = ? WHERE mem_id = ?");
        $stmt->bind_param("sissssss", 
            $data['name'], 
            $data['age'], 
            $data['dob'], 
            $data['mobileno'], 
            $data['pay_id'], 
            $data['trainer_id'], 
            $data['gym_id'], 
            $id
        );
        return $stmt->execute();
    }
    
    // Get all members with joined data and pagination
    public function getAllWithDetails($search = '', $limit = null, $offset = null) {
        $whereClause = '';
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $whereClause = "WHERE m.name LIKE ? OR m.mem_id LIKE ? OR m.mobileno LIKE ?";
            $searchParam = "%" . $search . "%";
            $params = [$searchParam, $searchParam, $searchParam];
            $types = 'sss';
        }
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total 
                       FROM member m 
                       LEFT JOIN payment p ON m.pay_id = p.pay_id 
                       LEFT JOIN trainer t ON m.trainer_id = t.trainer_id 
                       LEFT JOIN gym g ON m.gym_id = g.gym_id 
                       $whereClause";
        
        $total = 0;
        if (!empty($whereClause)) {
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $total = $countResult->fetch_assoc()['total'];
        } else {
            $countResult = $this->db->query($countQuery);
            $total = $countResult->fetch_assoc()['total'];
        }
        
        // Main query with pagination
        $query = "SELECT m.*, p.amount, p.pay_id as payment_plan, 
                         t.name as trainer_name, g.gym_name 
                  FROM member m 
                  LEFT JOIN payment p ON m.pay_id = p.pay_id 
                  LEFT JOIN trainer t ON m.trainer_id = t.trainer_id 
                  LEFT JOIN gym g ON m.gym_id = g.gym_id 
                  $whereClause
                  ORDER BY m.mem_id ASC";
        
        if ($limit !== null) {
            $query .= " LIMIT $limit";
            if ($offset !== null) {
                $query .= " OFFSET $offset";
            }
        }
        
        $members = [];
        if (!empty($whereClause)) {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $members = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $result = $this->db->query($query);
            $members = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
        
        return [
            'members' => $members,
            'total' => $total
        ];
    }
    
    // Search members
    public function search($searchTerm) {
        $search = "%" . $searchTerm . "%";
        $stmt = $this->db->prepare("SELECT m.*, p.amount, t.name as trainer_name, g.gym_name 
                                   FROM member m 
                                   LEFT JOIN payment p ON m.pay_id = p.pay_id 
                                   LEFT JOIN trainer t ON m.trainer_id = t.trainer_id 
                                   LEFT JOIN gym g ON m.gym_id = g.gym_id 
                                   WHERE m.name LIKE ? OR m.mem_id LIKE ?");
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Get member by ID with details
    public function findByIdWithDetails($id) {
        $stmt = $this->db->prepare("SELECT m.*, p.amount, t.name as trainer_name, g.gym_name 
                                   FROM member m 
                                   LEFT JOIN payment p ON m.pay_id = p.pay_id 
                                   LEFT JOIN trainer t ON m.trainer_id = t.trainer_id 
                                   LEFT JOIN gym g ON m.gym_id = g.gym_id 
                                   WHERE m.mem_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Validate member data
    public function validate($data) {
        $errors = [];
        
        if (empty($data['mem_id'])) {
            $errors[] = "Member ID is required";
        }
        
        if (empty($data['name'])) {
            $errors[] = "Name is required";
        }
        
        if (empty($data['age']) || $data['age'] < 10 || $data['age'] > 80) {
            $errors[] = "Valid age is required (10-80)";
        }
        
        if (empty($data['mobileno']) || !preg_match('/^\d{10}$/', $data['mobileno'])) {
            $errors[] = "Valid 10-digit mobile number is required";
        }
        
        if (empty($data['dob'])) {
            $errors[] = "Date of birth is required";
        } else {
            // Validate date of birth format and constraints
            $dob = $data['dob'];
            $age = isset($data['age']) ? (int)$data['age'] : 0;
            
            // Check if date format is valid (YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                $errors[] = "Date of birth must be in YYYY-MM-DD format";
            } else {
                $dobDate = new DateTime($dob);
                $today = new DateTime();
                $minDate = new DateTime();
                $maxDate = new DateTime();
                
                // Set date limits (10-80 years old)
                $minDate->modify('-80 years');
                $maxDate->modify('-10 years');
                
                // Check if date is not in the future
                if ($dobDate > $today) {
                    $errors[] = "Date of birth cannot be in the future";
                }
                
                // Check if date is within valid age range
                if ($dobDate < $minDate) {
                    $errors[] = "Date of birth indicates age over 80 years";
                }
                
                if ($dobDate > $maxDate) {
                    $errors[] = "Date of birth indicates age under 10 years";
                }
                
                // If age is provided, check if DOB matches the age (within 1 year tolerance)
                if ($age > 0) {
                    $calculatedAge = $today->diff($dobDate)->y;
                    if (abs($calculatedAge - $age) > 1) {
                        $errors[] = "Date of birth does not match the provided age. Calculated age: $calculatedAge";
                    }
                }
                
                // Additional check: DOB should not be older than 120 years (extreme case)
                $extremeMinDate = new DateTime();
                $extremeMinDate->modify('-120 years');
                if ($dobDate < $extremeMinDate) {
                    $errors[] = "Invalid date of birth - too old";
                }
            }
        }
        
        return $errors;
    }
    
    // Get members by trainer
    public function getByTrainer($trainerId) {
        $stmt = $this->db->prepare("SELECT * FROM member WHERE trainer_id = ?");
        $stmt->bind_param("s", $trainerId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Get members by gym
    public function getByGym($gymId) {
        $stmt = $this->db->prepare("SELECT * FROM member WHERE gym_id = ?");
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
            
            // First, delete any login records associated with this member
            $loginStmt = $this->db->prepare("DELETE FROM login WHERE member_id = ?");
            $loginStmt->bind_param("s", $id);
            $loginStmt->execute();
            
            // Then delete the member record
            $memberStmt = $this->db->prepare("DELETE FROM member WHERE mem_id = ?");
            $memberStmt->bind_param("s", $id);
            $success = $memberStmt->execute();
            
            if ($success && $memberStmt->affected_rows > 0) {
                // Commit the transaction
                $this->db->getConnection()->commit();
                $this->db->getConnection()->autocommit(true);
                return true;
            } else {
                // Rollback if member deletion failed
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