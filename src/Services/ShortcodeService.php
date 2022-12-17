<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Services;

class ShortcodeService extends AbstractService
{
    private array $shortcodes = [];

    public function register(): void
    {
        $this->setup();

        // register shortcode component for front and ajax action.
        add_action('wp_loaded', function () {
            $this->getComponentController()->registerComponents('shortcode', $this->shortcodes);
        });
    }

    private function setup()
    {
        $shortcodes = $this->getPlugin()->getConfig('shortcode', []);

        foreach ($shortcodes as $key => $shortcode) {
            $shortcode['id'] = $key;
            $shortcode['name'] = $key;
            $shortcode['enqueued'] = false;

            $this->registerShortcode($key, $shortcode);
        }
    }

    private function registerShortcode(string $key, array $shortcode)
    {
        $this->shortcodes[$key] = $shortcode;

        add_shortcode($shortcode['name'], function ($args) use ($key, $shortcode) {

            $callable = \Closure::fromCallable([$this->getPlugin(), 'wpShortcodeExecute']);

            if (!$this->shortcodes[$key]['enqueued']) {
                $enqueue = $this->getPlugin()->getComponentController()->getEnqueueService();
                $enqueue->enqueueShortcodeFiles($shortcode);

                $this->shortcodes[$key]['enqueued'] = true;
            }

            return call_user_func_array($callable, [$shortcode, empty($args) ? [] : $args]);
        });
    }
}
