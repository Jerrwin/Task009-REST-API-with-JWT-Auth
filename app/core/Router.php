<?php

class Router
{
    private $routes = [];

    // 1. Add Route (GET, POST, PUT, DELETE)
    public function add($method, $path, $controller, $action)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function get($path, $controller, $action)
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post($path, $controller, $action)
    {
        $this->add('POST', $path, $controller, $action);
    }

    public function put($path, $controller, $action)
    {
        $this->add('PUT', $path, $controller, $action);
    }

    public function delete($path, $controller, $action)
    {
        $this->add('DELETE', $path, $controller, $action);
    }

    // 2. Dispatch (Find the matching route and run it)
    public function dispatch($uri, $method)
    {
        $path = parse_url($uri, PHP_URL_PATH);

        // --- NEW LOGIC: Remove Project Subfolders ---
        // 1. Get the folder where the script is running (e.g., /Task009/public)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);

        // 2. Ensure slashes are forward slashes (Windows fix)
        $scriptDir = str_replace('\\', '/', $scriptDir);

        // 3. Remove that folder prefix from the URL
        if (strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }

        // 4. Ensure path starts with /
        $path = '/' . ltrim($path, '/');
        // ---------------------------------------------

        foreach ($this->routes as $route) {
            // Check if Method matches AND Path matches
            // We use Regex to handle dynamic IDs (e.g., /api/patients/5)
            // Convert route path "/api/patients/{id}" to Regex "#^/api/patients/(\d+)$#"

            $pattern = "#^" . preg_replace('/\{id\}/', '(\d+)', $route['path']) . "$#";

            if ($route['method'] === $method && preg_match($pattern, $path, $matches)) {

                // Load the Controller
                require_once __DIR__ . '/../controllers/' . $route['controller'] . '.php';
                $controllerName = $route['controller'];
                $controller = new $controllerName();

                // Get the ID if it exists (it will be in $matches[1])
                $id = isset($matches[1]) ? $matches[1] : null;

                // Call the method
                $actionName = $route['action'];
                $controller->$actionName($id);
                return;
            }
        }

        // If no route matches:
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "404 Not Found",
            "debug_path" => $path // This helps you see what the router actually sees!
        ]);
    }
}
?>