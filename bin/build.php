<?php

/**
 * Static site build entry point.
 *
 * Usage: composer build
 *   or:  php bin/build.php
 *
 * Boots the Zephyrus application, discovers all routes, dispatches each
 * through the full middleware/controller stack, and writes the rendered HTML
 * to the configured output directory.
 */

declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));

require ROOT_DIR . '/vendor/autoload.php';

use App\Models\Core\Application;
use Leaf\StaticSiteBuilder;
use Leaf\Content\SearchIndexBuilder;
use Leaf\Content\MarkdownParser;

echo "Building static site..." . PHP_EOL;

$app = new Application();
[$application, $router] = $app->buildForStaticGeneration();

$leafConfig = $app->getLeafConfig();
$outputDir = ROOT_DIR . '/' . ltrim($leafConfig->outputPath, '/');

$builder = new StaticSiteBuilder($application, $router);
$builder->setOutputDirectory($outputDir);
$builder->setPublicDirectory(ROOT_DIR . '/public');
$builder->setBaseUrl('http://localhost');

// Add explicit paths for parameterized routes (docs pages).
$contentDir = ROOT_DIR . '/' . ltrim($leafConfig->contentPath, '/');
$docPaths = [];
if (is_dir($contentDir)) {
    $sections = array_filter(
        scandir($contentDir),
        fn ($f) => $f !== '.' && $f !== '..' && is_dir($contentDir . '/' . $f),
    );
    foreach ($sections as $section) {
        $sectionDir = $contentDir . '/' . $section;
        $files = glob($sectionDir . '/*.md');
        foreach ($files as $file) {
            $slug = basename($file, '.md');
            $path = "/{$section}/{$slug}";
            $builder->addPath($path);
            $docPaths[] = $path;
        }
    }
}

// Exclude the search index JSON and / redirect from static page rendering.
$builder->excludePatterns(['#^/search\.json$#', '#^/$#']);

$result = $builder->build();

echo $result->summary() . PHP_EOL;

if (!$result->isSuccessful()) {
    echo PHP_EOL . "Errors:" . PHP_EOL;
    foreach ($result->errors as $error) {
        echo "  - {$error}" . PHP_EOL;
    }
    exit(1);
}

// Generate the search index JSON separately.
echo "Generating search index..." . PHP_EOL;
$searchIndexBuilder = new SearchIndexBuilder(
    $contentDir,
    new MarkdownParser(),
    $leafConfig->baseUrl,
);
$index = $searchIndexBuilder->build();
$searchJsonPath = $outputDir . '/search.json';
file_put_contents($searchJsonPath, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  -> " . count($index) . " pages indexed" . PHP_EOL;

// Generate a redirect index.html for / -> first doc page.
$firstPageUrl = $app->getContentLoader()->getFirstPageUrl() . '/';
$rootRedirectHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url={$firstPageUrl}">
    <title>Redirecting...</title>
</head>
<body>
    <p>Redirecting to <a href="{$firstPageUrl}">documentation</a>...</p>
</body>
</html>
HTML;
file_put_contents($outputDir . '/index.html', $rootRedirectHtml);
echo "  -> /index.html redirect created" . PHP_EOL;

// Move /404/index.html to /404.html (GitHub Pages convention).
$notFoundSource = $outputDir . '/404/index.html';
$notFoundTarget = $outputDir . '/404.html';
if (is_file($notFoundSource)) {
    rename($notFoundSource, $notFoundTarget);
    @rmdir($outputDir . '/404');
    echo "  -> 404.html created (GitHub Pages)" . PHP_EOL;
}

echo PHP_EOL . "Build complete!" . PHP_EOL;
