<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Controllers;

use Atk4\Ui\Form\Control;

class MetaFieldController implements \Atk4\AtkWordpress\Interfaces\IMetaField
{
    protected array $fields = [];

    /**
     * {@inheritDoc}
     */
    public function addField(string $name, Control $field, string $metaKeyName = null): void
    {
        // Add default name if not supplied.
        // adding underscore prevent Wp to display in custom field setup.
        if (!$metaKeyName) {
            $metaKeyName = '_' . $name;
        }

        $field->shortName = $metaKeyName;
        $this->fields[$name] = $field;
    }

    /**
     * {@inheritDoc}
     */
    public function getField($name): Control
    {
        return $this->fields[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
