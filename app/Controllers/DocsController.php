<?php

declare(strict_types=1);

namespace App\Controllers;

use Leaf\Config\LeafConfig;
use Leaf\Content\ContentLoader;
use Leaf\Content\SearchIndexBuilder;
use Zephyrus\Controller\Controller;
use Zephyrus\Http\Response;
use Zephyrus\Rendering\RenderResponses;
use Zephyrus\Routing\Attribute\Get;

final class DocsController extends Controller
{
    use RenderResponses;

    public function __construct(
        private readonly ContentLoader $contentLoader,
        private readonly SearchIndexBuilder $searchIndexBuilder,
        private readonly LeafConfig $leafConfig,
    ) {
    }

    #[Get('/')]
    public function index(): Response
    {
        $url = $this->contentLoader->getFirstPageUrl();
        return Response::redirect($url);
    }

    #[Get('/{section}/{slug}')]
    public function show(string $section, string $slug): Response
    {
        $page = $this->contentLoader->getPage($section, $slug);

        if ($page === null) {
            return $this->render('404', [
                'title' => 'Page Not Found',
            ], 404);
        }

        $description = $page->meta('description', '');
        if ($description === '') {
            $plaintext = strip_tags($page->html);
            $plaintext = preg_replace('/\s+/', ' ', trim($plaintext));
            $description = mb_strlen($plaintext) > 160
                ? mb_substr($plaintext, 0, 157) . '...'
                : $plaintext;
        }

        return $this->render('docs/page', [
            'title' => $page->meta('title', ucfirst(str_replace('-', ' ', $slug))),
            'pageDescription' => $description,
            'section' => $section,
            'slug' => $slug,
            'content' => $page->html,
            'sidebar' => $this->contentLoader->getSidebar(),
            'toc' => $page->toc,
            'prevPage' => $this->contentLoader->getPreviousPage($section, $slug),
            'nextPage' => $this->contentLoader->getNextPage($section, $slug),
        ]);
    }

    #[Get('/search.json')]
    public function searchIndex(): Response
    {
        $index = $this->searchIndexBuilder->build();
        return Response::json($index);
    }

    #[Get('/404')]
    public function notFound(): Response
    {
        return $this->render('404', [
            'title' => 'Page Not Found',
        ]);
    }
}
