<?php
class User
{
    private $conn;
    private $table = 'users';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1. Create New User
    public function create($name, $email, $password)
    {
        $query = "INSERT INTO " . $this->table . " (name, email, password) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($name));
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bind_param("sss", $name, $email, $password);

        return $stmt->execute();
    }

    // 2. Check if Email Exists
    public function emailExists($email)
    {
        $query = "SELECT id FROM " . $this->table . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // 3. Get User by Email (For Login)
    public function getUserByEmail($email)
    {
        $query = "SELECT id, name, email, password FROM " . $this->table . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return null;
    }

    // 4. Get User By ID (For Profile/Status)
    public function getUserById($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return null;
    }

    // 5. Store Refresh Token (Update if exists, Insert if new)
    public function storeRefreshToken($userId, $token, $expiresAt, $userAgent)
    {
        // Check if user already has a token
        $checkQuery = "SELECT id FROM refresh_tokens WHERE user_id = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $checkStmt->store_result();

        $exists = $checkStmt->num_rows > 0;
        $checkStmt->close();

        if ($exists) {
            // Update existing session
            $updateQuery = "UPDATE refresh_tokens SET token_hash = ?, expires_at = ?, user_agent = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param("sssi", $token, $expiresAt, $userAgent, $userId);
            return $stmt->execute();
        } else {
            // Create new session
            $insertQuery = "INSERT INTO refresh_tokens (user_id, token_hash, expires_at, user_agent) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($insertQuery);
            $stmt->bind_param("isss", $userId, $token, $expiresAt, $userAgent);
            return $stmt->execute();
        }
    }

    // 6. Verify Refresh Token
    public function verifyRefreshToken($token)
    {
        $query = "SELECT * FROM refresh_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $token);

        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return false;
    }

    // 7. Delete Refresh Token (For Rotation or Logout)
    public function deleteRefreshToken($token)
    {
        $query = "DELETE FROM refresh_tokens WHERE token_hash = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }

    // 8. Update Login Stats
    public function updateLoginStats($userId)
    {
        $query = "UPDATE " . $this->table . " SET login_count = login_count + 1, last_login_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    // 9. Get Token Details (For Status API)
    public function getTokenByUserId($userId)
    {
        $query = "SELECT user_agent, expires_at FROM refresh_tokens WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return null;
    }
}
?>