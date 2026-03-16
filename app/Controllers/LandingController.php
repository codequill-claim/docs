<?php

declare(strict_types=1);

namespace App\Controllers;

use Zephyrus\Controller\Controller;
use Zephyrus\Http\Response;
use Zephyrus\Rendering\RenderResponses;
use Zephyrus\Routing\Attribute\Get;

final class LandingController extends Controller
{
    use RenderResponses;

    #[Get('/')]
    public function index(): Response
    {
        return Response::redirect('/docs');
    }

    #[Get('/404')]
    public function notFound(): Response
    {
        return $this->render('404', [
            'title' => 'Page Not Found',
        ]);
    }
}
