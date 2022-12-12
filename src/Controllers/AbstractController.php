<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Controllers;

use Atk4\AtkWordpress\AtkWordpress;
use Atk4\AtkWordpress\Interfaces\IController;
use Atk4\Core\CollectionTrait;
use Atk4\Core\InitializerTrait;
use Atk4\Core\TrackableTrait;

abstract class AbstractController implements IController
{
    use CollectionTrait;
    use InitializerTrait;
    use TrackableTrait;

    public function getPlugin(): AtkWordpress
    {
        /** @var AtkWordpress $owner */
        $owner = $this->getOwner();

        return $owner;
    }
}
