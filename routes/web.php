<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\HomeController;
use App\Controllers\UserController;

return [
    // Public Routes
    ["GET", "/", [HomeController::class, "index"], "name" => "home"],
    ["GET", "/sign-in", [AuthController::class, "signInView"], "name" => "sign-in.view"],
    ["GET", "/sign-up", [AuthController::class, "signUpView"], "name" => "sign-up.view"],
    ["POST", "/sign-up", [AuthController::class, "register"], "name" => "register"],


    // Private Routes
    ["GET", "/dashboard", [DashboardController::class, "index"], "name" => "dashboard"],

    // Event Routes
    ["GET", "/dashboard/events", [EventController::class, "index"], "name" => "events.index"],
    ["GET", "/dashboard/events/create", [EventController::class, "create"], "name" => "events.create"],
    ["POST", "/dashboard/events", [EventController::class, "store"], "name" => "events.store"],

    // User Routes
    ["GET", "/dashboard/users", [UserController::class, "index"], "name" => "users.index"]
];
