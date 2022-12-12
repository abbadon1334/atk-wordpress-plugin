<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

class DashboardService extends AbstractService
{
    private array $widgets = [];

    public function register(): void
    {
        $this->setupWidgets();

        add_action('admin_init', function () {
            $this->getComponentController()->registerComponents('dashboard', $this->widgets);
        });
    }

    private function setupWidgets()
    {
        $widgets = $this->getPlugin()->getConfig('dashboard', []);

        foreach ($widgets as $key => $widget) {
            $widget['id'] = $key;

            $this->registerWidget($key, $widget);
        }
    }

    private function registerWidget($key, $widget)
    {
        $this->widgets[$key] = $widget;

        add_action('wp_dashboard_setup', function () use ($key, $widget) {
            $callable = \Closure::fromCallable([$this->getPlugin(), 'wpDashboardExecute']);

            $configureCallback = null;
            if ($widget['configureMode']) {
                $configureCallback = function () use ($key, $widget, $callable) {
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
