<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Interfaces;

interface IPlugin
{
    /**
     * WordPress' activation callback.
     */
    public function activatePlugin(): void;

    /**
     * WordPress' deactivation callback.
     */
    public function deactivatePlugin(): void;
}
