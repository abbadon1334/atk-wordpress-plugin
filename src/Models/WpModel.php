<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Data;

use Atk4\AtkWordpress\Helpers\WP;
use Atk4\Data\Model;

class WpModel extends Model
{
    public string $wp_table;

    /**
     * Return internal declaration of SQL Schema.
     *
     * Ex : return "
     * `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
     * `type` VARCHAR(255) NOT NULL DEFAULT '',
     * `imported` int(11) NOT NULL DEFAULT 0,
     * `date` DATE NOT NULL,
     * PRIMARY KEY  (`id`)
     * "
     */
    public function getSQLSchema(): ?string
    {
        return null;
    }

    protected function init(): void
    {
        if (!empty($this->wp_table)) {
            $this->table = WP::getDbPrefix() . $this->wp_table;
        }

        parent::init();
    }
}
