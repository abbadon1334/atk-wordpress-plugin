<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models;

use Atk4\AtkWordpress\Models\Internal\WpModel;

class TermTaxonomy extends WpModel
{
    public string $wp_table = 'term_taxonomy';

    public $idField = 'term_taxonomy_id';

    protected function init(): void
    {
        parent::init();

        $this->addField('taxonomy', ['system' => true]);

        $this->hasOne('term_id', [
            'model' => [Term::class],
            'theirField' => 'term_id',
            'system' => true,
        ]);

        $this->hasOne('parent', [
            'model' => [Term::class],
            'theirField' => 'term_id',
            'system' => true,
        ]);
    }
}
