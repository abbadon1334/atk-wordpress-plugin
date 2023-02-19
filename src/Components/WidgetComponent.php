<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Components;

use Atk4\AtkWordpress\AtkWordpress;
use Atk4\AtkWordpress\Interfaces\IWidget;

abstract class WidgetComponent extends \WP_Widget implements IWidget
{
    public $name;

    public $option_name;

    public $id_base;

    public $widget_options;

    /**
     * @var mixed
     */
    public $control_options;

    /**
     * The plugin running this widget.
     */
    public AtkWordpress $plugin;

    /**
     * The current widget configuration.
     */
    public array $widgetConfig;

    /**
     * Pre initialisation of our widget.
     * Call from the WidgetService when widget is register in WP.
     * Call directly after widget creation.
     */
    public function initializeWidget($id, array $config, AtkWordpress $plugin): void
    {
        $this->plugin = $plugin;
        $this->name = $config['title'];

        // Widget option_name in Option table that will hold the widget instance field value.
        $this->option_name = 'widget-' . $this->id_base;
        $this->widget_options = wp_parse_args($this->widget_options, $config['widget_ops']);

        $control = $config['widget_control_ops'] ?? [];
        $this->control_options = wp_parse_args($control, ['id_base' => $this->id_base]);

        // Our widget definition
        $this->widgetConfig = $config;

        // Add the id value to our widget definition.
        $this->widgetConfig['id'] = $id;
    }

    /**
     * Widget Frontend.
     *
     * The \Wp_Widget::widget() method.
     * If child class implement WidgetInterface, this method will call the onWidget method
     * passing a fully working atk view that will be echo when return by onWidget.
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance): void
    {
        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title'] ?? '');

        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $view = $this->onWidget(
            $this->plugin->newAtkAppView('widget.html', $this->widgetConfig['id']),
            $instance
        );
        $view->getApp()->run();

        echo $args['after_widget'];
    }

    /**
     * Widget Backend.
     *
     * The \Wp_Widget::form() method.
     * If child class implement WidgetInterface, this method will call the onForm method
     * passing a fully working atk view that will be echo when return by onForm.
     * Use the $view pass to onForm for adding your input field.
     *
     * @param array $instance
     */
    public function form($instance): void
    {
        $view = $this->onForm($this->plugin->newAtkAppView('widget.html', $this->widgetConfig['id']), $instance);

        $view->getApp()->run();
    }

    /**
     * Widget Backend update.
     *
     * The \Wp_Widget::update() method.
     * If child class implement WidgetInterface, this method will call the onUpdate method
     * Use the onUpdate to sanitize field entry value prior to save them to db.
     *
     * @param array $new_instance
     * @param array $old_instance
     *
     * @return array
     */
    public function update($new_instance, $old_instance): array
    {
        return $this->onUpdate($new_instance, $old_instance);
    }
}
