<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Interfaces;

use Atk4\Ui\Form\Control;
use Atk4\Ui\View;

interface IMetaField
{
    /**
     * AddFields to a Generic field object container, usually an array.
     *
     * @param string $name        the name of the field
     * @param View   $field       the atk field instance
     * @param string $metaKeyName metaKey name for your field in WP db.
     *
     * Note: using '_' in front of your meta key name, ex: _fieldName will
     * result in WP hiding the meta field in WP custom meta field box.
     */
    public function addField(string $name, View $field, string $metaKeyName);

    /**
     * Retrieve field from container with Generic field object.
     *
     * @param $name //the name of the field to retreive
     *
     * // return View FormField
     */
    public function getField(string $name): Control;

    /**
     * Retrieve all fields from a Generic fields container.
     */
    public function getFields(): array;
}
