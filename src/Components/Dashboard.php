<?php

namespace Atk4\AtkWordpress\Components;

class Dashboard extends AbstractComponent
{
    /**
     * Whether this dashboard is running under configuration mode or not.
     * This is automatically set by Plugin depending on the dashboard mode.
     *
     * @var bool
     */
    public $configureMode = false;
}