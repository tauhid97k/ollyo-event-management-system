<?php

namespace App\Controllers;

use EMS\Framework\Http\Response;

class HomeController
{
    public function index(): Response
    {
        $content = "<h1>Home Page</h1>";
        return new Response($content);
    }
}
