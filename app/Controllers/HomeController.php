<?php

namespace App\Controllers;

use EMS\Framework\Controller\Controller;
use EMS\Framework\Http\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->render("home.twig");
    }
}
