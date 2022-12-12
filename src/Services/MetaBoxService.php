<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

class MetaBoxService extends AbstractService
{
    private array $metaboxes = [];

    public function register(): void
    {
        $this->setupMetaboxes();

        add_action('admin_init', function () {
            $this->getComponentController()->registerComponents('metabox', $this->metaboxes);
        });
    }

    private function setupMetaboxes()
    {
        $metaboxes = $this->getPlugin()->getConfig('metabox', []);

        foreach ($metaboxes as $key => $metabox) {
            $metabox['id'] = $key;
            $metabox['hook'] = 'post.php';

            $this->registerMetabox($key, $metabox);
        }
    }

    private function registerMetabox(string $key, mixed $metabox)
    {
        $this->metaboxes[$key] = $metabox;

        add_action('add_meta_boxes', function () use ($key, $metabox) {
            add_meta_box(
                $key,
                $metabox['title'],
                \Closure::fromCallable([$this->getPlugin(), 'wpMetaboxExecute']),
                $metabox['screen'] ?? null,
                $metabox['context'] ?? 'advanced',
                $metabox['priority'] ?? 'default',
                $metabox['args'] ?? null
            );
        });
        // Add save post action
        add_action('save_post_' . $metabox['type'], \Closure::fromCallable([$this, 'savePostType']), 10, 3);
    }

    public function savePostType($postId, \WP_Post $post, $isUpdating)
    {
        // Add new post will trigger the save post hook and isUpdating will be false
        // We do want to catch this for saving our meta field.
        if ($isUpdating) {
            foreach ($this->metaboxes as $key => $metaBox) {
                $box = new $metaBox['uses']();
                $box->savePost($postId, $this->getPlugin());
            }
        }
    }

    public function getPostMetaData($postID, $postKey, $single = true)
    {
        return get_post_meta($postID, $postKey, $single);
    }

    public function savePostMetaData($postID, $postKey, $postValue)
    {
        return update_post_meta($postID, $postKey, $postValue);
    }
}
