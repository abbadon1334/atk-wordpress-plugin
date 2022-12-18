<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models;

use Atk4\AtkWordpress\Models\Internal\WpModel;

class TermRelationship extends WpModel
{
    public string $wp_table = 'term_relationships';

    public $idField = false;

    protected function init(): void
    {
        parent::init();

        $this->addField('object_id', ['system' => true]);

        $this->hasMany('posts', [
            'model' => [Post::class],
            'theirField' => 'ID',
            'ourField' => 'object_id',
        ]);

        $refTerm = $this->hasOne('term_taxonomy_id', [
            'model' => [TermTaxonomy::class],
            'theirField' => 'term_taxonomy_id',
            'ourField' => 'term_taxonomy_id',
        ]);
        $refTerm->addField('taxonomy');

        $refTerm = $this->hasOne('term', [
            'model' => [Term::class],
            'theirField' => 'term_id',
            'ourField' => 'term_taxonomy_id',
        ]);
        $refTerm->addField('name');
        $refTerm->addField('slug');
    }
}
