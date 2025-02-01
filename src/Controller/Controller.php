<?php

namespace EMS\Framework\Controller;

use EMS\Framework\Http\Request;
use EMS\Framework\Http\Response;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\TwigFunction;

abstract class Controller
{
    protected ?Request $request = null;

    public function generateUrl(string $routeName, array $params = []): string
    {
        $routes = include BASE_PATH . '/routes/web.php';

        $foundRoute = null;
        foreach ($routes as $route) {
            $foundRoute = null;
            foreach ($routes as $route) {
                if (isset($route['name']) && $route['name'] === $routeName) {
                    $foundRoute = $route;
                    break;
                }
            }
        }

        if (!$foundRoute) {
            throw new \Exception("Route '{$routeName}' not found."); // Handle the error
        }

        // Construct the URL
        $url = $foundRoute[1];

        // Handle parameters (if any)
        if (!empty($params)) {
            //  Important: FastRoute uses placeholders like {id} in routes.
            //  Need to replace these with the actual parameter values.
            foreach ($params as $paramName => $paramValue) {
                $placeholder = '{' . $paramName . '}';
                $url = str_replace($placeholder, $paramValue, $url);
            }

            // Handle any remaining query string parameters
            $remainingParams = array_diff_key($params, array_flip(array_map(function ($match) {
                return $match[1];
            }, $this->extractPlaceholders($foundRoute[1]))));
            if (!empty($remainingParams)) {
                $url .= '?' . http_build_query($remainingParams);
            }
        }

        return $url;
    }

    private function extractPlaceholders(string $routePath): array
    {
        $matches = [];
        preg_match_all('/\{([a-zA-Z_]+)\}/', $routePath, $matches, PREG_SET_ORDER);
        return $matches;
    }

    // Redirect Method
    protected function redirect(string $routeName, array $with = [], int $status = 302): Response
    {
        $url = $this->generateUrl($routeName);

        // Store flash data in the session (if exist)
        if (!empty($with)) {
            $_SESSION['flash'] = $with;
        }

        header('Location: ' . $url, true, $status);

        return new Response('', $status);
    }

    // Twig Template rendering method
    public function render(string $template, ?array $vars = [])
    {
        $templatePath = BASE_PATH . "/views";
        $loader = new FilesystemLoader($templatePath);
        $twig = new Environment($loader);

        $twig->addFunction(new TwigFunction('url', [$this, 'generateUrl']));
        $currentPath = parse_url($this->request->getUri(), PHP_URL_PATH);
        $twig->addGlobal('current_path', $currentPath);

        // Get flash messages or an empty array
        $flash = $_SESSION['flash'] ?? [];

        $vars['flash'] = $flash; // Assign flash messages to the 'flash' variable

        // Clear flash messages after they are assigned to the Twig variable
        unset($_SESSION['flash']);

        $content = $twig->render($template, $vars);

        $response = new Response($content);
        return $response;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}
