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

    public function signIn(): Response
    {
        return $this->render("sign-in.twig");
    }

    public function signUp(): Response
    {
        return $this->render("sign-up.twig");
    }
}
