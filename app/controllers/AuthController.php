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

        // Enforce strong password
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

        if (!$user || !password_verify($data->password, $user['password'])) {
            Response::send(false, "Invalid credentials", [], 401);
            return;
        }

        // Track login stats
        $this->userModel->updateLoginStats($user['id']);

        // Generate Access Token (Short-lived)
        $jwtExpiry = time() + ($_ENV['JWT_ACCESS_LIFETIME'] ?? 30);
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => $jwtExpiry
        ];
        $accessToken = JWT::encode($payload, $_ENV['JWT_SECRET']);

        // Generate Refresh Token (Long-lived)
        $refreshToken = bin2hex(random_bytes(32));
        $refreshSeconds = $_ENV['REFRESH_TOKEN_LIFETIME'] ?? 604800;
        $refreshExpiresAt = date('Y-m-d H:i:s', time() + $refreshSeconds);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Store Refresh Token
        $this->userModel->storeRefreshToken($user['id'], $refreshToken, $refreshExpiresAt, $userAgent);

        Response::send(true, "Login successful", [
            'access_token' => $accessToken,
            'access_token_expires_at' => date('Y-m-d H:i:s', $jwtExpiry),
            'refresh_token' => $refreshToken,
            'refresh_token_expires_at' => $refreshExpiresAt,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
    }

    // POST /api/refresh
    public function refresh()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->refresh_token)) {
            Response::send(false, "Refresh Token is missing", [], 400);
            return;
        }

        $incomingToken = $data->refresh_token;

        // Verify validity and expiry
        $tokenRow = $this->userModel->verifyRefreshToken($incomingToken);

        if (!$tokenRow) {
            Response::send(false, "Invalid or Expired Refresh Token. Please Login again.", [], 401);
            return;
        }

        // Token Rotation: Invalidate old token
        $this->userModel->deleteRefreshToken($incomingToken);

        // Generate New Access Token
        $user = $this->userModel->getUserById($tokenRow['user_id']);

        $jwtExpiry = time() + ($_ENV['JWT_ACCESS_LIFETIME'] ?? 30);
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => $jwtExpiry
        ];
        $newAccessToken = JWT::encode($payload, $_ENV['JWT_SECRET']);

        // Generate New Refresh Token
        $newRefreshToken = bin2hex(random_bytes(32));
        $refreshSeconds = $_ENV['REFRESH_TOKEN_LIFETIME'] ?? 604800;
        $newExpiresAt = date('Y-m-d H:i:s', time() + $refreshSeconds);
        $newUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Store New Refresh Token
        $this->userModel->storeRefreshToken($user['id'], $newRefreshToken, $newExpiresAt, $newUserAgent);

        Response::send(true, "Token Refreshed", [
            'access_token' => $newAccessToken,
            'access_token_expires_at' => date('Y-m-d H:i:s', $jwtExpiry),
            'refresh_token' => $newRefreshToken,
            'refresh_token_expires_at' => $newExpiresAt
        ]);
    }

    // GET /api/user/status
    public function status()
    {
        // Extract & Validate Header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::send(false, "Token not provided", [], 401);
            return;
        }

        // Validate JWT Signature & Expiry
        $decoded = JWT::validate($matches[1], $_ENV['JWT_SECRET']);
        if (!$decoded) {
            Response::send(false, "Invalid Token or Session Expired", [], 401);
            return;
        }

        // Fetch User & Session Data
        $userId = $decoded['user_id'];
        $user = $this->userModel->getUserById($userId);
        $tokenInfo = $this->userModel->getTokenByUserId($userId);

        // Calculate Session Duration
        $expiryDate = new DateTime($tokenInfo['expires_at']);
        $now = new DateTime();

        if ($now > $expiryDate) {
            $timeLeft = "Expired";
        } else {
            $diff = $now->diff($expiryDate);
            $timeLeft = $diff->format('%a days, %h hours, %i minutes, %s seconds');
        }

        Response::send(true, "User Status", [
            "status" => "Active",
            "username" => $user['name'],
            "email" => $user['email'],
            "login_count" => $user['login_count'],
            "last_login" => $user['last_login_at'],
            "last_used_device" => $tokenInfo['user_agent'],
            "session_expires_at" => $tokenInfo['expires_at'],
            "session_time_left" => $timeLeft
        ]);
    }
}
?>