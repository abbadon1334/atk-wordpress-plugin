<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models;

use Atk4\AtkWordpress\Helpers\WP;
use Atk4\AtkWordpress\Models\Internal\WpModelJoin;
use Atk4\AtkWordpress\Models\Internal\WpModelPostMeta;
use Atk4\Data\Persistence;

class Post extends Internal\WpModel
{
    // public $_defaultSeedJoin = [WpModelJoin::class]; // not working

    public string $wp_table = 'posts';

    public string $post_type = 'post';

    public string $idTable = 'ID';

    public function __construct(Persistence $persistence = null, array $defaults = [])
    {
        // $defaults['_defaultSeedJoin'] = [WpModelJoin::class];

        parent::__construct($persistence, $defaults);

        $this->setDefaults($defaults);

        $this->_defaultSeedJoin = [WpModelJoin::class];
    }

    protected function init(): void
    {
        parent::init();

        $this->addField('post_type', ['system' => true]);
        $this->addCondition('post_type', $this->post_type);
    }

    public function withTaxonomy(string $taxonomy)
    {
        return $this->hasMany($taxonomy, [
            'model' => function () use ($taxonomy): \Atk4\AtkWordpress\Models\TermRelationship {
                $model = new TermRelationship($this->getPersistence());
                $model->addCondition('taxonomy', $taxonomy);

                return $model;
            },
            'ourField' => 'ID',
            'theirField' => 'object_id',
        ]);
    }
}
