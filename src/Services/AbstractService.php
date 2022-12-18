<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

use Atk4\AtkWordpress\AtkWordpress;
use Atk4\AtkWordpress\Controllers\ComponentController;
use Atk4\AtkWordpress\Interfaces\IService;
use Atk4\Core\InitializerTrait;
use Atk4\Core\NameTrait;
use Atk4\Core\TrackableTrait;

abstract class AbstractService implements IService
{
    use InitializerTrait;
    use NameTrait;
    use TrackableTrait;

    public function getPlugin(): AtkWordpress
    {
        return $this->getComponentController()->getPlugin();
    }

    public function getComponentController(): ComponentController
    {
        /** @var ComponentController $cc */
        $cc = $this->getOwner();

        return $cc;
    }
}
