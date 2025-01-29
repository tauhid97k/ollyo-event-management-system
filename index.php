<?php

use EMS\Framework\Http\Kernel;
use EMS\Framework\Http\Request;

define("BASE_PATH", __DIR__);

require_once BASE_PATH . '/vendor/autoload.php';

$request = Request::create();

$kernel = new Kernel();

$response = $kernel->handle($request);

$response->send();
