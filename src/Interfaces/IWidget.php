<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Interfaces;

use Atk4\AtkWordpress\AtkWordpressView;

interface IWidget
{
    /**
     * Called by the Widget component class on WP_Widget::widget() method.
     * This method will be called prior to echo the html for this view, allowing
     * developer to add other element to this view.
     *
     * @param AtkWordpressView $view The atk view
     *
     * @return AtkWordpressView $view the view to echo in widget() method
     */
    public function onWidget(AtkWordpressView $view, array $instance): AtkWordpressView;

    /**
     * Called by the Widget component class on WP_Widget::form() method.
     * This method will be called prior to echo the html for this view, allowing
     * developer to add field input to the view.
     *
     * @return AtkWordpressView $view the view to echo in form() method
     */
    public function onForm(AtkWordpressView $view, array $instance): AtkWordpressView;

    /**
     * Called by the Widget component class on WP_Widget::update() method.
     * This method is called prior to update the instance value in db.
     *
     * @param array $newInstance the instance array with new value from user
     * @param array $oldInstance the instance with previous saved db value
     *
     * @return array the instance with value to save in db
     */
    public function onUpdate(array $newInstance, array $oldInstance): array;
}
