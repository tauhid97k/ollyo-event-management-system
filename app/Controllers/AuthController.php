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

    // User Login
    public function login()
    {
        $errors = $this->request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if (!empty($errors)) {
            $oldInput = $this->request->all();
            unset($oldInput['password']); // Remove the password from the old input
            $_SESSION['old'] = $oldInput;

            return $this->redirect('sign-in.view', ['errors' => $errors]);
        }

        $email = $this->request->input('email');
        $password = $this->request->input('password');

        $user = new User();
        $existingUser = $user->findByEmail($email);

        // Check if user exist and if credentials are correct
        if ($existingUser && password_verify($password, $existingUser['password'])) {

            session_regenerate_id(true);

            // If success Store user information in the session
            $_SESSION['user'] = [
                'id' => $existingUser['id'],
                'name' => $existingUser['name'],
                'email' => $existingUser['email'],
            ];

            return $this->redirect('dashboard', ['message' => 'Login successful']);
        } else {
            // Invalid credentials
            $oldInput = $this->request->all();
            unset($oldInput['password']); // Remove the password from the old input
            $_SESSION['old'] = $oldInput;

            return $this->redirect('sign-in.view', ['error' => 'Invalid email or password']);
        }
    }

    // User logout
    public function logout(): Response
    {
        // Clear the session 
        unset($_SESSION['user']);

        // Regenerate the session ID 
        session_regenerate_id(true);

        // Destroy the session 
        session_destroy();

        return $this->redirect('sign-in.view', ['message' => 'You are now logged out']);
    }
}
