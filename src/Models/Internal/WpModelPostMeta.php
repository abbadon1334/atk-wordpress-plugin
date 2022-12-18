<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models\Internal;

class WpModelPostMeta extends WpModel
{
    public string $wp_table = 'postmeta';

    public $idField = 'meta_id';

    protected function init(): void
    {
        parent::init();

        $this->addField('meta_key', ['system' => true]);
        $this->addField('post_id', ['system' => true]);
    }
}
