<?php
class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Create User
    public function create($name, $email, $password) {
        $query = "INSERT INTO " . $this->table . " (name, email, password) VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data (prevent XSS)
        $name = htmlspecialchars(strip_tags($name));
        $email = htmlspecialchars(strip_tags($email));
        
        // Bind parameters
        $stmt->bind_param("sss", $name, $email, $password);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // 2. Check if email exists
    public function emailExists($email) {
        $query = "SELECT id, password, name FROM " . $this->table . " WHERE email = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        return $stmt->num_rows > 0;
    }

    // 3. Get User by Email (For Login)
    public function getUserByEmail($email) {
        $query = "SELECT id, name, email, password FROM " . $this->table . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            return $result->fetch_assoc(); // Returns the user row (or null)
        }
        return null;
    }
}
?>