<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress;

use Atk4\Ui\View;

class AtkWordpressView extends View
{
    public function getPlugin(): AtkWordpress
    {
        return $this->getApp()->getPlugin();
    }

    public function tryGetJsActions(): array
    {
        return $this->_jsActions ?? [];
    }
}
