<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Components;

use Atk4\AtkWordpress\AtkWordpressView;

abstract class AbstractComponent extends AtkWordpressView
{
    public $defaultTemplate = 'component.html';
}
