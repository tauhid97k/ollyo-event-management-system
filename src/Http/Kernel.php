<?php

namespace EMS\Framework\Http;

use EMS\Framework\Controller\Controller;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

class Kernel
{
    public function handle(Request $request): Response
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            $routes = include BASE_PATH . '/routes/web.php';

            foreach ($routes as $route) {
                $routeCollector->addRoute(...$route);
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()
        );

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

                return call_user_func_array([$controller, $method], $vars);
        }
    }
}
