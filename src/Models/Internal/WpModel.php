<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models\Internal;

use Atk4\AtkWordpress\Helpers\WP;
use Atk4\Data\Model;
use Atk4\Data\Persistence\Sql;

abstract class WpModel extends Model
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

    public function joinPostMetaValue(string $meta_key, string $fieldName, $fieldDefaults = []): WpModelJoin
    {
        /** @var Sql $this */
        $persistence = $this->getPersistence();

        $alias = 'md_' . $meta_key;

        /** @var WpModelJoin $join */
        $join = $this->join($alias, [
            'foreignTable' => WP::getDbPrefix() . 'postmeta',
            'on' => $persistence->expr($this, WP::getDbPrefix() . 'posts.ID = ' . $alias . '.post_id AND ' . $alias . '.meta_key = "' . $meta_key . '"'),
            'foreignModel' => function () use ($meta_key): WpModelPostMeta {
                $postmeta = new WpModelPostMeta($this->getPersistence());
                $postmeta->addCondition('meta_key', $meta_key);

                return $postmeta;
            },
            'foreignAlias' => $alias,
            'masterField' => 'ID',
            'foreignField' => 'post_id',
        ]);

        $join->addField($fieldName, array_merge(['actual' => 'meta_value'], $fieldDefaults));

        return $join;
    }
}
