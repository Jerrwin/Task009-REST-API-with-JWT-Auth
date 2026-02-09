<?php
require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';

class AuthMiddleware
{

    public static function handle()
    {
        // 1. Get headers (Handling Apache/Nginx differences)
        $headers = getallheaders();
        $authHeader = null;

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }

        // 2. Check existence
        if (!$authHeader) {
            Response::send(false, "Unauthorized: No token provided", [], 401);
            exit;
        }

        // 3. Extract Bearer Token
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            Response::send(false, "Unauthorized: Invalid token format", [], 401);
            exit;
        }

        // 4. Validate
        $secret = $_ENV['JWT_SECRET'];
        $userData = JWT::validate($token, $secret);

        if (!$userData) {
            Response::send(false, "Unauthorized: Invalid or Expired Token", [], 401);
            exit;
        }

        // 5. ATTACH USER DATA TO REQUEST
        // This fulfills the requirement: "Attach decoded data to request"
        // Now in your PatientController, you can access $_REQUEST['user']['id']
        $_REQUEST['user'] = $userData;
    }
}
?>