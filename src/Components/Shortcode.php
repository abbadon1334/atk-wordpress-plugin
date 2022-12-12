<?php

namespace Atk4\AtkWordpress\Components;

class Shortcode extends AbstractComponent
{
    /*
     * The attribute define in shortcode code that can be use when setting the view.
     * ex: setting a shortcode in a page like this: [myshortcode title="myTitle" class="myClass"]
     * then when view is create the args property will contains array with attribute name and value.
     *   - $args = ['title' => 'myTitle', 'class' => 'myClass']
     */
    public array $args = [];
}