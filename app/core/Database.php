<?php

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function connect()
    {
        $this->conn = null;

        // Load credentials from Environment Variables
        $this->host = $_ENV['DB_HOST'];
        $this->db_name = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];

        // Load Port (Default to 3306 if not set in .env)
        $this->port = isset($_ENV['DB_PORT']) ? (int) $_ENV['DB_PORT'] : 3306;

        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);
        } catch (Exception $e) {
            die("Connection Error: " . $e->getMessage());
        }

        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        // Set Charset
        $this->conn->set_charset("utf8");

        return $this->conn;
    }
}
?>