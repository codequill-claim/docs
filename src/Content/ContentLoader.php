<?php

declare(strict_types=1);

namespace Leaf\Content;

use Leaf\Config\LeafConfig;

/**
 * Loads documentation content from markdown files and provides
 * navigation structure (sidebar, prev/next links).
 */
final class ContentLoader
{
    /** @var list<array{section: string, slug: string, title: string, url: string}> */
    private array $flatPages = [];

    /** @var array<string, array{title: string, items: list<array{title: string, url: string, section: string, slug: string}>}> */
    private array $sidebar = [];

    private bool $loaded = false;

    public function __construct(
        private readonly string $contentDirectory,
        private readonly MarkdownParser $parser,
        private readonly LeafConfig $config,
    ) {
    }

    /**
     * @return list<array{title: string, items: list<array{title: string, url: string}>}>
     */
    public function getSidebar(): array
    {
        $this->ensureLoaded();
        return array_values($this->sidebar);
    }

    public function getPage(string $section, string $slug): ?ParsedMarkdown
    {
        $path = $this->contentDirectory . '/' . $section . '/' . $slug . '.md';
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        return $this->parser->parse($content);
    }

    /**
     * @return array{title: string, url: string}|null
     */
    public function getPreviousPage(string $section, string $slug): ?array
    {
        $this->ensureLoaded();
        $url = "/{$section}/{$slug}";

        foreach ($this->flatPages as $i => $page) {
            if ($page['url'] === $url && $i > 0) {
                return [
                    'title' => $this->flatPages[$i - 1]['title'],
                    'url' => $this->flatPages[$i - 1]['url'],
                ];
            }
        }

        return null;
    }

    /**
     * @return array{title: string, url: string}|null
     */
    public function getNextPage(string $section, string $slug): ?array
    {
        $this->ensureLoaded();
        $url = "/{$section}/{$slug}";

        foreach ($this->flatPages as $i => $page) {
            if ($page['url'] === $url && isset($this->flatPages[$i + 1])) {
                return [
                    'title' => $this->flatPages[$i + 1]['title'],
                    'url' => $this->flatPages[$i + 1]['url'],
                ];
            }
        }

        return null;
    }

    /**
     * @return list<array{section: string, slug: string, title: string, url: string}>
     */
    public function getAllPages(): array
    {
        $this->ensureLoaded();
        return $this->flatPages;
    }

    public function getFirstPageUrl(): string
    {
        $this->ensureLoaded();
        return $this->flatPages[0]['url'] ?? '/getting-started/introduction';
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->flatPages = [];
        $this->sidebar = [];

        if (!is_dir($this->contentDirectory)) {
            $this->loaded = true;
            return;
        }

        $configSections = $this->config->sections;

        // If sections are configured, use that order. Otherwise, scan and sort alphabetically.
        if ($configSections !== []) {
            $sections = array_keys($configSections);
        } else {
            $sections = array_filter(
                scandir($this->contentDirectory) ?: [],
                fn ($f) => $f !== '.' && $f !== '..' && is_dir($this->contentDirectory . '/' . $f),
            );
            sort($sections);
        }

        $sectionIndex = 0;
        foreach ($sections as $section) {
            $sectionDir = $this->contentDirectory . '/' . $section;
            if (!is_dir($sectionDir)) {
                continue;
            }

            $files = glob($sectionDir . '/*.md');
            if (!$files) {
                continue;
            }

            $pages = [];
            foreach ($files as $file) {
                $slug = basename($file, '.md');
                $frontMatter = $this->parser->extractFrontMatter(file_get_contents($file));
                $pages[] = [
                    'section' => $section,
                    'slug' => $slug,
                    'title' => $frontMatter['title'] ?? $this->slugToTitle($slug),
                    'order' => $frontMatter['order'] ?? 99,
                    'url' => "/{$section}/{$slug}",
                ];
            }

            usort($pages, fn (array $a, array $b) => $a['order'] <=> $b['order']);

            $sectionLabel = $configSections[$section] ?? $this->slugToTitle($section);

            $sidebarItems = array_map(fn (array $page) => [
                'title' => $page['title'],
                'url' => $page['url'],
                'section' => $page['section'],
                'slug' => $page['slug'],
            ], $pages);

            $this->sidebar[$section] = [
                'title' => $sectionLabel,
                'items' => $sidebarItems,
            ];

            foreach ($pages as $page) {
                $this->flatPages[] = [
                    'section' => $page['section'],
                    'slug' => $page['slug'],
                    'title' => $page['title'],
                    'url' => $page['url'],
                ];
            }

            $sectionIndex++;
        }

        $this->loaded = true;
    }

    private function slugToTitle(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }
}
