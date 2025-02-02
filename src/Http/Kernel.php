<?php

namespace EMS\Framework\Http;

use Dotenv\Dotenv;
use EMS\Framework\Controller\Controller;
use EMS\Framework\Database\Connection;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

class Kernel
{
    protected ?Connection $connection = null;
    private ?Response $response; // Declare Response property


    public function __construct()
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(BASE_PATH);
        $dotenv->load();

        // MySQL Access Env
        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['DB_DATABASE'];
        $dbUser = $_ENV['DB_USERNAME'];
        $dbPass = $_ENV['DB_PASSWORD'];
        $dbPort = $_ENV['DB_PORT'];

        // Connection
        $connectionString = "mysql:host={$dbHost};dbname={$dbName};port={$dbPort}";

        $this->connection = Connection::create($connectionString, $dbUser, $dbPass);

        $this->response = new Response();
    }

    public function handle(Request $request): Response
    {
        $routes = include BASE_PATH . '/routes/web.php';

        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) use ($routes) {
            foreach ($routes as $routeDefinition) {
                $method = $routeDefinition[0];
                $path = $routeDefinition[1];
                $handler = $routeDefinition[2];
                $name = $routeDefinition['name'] ?? null;

                $routeCollector->addRoute($method, $path, $handler);

                // if Name is set
                if ($name) {
                    $routeNames[$method . $path] = $name;
                }
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()
        );

        // Route validation and response
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // 404
                return new Response("<h1>Not Found</h1>", 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                // 405
                return new Response("<h1>Method Not Allowed</h1>", 405);
            case Dispatcher::FOUND:
                [$status, [$controller, $method], $vars] = $routeInfo;

                $controller = new $controller;

                if ($controller instanceof Controller) {
                    $controller->setRequest($request);
                }

                // Get the current path for matching
                $currentPath = $request->getUri();

                // Find the matching route definition to get the middleware
                foreach ($routes as $routeDefinition) {
                    if ($routeDefinition[1] === $currentPath) {
                        $middleware = $routeDefinition['middleware'] ?? null;

                        if ($middleware === 'auth' && !isset($_SESSION['user'])) {
                            $redirectUrl = $this->response->generateUrl('sign-in.view');
                            header('Location: ' . $redirectUrl);
                            exit;
                        }

                        if ($middleware === 'guest' && isset($_SESSION['user'])) {
                            $redirectUrl = $this->response->generateUrl('dashboard');
                            header('Location: ' . $redirectUrl);
                            exit;
                        }
                        break;
                    }
                }

                return call_user_func_array([$controller, $method], $vars);
        }
    }
}
