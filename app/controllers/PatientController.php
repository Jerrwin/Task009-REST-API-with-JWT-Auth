<?php
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Response.php';

class PatientController {
    private $patientModel;

    public function __construct() {
        // 1. PROTECT THE ENTIRE CONTROLLER
        // If the token is missing or invalid, the script DIES here.
        AuthMiddleware::handle(); 

        // 2. Connect to DB
        $database = new Database();
        $db = $database->connect();
        $this->patientModel = new Patient($db);
    }

    // GET /api/patients
    public function index() {
        $patients = $this->patientModel->getAll();
        Response::send(true, "Patients fetched successfully", $patients);
    }

    // POST /api/patients
    public function store() {
        // Get data (JsonMiddleware already put it in $_POST or we read php://input)
        $data = json_decode(file_get_contents("php://input"), true);

        // Validation
        if (!isset($data['name']) || !isset($data['age']) || !isset($data['phone'])) {
            Response::send(false, "Name, Age, and Phone are required", [], 400);
            return;
        }

        $newId = $this->patientModel->create($data);

        if ($newId) {
            $data['id'] = $newId; // Attach new ID to response
            Response::send(true, "Patient created successfully", $data, 201);
        } else {
            Response::send(false, "Failed to create patient", [], 500);
        }
    }

    // PUT /api/patients/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);

        // Simple validation
        if (empty($data)) {
            Response::send(false, "No data provided to update", [], 400);
            return;
        }

        if ($this->patientModel->update($id, $data)) {
            Response::send(true, "Patient updated successfully", $data);
        } else {
            Response::send(false, "Failed to update patient", [], 500);
        }
    }

    // DELETE /api/patients/{id}
    public function destroy($id) {
        if ($this->patientModel->delete($id)) {
            Response::send(true, "Patient deleted successfully", []);
        } else {
            Response::send(false, "Failed to delete patient", [], 500);
        }
    }
}
?>