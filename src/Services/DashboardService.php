<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

class DashboardService extends AbstractService
{
    private array $widgets = [];

    public function register(): void
    {
        $this->setupWidgets();

        add_action('admin_init', function (): void {
            $this->getComponentController()->registerComponents('dashboard', $this->widgets);
        });
    }

    private function setupWidgets(): void
    {
        $widgets = $this->getPlugin()->getConfig('dashboard', []);

        foreach ($widgets as $key => $widget) {
            $widget['id'] = $key;

            $this->registerWidget($key, $widget);
        }
    }

    private function registerWidget($key, $widget): void
    {
        $this->widgets[$key] = $widget;

        add_action('wp_dashboard_setup', function () use ($key, $widget): void {
            $callable = \Closure::fromCallable(fn ($key, $dashboard, $configureMode = false) => $this->getPlugin()->wpDashboardExecute($key, $dashboard, $configureMode));

            $configureCallback = null;
            if ($widget['configureMode']) {
                $configureCallback = static function () use ($key, $widget, $callable): void {
                    call_user_func_array($callable, [$key, $widget, true]);
                };
            }

            wp_add_dashboard_widget(
                $key,
                $widget['title'],
                $callable,
                $configureCallback,
                [],
                $widget['context'] ?? 'normal',
                $widget['priority'] ?? 'core',
            );
        });
    }
}
