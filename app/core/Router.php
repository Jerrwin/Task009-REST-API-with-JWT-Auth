<?php

class Router
{
    private $routes = [];

    // Add a route to the internal list
    public function add($method, $path, $controller, $action)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    // Shorthand methods for HTTP verbs
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

    // Dispatch: Find the matching route and execute it
    public function dispatch($uri, $method)
    {
        $path = parse_url($uri, PHP_URL_PATH);

        // 1. Clean URL: Remove project subfolders (e.g., /Task009/public) to get the relative path
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if (strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }
        $path = '/' . ltrim($path, '/');

        // 2. Search for a matching route
        foreach ($this->routes as $route) {

            // Convert route definition (e.g., /patients/{id}) into Regex to match dynamic IDs
            $pattern = "#^" . preg_replace('/\{id\}/', '(\d+)', $route['path']) . "$#";

            if ($route['method'] === $method && preg_match($pattern, $path, $matches)) {

                // 3. Load and Instantiate Controller
                require_once __DIR__ . '/../controllers/' . $route['controller'] . '.php';
                $controllerName = $route['controller'];
                $controller = new $controllerName();

                // 4. Extract ID (if present) and call the action
                $id = isset($matches[1]) ? $matches[1] : null;
                $actionName = $route['action'];
                $controller->$actionName($id);
                return;
            }
        }

        // 5. No match found -> 404 Error
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "404 Not Found",
            "path" => $path
        ]);
    }
}
?>