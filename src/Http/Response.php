<?php

namespace EMS\Framework\Http;

class Response
{
    public function __construct(
        private ?string $content = "",
        private int $status = 200,
        private array $headers = []
    ) {
        http_response_code($status);
    }

    public function send(): void
    {
        echo $this->content;
    }

    // Generate URL
    public function generateUrl(string $routeName, array $params = []): string
    {
        $routes = include BASE_PATH . '/routes/web.php';

        $foundRoute = null;
        foreach ($routes as $route) {
            if (isset($route['name']) && $route['name'] === $routeName) {
                $foundRoute = $route;
                break;
            }
        }

        if (!$foundRoute) {
            throw new \Exception("Route '{$routeName}' not found.");
        }

        $url = $foundRoute[1];

        if (!empty($params)) {
            // Extract and replace placeholders
            preg_match_all('/\{([a-zA-Z_]+)\}/', $foundRoute[1], $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $paramName = $match[1];
                if (isset($params[$paramName])) {
                    $url = str_replace('{' . $paramName . '}', $params[$paramName], $url);
                    unset($params[$paramName]); // Remove used parameters
                }
            }

            // Handle remaining query string parameters
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        }

        return $url;
    }
}
