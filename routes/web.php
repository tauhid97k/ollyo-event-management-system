<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\HomeController;
use App\Controllers\UserController;

return [
    // Public Routes
    ["GET", "/", [HomeController::class, "index"], "name" => "home"],
    ["GET", "/sign-in", [AuthController::class, "signInView"], "name" => "sign-in.view",  "middleware" => "guest"],
    ["GET", "/sign-up", [AuthController::class, "signUpView"], "name" => "sign-up.view",  "middleware" => "guest"],
    ["POST", "/sign-up", [AuthController::class, "register"], "name" => "register"],
    ["POST", "/sign-in", [AuthController::class, "login"], "name" => "login"],


    // Private Routes
    ["POST", "/logout", [AuthController::class, "logout"], "name" => "logout", "middleware" => "auth"],

    ["GET", "/dashboard", [DashboardController::class, "index"], "name" => "dashboard", "middleware" => "auth"],

    // Event Routes
    ["GET", "/dashboard/events", [EventController::class, "index"], "name" => "events.index", "middleware" => "auth"],
    ["GET", "/dashboard/events/create", [EventController::class, "create"], "name" => "events.create",  "middleware" => "auth"],
    ["POST", "/dashboard/events", [EventController::class, "store"], "name" => "events.store",  "middleware" => "auth"],

    // User Routes
    ["GET", "/dashboard/users", [UserController::class, "index"], "name" => "users.index",  "middleware" => "auth"]
];
