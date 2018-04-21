<?php

namespace Application;

use Symfony\Component\HttpFoundation\Response;

class NotFoundController
{
    public function indexAction()
    {
        return new Response(Response::$statusTexts[404], 404);
    }
}
