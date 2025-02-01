<?php

namespace App\Controllers;

use EMS\Framework\Controller\Controller;
use EMS\Framework\Http\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return $this->render("/dashboard/users/index.twig");
    }
}
