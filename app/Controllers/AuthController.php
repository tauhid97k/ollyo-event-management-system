<?php

namespace App\Controllers;

use EMS\Framework\Controller\Controller;
use EMS\Framework\Http\Response;

class AuthController extends Controller
{
    public function signInView(): Response
    {
        return $this->render("sign-in.twig");
    }

    public function signUpView(): Response
    {
        return $this->render("sign-up.twig");
    }
}
