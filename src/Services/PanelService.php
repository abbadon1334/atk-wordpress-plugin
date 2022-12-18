<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

class PanelService extends AbstractService
{
    public array $panels = [];

    public function register(): void
    {
        $this->setupPanels();

        // register panel components with ctrl ounce fully loaded and with hook setting in place.
        add_action('admin_init', function (): void {
            $this->getComponentController()->registerComponents('panel', $this->panels);
        });
    }

    private function setupPanels(): void
    {
        $panels = $this->getPlugin()->getConfig('panel', []);

        foreach ($panels as $key => &$panel) {
            $panel['id'] = $key;
            $panel['slug'] = $key;
            $this->panels[$key] = $panel;
        }

        add_action('admin_menu', function () use ($panels): void {
            foreach ($panels as $key => $panel) {
                $type = $panel['type'] ?? '';
                $parent = $panel['parent'] ?? '';

                if (!empty($parent)) {
                    continue;
                }

                if ($type === 'wp-sub-panel') {
                    $this->registerWpSubPanel($key, $panel);

                    continue;
                }

                $this->registerPanel($key, $panel, $panels);
            }
        });
    }

    private function registerWpSubPanel(int $key, $panel): void
    {
        $executor = \Closure::fromCallable(fn () => $this->getPlugin()->wpPanelExecute());

        $hook = add_submenu_page(
            $panel['parent'],
            $panel['page'],
            $panel['menu'],
            $panel['capabilities'],
            $panel['slug'],
            $executor
        );

        $this->panels[$key]['hook'] = $hook;
    }

    private function registerPanel(string $key, $parent, $panels): void
    {
        $executor = \Closure::fromCallable(fn () => $this->getPlugin()->wpPanelExecute());

        $hook = add_menu_page(
            $parent['page'],
            $parent['menu'],
            $parent['capabilities'],
            $parent['slug'],
            $executor,
            $parent['icon'] ?? '',
            $parent['position'] ?? null
        );

        $this->panels[$key]['hook'] = $hook;

        foreach ($panels as $sub_key => $sub) {
            $sub_parent = $sub['parent'] ?? '';

            if ($sub_parent !== $key) {
                continue;
            }

            $hook = add_submenu_page(
                $parent['slug'],
                $sub['page'],
                $sub['menu'],
                $sub['capabilities'],
                $sub['slug'],
                $executor,
                $sub['position'] ?? null
            );

            $this->panels[$sub_key]['hook'] = $hook;
        }
    }
}
