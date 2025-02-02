<?php

namespace App\Controllers;

use EMS\Framework\Controller\Controller;
use EMS\Framework\Http\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $user = $this->request->auth();
        if ($user->role !== 'admin') {
            return $this->redirect("dashboard");
        }

        return $this->render("/dashboard/users/index.twig");
    }
}
