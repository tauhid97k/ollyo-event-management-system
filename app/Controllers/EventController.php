<?php

namespace App\Controllers;

use EMS\Framework\Controller\Controller;
use EMS\Framework\Http\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return $this->render("/dashboard/events/index.twig");
    }
}
