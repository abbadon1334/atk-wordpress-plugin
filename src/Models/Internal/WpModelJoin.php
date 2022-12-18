<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Models\Internal;

use Atk4\Data\Model;
use Atk4\Data\Persistence\Sql\Join;

class WpModelJoin extends Join
{
    /** @var Model|\Closure|null */
    public $foreignModel;

    protected function createFakeForeignModel(): Model
    {
        if ($this->foreignModel instanceof \Closure) {
            $this->foreignModel = ($this->foreignModel)($this->getOwner()->getPersistence());
        }

        $fakeModel = $this->foreignModel;

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

        return $fakeModel;
    }
}
