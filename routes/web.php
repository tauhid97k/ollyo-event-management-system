<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\HomeController;
use App\Controllers\UserController;

return [
    ["GET", "/", [HomeController::class, "index"], "name" => "home"],
    ["GET", "/sign-in", [AuthController::class, "signInView"], "name" => "sign-in.view"],
    ["GET", "/sign-up", [AuthController::class, "signUpView"], "name" => "sign-up.view"],
    ["GET", "/dashboard", [DashboardController::class, "index"], "name" => "dashboard"],
    ["GET", "/dashboard/events", [EventController::class, "index"], "name" => "events.index"],
    ["GET", "/dashboard/users", [UserController::class, "index"], "name" => "users.index"]
];
