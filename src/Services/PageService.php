<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

class PageService extends AbstractService
{
    private array $pages = [];

    public function register(): void
    {
        $this->setupWidgets();

        $this->getComponentController()->registerComponents('page', $this->pages);
    }

    private function setupWidgets(): void
    {
        $pages = $this->getPlugin()->getConfig('page', []);

        foreach ($pages as $key => $page) {
            $page['id'] = $key;

            $this->registerPage($key, $page);
        }
    }

    private function registerPage($key, $page): void
    {
        $this->pages[$key] = $page;

        add_filter('theme_page_templates', static fn ($templates) => array_merge($templates, [
            $page['id'] => __($page['title'], $page['domain'] ?? 'text-domain'),
        ]));

        add_shortcode('page-active', function ($args) use ($key, $page) {
            if (!is_array($args)) {
                $args = [$args];
            }

            $meta = get_post_meta(get_the_ID());

            if ($meta['_wp_page_template'][0] === $key) {
                return $this->getPlugin()->wpPageExecute($page, $args);
            }

            return 'ERROR';
        });

        add_filter('template_include', function ($template) use ($page) {
            if (!is_page()) {
                return $template;
            }

            $meta = get_post_meta(get_the_ID());

            if (empty($meta['_wp_page_template'][0])) {
                return $template;
            }

            if ($meta['_wp_page_template'][0] === $template) {
                return $template;
            }

            if ($meta['_wp_page_template'][0] === 'default') {
                return $template;
            }

            return empty($page['file'])
                ? $this->getPlugin()->getPluginBasePath() . '/vendor/abbadon1334/atk-wordpress-plugin/resources/wordpress-template.php'
                : $template;
        }, 99);
    }
}
