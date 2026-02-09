<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/JWT.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $database = new Database();
        $db = $database->connect();
        $this->userModel = new User($db);
    }

    // POST /api/register
    public function register()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->name) || !isset($data->email) || !isset($data->password)) {
            Response::send(false, "Please provide name, email, and password", [], 400);
            return;
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $data->password)) {
            Response::send(false, "Password must be at least 8 chars, contain 1 Uppercase & 1 Special Character", [], 400);
            return;
        }

        if ($this->userModel->emailExists($data->email)) {
            Response::send(false, "Email already exists", [], 409);
            return;
        }

        $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

        if ($this->userModel->create($data->name, $data->email, $hashed_password)) {
            Response::send(true, "User registered successfully", [], 201);
        } else {
            Response::send(false, "User registration failed", [], 500);
        }
    }


    // POST /api/login
    public function login()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email) || !isset($data->password)) {
            Response::send(false, "Please provide email and password", [], 400);
            return;
        }

        $user = $this->userModel->getUserByEmail($data->email);

        if (!$user) {
            Response::send(false, "Invalid credentials", [], 401);
            return;
        }

        if (!password_verify($data->password, $user['password'])) {
            Response::send(false, "Invalid credentials", [], 401);
            return;
        }

        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'iat' => time(), 
            'exp' => time() + $_ENV['JWT_EXPIRY'] 
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET']);

        Response::send(true, "Login successful", [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
    }
}
?>