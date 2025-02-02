<?php

namespace App\Controllers;

use EMS\Framework\Controller\Controller;
use EMS\Framework\Http\Response;
use App\Models\User;

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

    // User Registration
    public function register()
    {
        $errors = $this->request->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if (!empty($errors)) {
            $oldInput = $this->request->all();
            unset($oldInput['password']); // Remove the password from the old input
            $_SESSION['old'] = $oldInput;

            return $this->redirect('sign-up.view', ['errors' => $errors]);
        }

        $user = new User();

        // Check if user with this email already exists
        if ($user->findByEmail($this->request->input('email'))) {
            $_SESSION['old'] = $this->request->all();
            return $this->redirect('sign-up.view', ['error' => 'A user with this email already exists.']);
        }


        // Save new user
        $data = $this->request->all();
        $result = $user->save($data);

        if ($result) {
            return $this->redirect('sign-in.view', ['message' => 'Registration successful']);
        } else {
            $_SESSION['old'] = $this->request->all();
            return $this->redirect('sign-up.view', ['error' => 'Registration failed. Please try again.']);
        }
    }
}
