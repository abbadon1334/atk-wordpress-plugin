<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models;

use Atk4\AtkWordpress\Models\Internal\WpModel;

class Term extends WpModel
{
    public string $wp_table = 'terms';

    public $idField = 'term_id';

    protected function init(): void
    {
        parent::init();

        $this->addField('name', ['system' => true]);
        $this->addField('slug', ['system' => true]);

        $taxonomy = $this->hasOne('term_taxonomy', [
            'model' => [TermTaxonomy::class],
            'theirField' => 'term_id',
        ]);

        $taxonomy->addField('taxonomy');

        $this->hasMany('relationships', [
            'model' => [TermRelationship::class],
            'theirField' => 'term_taxonomy_id',
            'ourField' => 'term_id',
        ]);
    }
}
