<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models;

use Atk4\AtkWordpress\Helpers\WP;
use Atk4\AtkWordpress\Models\Internal\WpModelJoin;
use Atk4\AtkWordpress\Models\Internal\WpModelPostMeta;
use Atk4\Data\Persistence;

class PostModel extends Internal\WpModel
{
    // public $_defaultSeedJoin = [WpModelJoin::class]; // not working

    public string $wp_table = 'posts';

    public string $post_type = 'post';

    public string $idTable = 'ID';

    public function __construct(Persistence $persistence = null, array $defaults = [])
    {
        // $defaults['_defaultSeedJoin'] = [WpModelJoin::class];

        parent::__construct($persistence, $defaults);

        $this->_defaultSeedJoin = [WpModelJoin::class];
    }

    protected function init(): void
    {
        parent::init();

        $this->addField('post_type', ['system' => true]);
        $this->addCondition('post_type', $this->post_type);
    }

    public function joinPostMetaValue(string $meta_key, string $fieldName, $fieldDefaults = []): WpModelJoin
    {
        $alias = 'md_' . $meta_key;

        /** @var WpModelJoin $join */
        $join = $this->join($alias, [
            'foreignTable' => WP::getDbPrefix() . 'postmeta',
            'on' => $this->expr(WP::getDbPrefix() . 'posts.ID = ' . $alias . '.post_id AND ' . $alias . '.meta_key = "' . $meta_key . '"'),
            'foreignModel' => function () use ($meta_key): \Atk4\AtkWordpress\Models\Internal\WpModelPostMeta {
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
