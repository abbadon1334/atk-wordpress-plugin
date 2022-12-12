<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

class EnqueueService extends AbstractService
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', fn ($hook) => $this->enqueueAdminFiles($hook));
        add_action('wp_enqueue_scripts', fn ($hook) => $this->enqueueFrontFiles($hook));
    }

    private function enqueueAdminFiles(string $hook)
    {
        if (!is_admin()) {
            return;
        }

        $this->registerAtkAssets();

        $ctrl = $this->getComponentController();

        if ($component = $ctrl->searchComponentByType('panel', $hook, 'hook')) {
        } elseif ($hook === 'post.php') {
            // if we are here, mean that we are editing a post.
            // check it's type and see if a metabox is using this type.
            if ($postType = get_post_type($_GET['post'])) {
                $component = $ctrl->searchComponentByType('metabox', $postType, 'type');
            }
        } elseif ($hook === 'post-new.php') {
            if ($postType = @$_GET['post_type']) {
                // Check if we have a metabox that is using this post type.
                $component = $ctrl->searchComponentByType('metabox', $postType, 'type');
            } else {
                // if not post_type set, this mean that we have a regular post.
                // Check if a metabox using post.
                $component = $ctrl->searchComponentByType('metabox', 'post', 'type');
            }
        } elseif ($hook === 'index.php') {
            // if we are here mean that we are in dashboard page.
            // for now, just load atk js file if we are using dashboard.
            $component = $ctrl->getComponentsByType('dashboard');
        }

        $jsFiles = $this->getPlugin()->getConfig('enqueue/admin/js', []);
        $cssFiles = $this->getPlugin()->getConfig('enqueue/admin/css', []);

        if (isset($component)) {
            $jsFiles = array_merge($jsFiles, $component['js'] ?? []);
            $cssFiles = array_merge($cssFiles, $component['css'] ?? []);

            // Load our register atk js and css.
            foreach ($this->getPlugin()->getConfig('enqueue/atk/js') as $key => $url) {
                wp_enqueue_script($key);
            }

            foreach ($this->getPlugin()->getConfig('enqueue/atk/css') as $key => $url) {
                wp_enqueue_style($key);
            }
        }

        $this->enqueueFiles($jsFiles, $cssFiles);
    }

    public function registerAtkAssets()
    {
        $atk_js = $this->getPlugin()->getConfig('enqueue/atk/js', []);

        foreach ($atk_js as $name => $js) {
            $js['name'] = $name;
            wp_register_script(
                $js['name'],
                $this->buildPathAsset($js['src']),
                $js['deps'] ?? [],
                $js['version'] ?? false,
                $js['footer'] ?? true
            );
        }

        $atk_css = $this->getPlugin()->getConfig('enqueue/atk/css', []);

        foreach ($atk_css as $name => $css) {
            $css['name'] = $name;
            wp_register_style(
                $css['name'],
                $this->buildPathAsset($css['src']),
                $css['deps'] ?? [],
                $css['version'] ?? false,
                $css['media'] ?? 'all'
            );
        }
    }

    private function getPluginBaseUrl(): string
    {
        return $this->getPlugin()->getPluginBaseUrl();
    }

    private function buildPathAsset(string $relative_path = null)
    {
        if (empty($relative_path)) {
            return '';
        }

        return $this->getPlugin()->getPluginBaseUrl() . $relative_path;
    }

    private function enqueueFiles(array $js_files, array $css_files)
    {
        foreach ($js_files as $name => $js) {
            $js['name'] = $name;
            wp_enqueue_script(
                $js['name'],
                $this->buildPathAsset($js['src']),
                $js['deps'] ?? [],
                $js['version'] ?? false,
                $js['footer'] ?? false
            );
        }

        foreach ($css_files as $name => $css) {
            $css['name'] = $name;
            wp_enqueue_style(
                $css['name'],
                $this->buildPathAsset($css['src']),
                $css['deps'] ?? [],
                $css['version'] ?? false,
                $css['media'] ?? 'all'
            );
        }
    }

    private function enqueueFrontFiles(string $hook = null)
    {
        if (is_admin()) {
            return;
        }

        $this->registerAtkAssets();

        $this->enqueueFiles(
            $this->getPlugin()->getConfig('enqueue/front/js', []),
            $this->getPlugin()->getConfig('enqueue/front/css', []),
        );
    }

    public function enqueueShortcodeFiles(array $shortcode)
    {
        $use_atk = $shortcode['atk'] ?? false;

        if ($use_atk) {
            $this->enqueueFrontFiles();
        }

        // Load our register atk js and css.
        foreach ($this->getPlugin()->getConfig('enqueue/atk/js') as $key => $url) {
            wp_enqueue_script($key);
        }

        foreach ($this->getPlugin()->getConfig('enqueue/atk/css') as $key => $url) {
            wp_enqueue_style($key);
        }

        $this->enqueueFiles($shortcode['js'] ?? [], $shortcode['css'] ?? []);
    }
}
