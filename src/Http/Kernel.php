<?php

namespace EMS\Framework\Http;

class Kernel
{
    public function handle(Request $request): Response
    {
        $content = "<h1>Hello World PHP</h1>";

        return new Response($content);
    }
}
