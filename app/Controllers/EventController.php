<?php

namespace App\Controllers;

use App\Models\Event;
use EMS\Framework\Controller\Controller;
use EMS\Framework\Http\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $user = $this->request->auth();

        $perPage = 2; // Number of events per page
        $page = $this->request->get('page', 1); // Get current page number (default 1)
        $search = $this->request->get('search'); // Get search term

        // Sanitize the search term
        $search = $search ? filter_var($search, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null;

        $events = Event::getEventsWithPagination($user->id, $perPage, $page, $search);
        $totalEvents = Event::getTotalEvents($user->id, $search);
        $totalPages = ceil($totalEvents / $perPage);

        return $this->render("/dashboard/events/index.twig", [
            'events' => $events,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
        ]);
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
            "thumbnail" => "sometimes|file|max_size:2|mime:image/jpeg,image/jpg,image/png", // Adjust max size
        ]);

        if (!empty($errors)) {
            $_SESSION['old'] = $this->request->all();
            return $this->redirect('events.create', ['errors' => $errors]);
        }

        $user = $this->request->auth();
        $data = $this->request->all();

        $data['user_id'] = $user->id;

        if ($this->request->hasFile('thumbnail')) {
            $thumbnail = $_FILES['thumbnail'];
            $originalFilename = $thumbnail['name'];

            $timestamp = time();
            $randomString = bin2hex(random_bytes(8));
            $folderName = 'thumbnail-' . $randomString . '-' . $timestamp; // Folder name with random and timestamp

            $userSpecificDir = 'storage/events/user-' . $user->id; // User-specific directory
            $fullUserSpecificDir = __DIR__ . '/../../' . $userSpecificDir;

            // Create directory if it doesn't exist & adjust permissions
            if (!is_dir($fullUserSpecificDir)) {
                mkdir($fullUserSpecificDir, 0777, true);
            }

            $uploadDir = $userSpecificDir . '/' . $folderName; // Add the random folder
            $fullUploadDir = __DIR__ . '/../../' . $uploadDir;

            if (!is_dir($fullUploadDir)) {
                mkdir($fullUploadDir, 0777, true); // Create the nested folder
            }

            $targetPath = $fullUploadDir . '/' . $originalFilename;

            if (move_uploaded_file($thumbnail['tmp_name'], $targetPath)) {
                $data['thumbnail'] = '/' . $uploadDir . '/' . $originalFilename; // URL with full path
            } else {
                // Handle file move failure
                return $this->redirect('events.create', ['error' => 'Thumbnail upload failed.']);
            }
        }

        $event = new Event();
        if ($event->save($data)) {
            return $this->redirect("events.index", ["message" => "Event created"]);
        } else {
            // Handle event save failure
            return $this->redirect('events.create', ['error' => 'Event creation failed.']);
        }
    }
}
