<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

use Atk4\AtkWordpress\Components\WidgetComponent;

class WidgetService extends AbstractService
{
    private array $widgets = [];

    public function register(): void
    {
        $widgets = $this->getPlugin()->getConfig('widget', []);

        foreach ($widgets as $key => $widget) {
            $widget['id'] = $key;
            // $widget['show_instance_in_rest'] = true;
            $this->registerWidget($key, $widget);
        }
    }

    public function registerWidget($id, $widget)
    {
        $this->widgets[$id] = $widget;

        add_action('widgets_init', function () use ($id, $widget) {
            global $wp_widget_factory;

            /** @var WidgetComponent $widget */
            $widgetComponent = new $widget['uses'](
                $id,
                $widget['title'],
                $widget['widget_ops'],
                $widget['widget_control_ops'],
            );

            register_widget($widgetComponent);

            // pre init latest widget.
            $widgetComponent->initializeWidget($id, $widget, $this->getPlugin());
        });
    }
}
