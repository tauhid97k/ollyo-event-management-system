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
    protected ?Response $response = null;

    public function __construct()
    {
        $this->response = new Response();
    }

    // Redirect Method
    protected function redirect(string $routeName, array $with = [], int $status = 302): Response
    {
        $url = $this->response->generateUrl($routeName);

        // Store flash data in the session (if exist)
        if (!empty($with)) {
            $_SESSION['flash'] = $with;
        }

        header('Location: ' . $url, true, $status);

        return $this->response;
    }

    // Twig Template rendering method
    public function render(string $template, ?array $vars = [])
    {
        $templatePath = BASE_PATH . "/views";
        $loader = new FilesystemLoader($templatePath);
        $twig = new Environment($loader);

        $twig->addFunction(new TwigFunction('url', [$this->response, 'generateUrl']));
        $currentPath = parse_url($this->request->getUri(), PHP_URL_PATH);
        $twig->addGlobal('current_path', $currentPath);
        $twig->addFunction(new TwigFunction('auth', [$this->request, 'auth']));

        // Get flash messages or an empty array
        $flash = $_SESSION['flash'] ?? [];
        $vars['flash'] = $flash;
        unset($_SESSION['flash']);

        // Get old input values or an empty array
        $oldInput = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $vars['old'] = $oldInput;

        $content = $twig->render($template, $vars);

        $response = new Response($content);
        return $response;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}
