<?php

use App\Controllers\HomeController;

return [
    ["GET", "/", [HomeController::class, "index"], "name" => "home"],
    ["GET", "/sign-in", [HomeController::class, "signIn"], "name" => "sign-in"],
    ["GET", "/sign-up", [HomeController::class, "signUp"], "name" => "sign-up"]
];
