<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Controllers;

use Atk4\AtkWordpress\AtkWordpress;
use Atk4\AtkWordpress\Interfaces\IService;
use Atk4\AtkWordpress\Services\DashboardService;
use Atk4\AtkWordpress\Services\EnqueueService;
use Atk4\AtkWordpress\Services\MetaBoxService;
use Atk4\AtkWordpress\Services\PageService;
use Atk4\AtkWordpress\Services\PanelService;
use Atk4\AtkWordpress\Services\ShortcodeService;
use Atk4\AtkWordpress\Services\WidgetService;

class ComponentController extends AbstractController
{
    protected array $components = [];

    /** @var IService[] */
    protected array $services = [];

    public function setup(AtkWordpress $param): void
    {
        $this->addServiceType('enqueue', EnqueueService::class);
        $this->addServiceType('panel', PanelService::class);
        $this->addServiceType('metabox', MetaBoxService::class);
        $this->addServiceType('dashboard', DashboardService::class);
        $this->addServiceType('widget', WidgetService::class);
        $this->addServiceType('shortcode', ShortcodeService::class);
        $this->addServiceType('page', PageService::class);
    }

    private function addServiceType(string $type, string $service_fqcn): void
    {
        /** @var IService $service */
        $service = new $service_fqcn();
        $this->_addIntoCollection($type, $service, 'services');
        $service->register();
    }

    public function getEnqueueService(): EnqueueService
    {
        /** @var EnqueueService $enqueue */
        $enqueue = $this->getServiceByType('enqueue');

        return $enqueue;
    }

    public function getServiceByType(string $type): IService
    {
        /** @var IService $service */
        $service = $this->_getFromCollection($type, 'services');

        return $service;
    }

    public function registerComponents($type, array $components): void
    {
        $this->components[$type] = $components;
    }

    public function searchComponentByType(string $type, string $searchValue, string $searchKey = 'id')
    {
        $componentsType = $this->getComponentsByType($type);

        if ($componentsType === []) {
            // TODO throw exception?
        }

        foreach ($componentsType as $component) {
            if (($component[$searchKey] ?? 'unknown_value') === $searchValue) {
                return $component;
            }
        }

        return null; // todo throw exception?
    }

    public function getComponentsByType($type): array
    {
        return $this->components[$type] ?? [];
    }

    /**
     * Return a component from the components array
     * base on it's key value regardless of the component type.
     *
     * @param array $components
     *
     * @return array|mixed
     */
    public function searchComponentByKey(string $search)
    {
        foreach ($this->components as $keyType => $components) {
            if ($keyType === $search) {
                return $components;
            }

            if (empty($components)) {
                continue;
            }

            foreach ($components as $key => $component) {
                if ($key === $search) {
                    return $component;
                }
            }
        }

        return null;
    }

    /**
     * Get meta data value associated to a post.
     *
     * @return mixed
     */
    public function getPostMetaData(int $postID, string $postKey, bool $single = true)
    {
        return $this->getMetaboxService()->getPostMetaData($postID, $postKey, $single);
    }

    public function getMetaboxService(): MetaBoxService
    {
        /** @var MetaBoxService $metabox */
        $metabox = $this->getServiceByType('metabox');

        return $metabox;
    }

    /**
     * Save meta data associated to a post.
     *
     * @param mixed $postValue
     *
     * @return mixed
     */
    public function savePostMetaData(int $postID, string $postKey, $postValue)
    {
        return $this->getMetaboxService()->savePostMetaData($postID, $postKey, $postValue);
    }
}
