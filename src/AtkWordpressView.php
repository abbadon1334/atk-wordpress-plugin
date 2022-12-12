<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress;

class AtkWordpressView extends \Atk4\Ui\View
{
    public function getPlugin(): AtkWordpress
    {
        return $this->getApp()->getPlugin();
    }

    public function getApp(): AtkWordpressApp
    {
        return parent::getApp();
    }

    public function tryGetJsActions(): array
    {
        return $this->_jsActions ?? [];
    }
}
