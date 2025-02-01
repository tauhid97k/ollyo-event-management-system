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
            'title' => "required|string",
            "date" => "required|date",
            "description" => "required|string",
            "status" => "required|in:upcoming,open,closed,private",
            "limit" => "required|number:int|min:1",
            "thumbnail" => "sometimes|file|max_size:1|mime:image/jpeg,image/jpg,image/png",
        ]);

        if (!empty($errors)) {
            $_SESSION['old'] = $this->request->all();
            return $this->redirect('events.create', ['errors' => $errors]);
        }

        return $this->redirect("events.index", ["message" => "Event created"]);
    }
}
