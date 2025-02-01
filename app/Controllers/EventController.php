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

    public function create(): Response
    {
        return $this->render("/dashboard/events/create.twig");
    }

    public function store()
    {
        $errors = $this->request->validate([
            'name' => "required|string",
            "description" => "required|string",
            "date" => "required|date",
            "status" => "required|in:public,private",
            "limit" => "required|number:int",
            "thumbnail" => "sometimes|file|max_size: 5",
        ]);

        if (!empty($errors)) {
            return $this->redirect('events.create', ['errors' => $errors, 'old' => $this->request->all()]);
        }

        return $this->redirect("events.create", ["message" => "Event created"]);
    }
}
