<?php

class Patient
{
    private $conn;
    private $table = 'patients';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1. Get All Patients
    public function getAll()
    {
        // Order by newest first
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // 2. Create Patient
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " (name, age, gender, phone, address) VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        // Clean and Bind
        $name = htmlspecialchars(strip_tags($data['name']));
        $age = htmlspecialchars(strip_tags($data['age']));
        $gender = htmlspecialchars(strip_tags($data['gender']));
        $phone = htmlspecialchars(strip_tags($data['phone']));
        $address = htmlspecialchars(strip_tags($data['address']));

        $stmt->bind_param("sisss", $name, $age, $gender, $phone, $address);

        if ($stmt->execute()) {
            return $this->conn->insert_id; // Return the new ID
        }
        return false;
    }

    // 3. Update Patient
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table . " SET name=?, age=?, gender=?, phone=?, address=? WHERE id=?";

        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($data['name']));
        $age = htmlspecialchars(strip_tags($data['age']));
        $gender = htmlspecialchars(strip_tags($data['gender']));
        $phone = htmlspecialchars(strip_tags($data['phone']));
        $address = htmlspecialchars(strip_tags($data['address']));

        $stmt->bind_param("sisssi", $name, $age, $gender, $phone, $address, $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // 4. Delete Patient
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>