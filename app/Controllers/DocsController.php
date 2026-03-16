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

    #[Get('/docs')]
    public function index(): Response
    {
        $url = $this->contentLoader->getFirstPageUrl();
        return Response::redirect($url);
    }

    #[Get('/docs/{section}/{slug}')]
    public function show(string $section, string $slug): Response
    {
        $page = $this->contentLoader->getPage($section, $slug);

        if ($page === null) {
            return $this->render('404', [
                'title' => 'Page Not Found',
            ], 404);
        }

        return $this->render('docs/page', [
            'title' => $page->meta('title', ucfirst(str_replace('-', ' ', $slug))),
            'section' => $section,
            'slug' => $slug,
            'content' => $page->html,
            'sidebar' => $this->contentLoader->getSidebar(),
            'toc' => $page->toc,
            'prevPage' => $this->contentLoader->getPreviousPage($section, $slug),
            'nextPage' => $this->contentLoader->getNextPage($section, $slug),
        ]);
    }

    #[Get('/docs/search.json')]
    public function searchIndex(): Response
    {
        $index = $this->searchIndexBuilder->build();
        return Response::json($index);
    }
}
