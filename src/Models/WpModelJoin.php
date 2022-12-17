<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models;

use Atk4\AtkWordpress\Helpers\WP;
use Atk4\Data\Model;
use Atk4\Data\Persistence\Sql\Join;

class WpModelJoin extends Join
{
    public $foreignTableIdField = 'id';
    public $foreignTableIdFieldType = 'integer';

    protected function createFakeForeignModel(): Model
    {
        $fakeModel = new Model($this->getOwner()->getPersistence(), [
            'table' => $this->foreignTable,
            'idField' => $this->foreignTableIdField,
        ]);

        foreach ($this->getOwner()->getFields() as $ownerField) {
            if ($ownerField->hasJoin() && $ownerField->getJoin()->shortName === $this->shortName) {
                $ownerFieldPersistenceName = $ownerField->getPersistenceName();
                if ($ownerFieldPersistenceName !== $fakeModel->idField && $ownerFieldPersistenceName !== $this->foreignField) {
                    $fakeModel->addField($ownerFieldPersistenceName, [
                        'type' => $ownerField->type,
                    ]);
                }
            }
        }
        if ($fakeModel->idField !== $this->foreignField && $this->foreignField !== null) {
            $fakeModel->addField($this->foreignField, ['type' => $this->foreignTableIdFieldType]);
        }

        return $fakeModel;
    }
}
