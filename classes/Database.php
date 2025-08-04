<?php
/**
 * Database Connection Class
 * Handles database connection and basic operations using singleton pattern
 */
class Database {
    private static $instance = null;
    private $connection;
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'gym';
    
    // Private constructor to prevent direct creation
    private function __construct() {
        $this->connect();
    }
    
    // Get singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Establish database connection
    private function connect() {
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("We're experiencing technical difficulties. Please try again later.");
        }
    }
    
    // Get database connection
    public function getConnection() {
        return $this->connection;
    }
    
    // Prepare statement
    public function prepare($query) {
        return $this->connection->prepare($query);
    }
    
    // Execute query
    public function query($query) {
        return $this->connection->query($query);
    }
    
    // Get real escape string
    public function real_escape_string($string) {
        return $this->connection->real_escape_string($string);
    }
    
    // Begin transaction
    public function begin_transaction() {
        return $this->connection->begin_transaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // Close connection
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>