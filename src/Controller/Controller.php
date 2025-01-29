<?php

namespace EMS\Framework\Controller;

use EMS\Framework\Http\Request;
use EMS\Framework\Http\Response;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

abstract class Controller
{
    protected ?Request $request = null;

    public function render(string $template, ?array $vars = [])
    {
        $templatePath = BASE_PATH . "/views";
        $loader = new FilesystemLoader($templatePath);
        $twig = new Environment($loader);

        $content = $twig->render($template, $vars);

        $response = new Response($content);

        return $response;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}
