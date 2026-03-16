<?php

declare(strict_types=1);

namespace App\Models\Core;

use App\Controllers\DocsController;
use Dotenv\Dotenv;
use Leaf\Config\LeafConfig;
use Leaf\Content\ContentLoader;
use Leaf\Content\MarkdownParser;
use Leaf\Content\SearchIndexBuilder;
use Leaf\LeafLatteExtension;
use Zephyrus\Core\ApplicationBuilder;
use Zephyrus\Core\Config\Configuration;
use Zephyrus\Http\Request;
use Zephyrus\Http\Response;
use Zephyrus\Rendering\LatteEngine;
use Zephyrus\Rendering\RenderConfig;
use Zephyrus\Routing\Exception\RouteNotFoundException;
use Zephyrus\Routing\Router;

abstract class Kernel
{
    protected Configuration $config;
    protected LeafConfig $leafConfig;
    protected LatteEngine $renderEngine;
    protected MarkdownParser $markdownParser;
    protected ContentLoader $contentLoader;
    protected SearchIndexBuilder $searchIndexBuilder;

    public function __construct()
    {
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }
        $this->boot();
    }

    public function run(): void
    {
        [$app] = $this->buildApplication();
        $request = Request::fromGlobals();
        $response = $app->handle($request);
        $response->send();
    }

    /**
     * @return array{0: \Zephyrus\Core\Application, 1: Router}
     */
    public function buildForStaticGeneration(): array
    {
        return $this->buildApplication();
    }

    public function getConfig(): Configuration
    {
        return $this->config;
    }

    public function getLeafConfig(): LeafConfig
    {
        return $this->leafConfig;
    }

    public function getRenderEngine(): LatteEngine
    {
        return $this->renderEngine;
    }

    public function getContentLoader(): ContentLoader
    {
        return $this->contentLoader;
    }

    protected function registerControllers(Router $router): Router
    {
        return $router->discoverControllers(
            namespace: 'App\\Controllers',
            directory: ROOT_DIR . '/app/Controllers',
        );
    }

    /**
     * @return array{0: \Zephyrus\Core\Application, 1: Router}
     */
    private function buildApplication(): array
    {
        $router = $this->registerControllers(new Router());

        $renderEngine = $this->renderEngine;
        $contentLoader = $this->contentLoader;
        $searchIndexBuilder = $this->searchIndexBuilder;
        $leafConfig = $this->leafConfig;

        $app = ApplicationBuilder::create()
            ->withConfiguration($this->config, basePath: ROOT_DIR)
            ->withRouter($router)
            ->withControllerFactory(function (string $class) use ($renderEngine, $contentLoader, $searchIndexBuilder, $leafConfig): object {
                if ($class === DocsController::class) {
                    $controller = new DocsController($contentLoader, $searchIndexBuilder, $leafConfig);
                } else {
                    $controller = new $class();
                }
                if (method_exists($controller, 'setRenderEngine')) {
                    $controller->setRenderEngine($renderEngine);
                }
                return $controller;
            })
            ->withExceptionHandler(RouteNotFoundException::class, function (\Throwable $e, ?Request $r) use ($renderEngine): Response {
                $html = $renderEngine->render('404', [
                    'title' => 'Page Not Found',
                ]);
                return Response::html($html, 404);
            })
            ->build();

        return [$app, $router];
    }

    private function boot(): void
    {
        Dotenv::createImmutable(ROOT_DIR)->safeLoad();

        $this->config = Configuration::fromYamlFile(ROOT_DIR . '/config.yml', [
            'render' => RenderConfig::class,
            'leaf' => LeafConfig::class,
        ]);

        /** @var LeafConfig $leafConfig */
        $leafConfig = $this->config->section('leaf');
        $this->leafConfig = $leafConfig ?? LeafConfig::fromArray([]);

        /** @var RenderConfig $renderConfig */
        $renderConfig = $this->config->section('render') ?? RenderConfig::fromArray([]);
        $this->renderEngine = $renderConfig->createEngine(ROOT_DIR);
        $this->renderEngine->addExtension(new LeafLatteExtension($this->leafConfig));

        $contentDir = ROOT_DIR . '/' . ltrim($this->leafConfig->contentPath, '/');
        $this->markdownParser = new MarkdownParser();
        $this->contentLoader = new ContentLoader($contentDir, $this->markdownParser, $this->leafConfig);
        $this->searchIndexBuilder = new SearchIndexBuilder($contentDir, $this->markdownParser);
    }
}
