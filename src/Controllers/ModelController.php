<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Controllers;

use Atk4\AtkWordpress\Helpers\WP;
use Atk4\AtkWordpress\Models\Internal\WpModel;

class ModelController extends AbstractController
{
    /**
     * @var WpModel[]
     */
    private array $models = [];

    public function addModelAsSeed(string $fqcn, array $defaults): WpModel
    {
        if (!isset($defaults['persistence'])) {
            $defaults['persistence'] = $this->getPlugin()->getDbConnection();
        }

        /** @var WpModel $model */
        $model = new $fqcn($defaults);

        return $model;
    }

    public function addModel(WpModel $model, string $override_key = null): WpModel
    {
        $key = $override_key ?? get_class($model);

        if (!array_key_exists($key, $this->models)) {
            $this->models[$key] = $model;
        }

        return $model;
    }

    public function updateDBSqlSchema(): void
    {
        // process models statement for dbDelta
        foreach ($this->models as $model) {
            $schema = $model->getSQLSchema();

            if (empty($schema)) {
                continue;
            }

            $stmt = sprintf(
                // @lang mysql
                'CREATE TABLE `%s` (%s%s%s)%sCOLLATE {%s}',
                $model->table,
                \PHP_EOL,
                $model->getSQLSchema(),
                \PHP_EOL,
                \PHP_EOL,
                Wp::getDbCharsetCollate()
            );

            dbDelta($stmt);
        }
    }

    public function getModel(string $key): ?WpModel
    {
        return clone $this->models[$key] ?? null;
    }
}
