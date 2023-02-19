<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress;

use Atk4\Ui\View;

class AtkWordpressView extends View
{
    public function getApp(): AtkWordpressApp
    {
        /** @var AtkWordpressApp $app */
        $app = parent::getApp();

        return $app;
    }

    public function getPlugin(): AtkWordpress
    {
        return $this->getApp()->getPlugin();
    }

    // TODO Remove??
    public function tryGetJsActions(): array
    {
        return $this->_jsActions ?? [];
    }
}
