<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress;

use Atk4\AtkWordpress\Helpers\WP;
use Atk4\Ui\App;
use Atk4\Ui\HtmlTemplate;
use Atk4\Ui\Persistence\Ui;
use Atk4\Ui\View;

class AtkWordpressApp extends \Atk4\Ui\App
{
    public const HOOK_BEFORE_OUTPUT = App::class . '@beforeOutput';
    /**
     * The plugin running this app.
     */
    public ?AtkWordpress $plugin;

    /**
     * The html produce by this app.
     */
    public AtkWordpressView $wpHtml;

    /**
     * The default directory name of atk template.
     */
    public string $skin = 'semantic-ui';

    public function getPlugin(): AtkWordpress
    {
        return $this->plugin;
    }

    /**
     * atk view initialisation.
     */
    protected function init(): void
    {
        parent::init();
    }

    /**
     * AtkWpApp constructor.
     */
    public function __construct(AtkWordpress $plugin = null, UI $ui_persistence = null)
    {
        $this->plugin = $plugin;
        if (!isset($ui_persistence)) {
            $this->uiPersistence = new Ui();
        } else {
            $this->uiPersistence = $ui_persistence;
        }
    }

    public function initWpLayout(AtkWordpressView $view, $layout, $name)
    {
        $this->wpHtml = new AtkWordpressView(['defaultTemplate' => $layout, 'name' => $name]);
        $this->wpHtml->setApp($this);
        $this->wpHtml->invokeInit();
        $this->wpHtml->addClass('fluid');
        $this->wpHtml->addStyle('padding-right', '10px');

        $this->wpHtml->add($view);

        return $view;
    }

    /**
     * Runs app and echo rendered template.
     */
    public function execute(bool $isAjax = false)
    {
        echo $this->render($isAjax);
    }

    /**
     * Take care of rendering views.
     *
     * @return mixed
     */
    public function render($isAjax)
    {
        $this->hook(App::HOOK_BEFORE_RENDER);
        $this->isRendering = true;
        $this->wpHtml->renderAll();
        $this->wpHtml->template->dangerouslyAppendHtml('HEAD', $this->getJsReady($this->wpHtml));
        $this->isRendering = false;
        $this->hook(self::HOOK_BEFORE_OUTPUT);

        return $this->wpHtml->template->renderToHtml();
    }

    /**
     * Return the db connection run by this plugin.
     *
     * @return mixed
     */
    public function getDbConnection()
    {
        return $this->plugin->getDbConnection();
    }

    /**
     * Return url.
     *
     * @param array $page
     * @param bool  $needRequestUri
     * @param array $extraArgs
     */
    public function url($page = [], $needRequestUri = false, $extraArgs = []): string
    {
        if (is_string($page)) {
            return $page;
        }

        $requested_wp_page = $_REQUEST['page'] ?? null;

        if ($requested_wp_page) {
            $extraArgs['page'] = $requested_wp_page;
        }

        return parent::url($page, $needRequestUri, $extraArgs);
    }

    /**
     * Return url.
     *
     * @param array $page
     * @param bool  $useRequestUrl
     * @param array $extraRequestUrlArgs
     */
    public function jsUrl($page = [], $useRequestUrl = false, $extraRequestUrlArgs = []): string
    {
        if (is_string($page)) {
            return $page;
        }

        // if running front end set url for ajax.
        $this->page = (!WP::isAdmin() ? WP::getBaseAdminUrl() : '') . 'admin-ajax';

        if (is_array($page) && !isset($page[0])) {
            $page[0] = $this->page;
        }

        $extraRequestUrlArgs['action'] = $this->plugin->getPluginName();
        $extraRequestUrlArgs['atkwp'] = $this->plugin->getActivatedComponent('id');

        if ($this->plugin->getComponentCount() > 0) {
            $extraRequestUrlArgs['atkwp-count'] = $this->plugin->getComponentCount();
        }

        if ($this->getConfig('plugin/use_nounce', false)) {
            $extraRequestUrlArgs['_ajax_nonce'] = WP::createWpNounce($this->plugin->getPluginName());
        }

        // Page argument may be forced by using $config['plugin']['use_page_argument'] = true in config-default.php.
        if (isset($extraRequestUrlArgs['path']) || $this->getConfig('plugin/use_page_argument', false)) {
            $extraRequestUrlArgs['page'] = $this->getPlugin()->getActivatedComponent('slug');
        }

        return parent::jsUrl($page, $useRequestUrl, $extraRequestUrlArgs);
    }

    private function getConfig($path, $default = null)
    {
        return $this->getPlugin()->getConfig($path, $default);
    }

    private function buildUrl($wpPage, $page, $extras): string
    {
        $result = $extras;
        $sticky = $this->stickyGetArguments;
        $this->page = $wpPage;

        if (!isset($page[0])) {
            $page[0] = $this->page;

            if (!empty($sticky)) {
                foreach ($sticky as $key => $val) {
                    if ($val === true) {
                        if (isset($_GET[$key])) {
                            $val = $_GET[$key];
                        } else {
                            continue;
                        }
                    }
                    if (!isset($result[$key])) {
                        $result[$key] = $val;
                    }
                }
            }
        }

        foreach ($page as $arg => $val) {
            if ($arg === 0) {
                continue;
            }

            if ($val === null || $val === false) {
                unset($result[$arg]);
            } else {
                $result[$arg] = $val;
            }
        }

        $page = $page[0];

        $url = $page ? $page . '.php' : '';

        $args = http_build_query($result);

        if ($args) {
            $url = $url . '?' . $args;
        }

        return $url;
    }

    /**
     * Return javascript action.
     */
    public function getJsReady(AtkWordpressView $app_view): string
    {
        $actions = [];

        foreach ($app_view->tryGetJsActions() as $eventActions) {
            foreach ($eventActions as $action) {
                $actions[] = $action;
            }
        }

        if (!$actions) {
            return '';
        }

        $actions['indent'] = '';
        $ready = new \Atk4\Ui\JsFunction(['$'], $actions);

        return "<script>jQuery(document).ready({$ready->jsRender()})</script>";
    }

    public function loadTemplate($filename): ?HtmlTemplate
    {
        $template = new HtmlTemplate();
        $template->setApp($this);

        foreach ($this->getPlugin()->getTemplateLocation($filename) as $path) {
            try {
                return $template->loadFromFile($path);
            } catch (\Throwable $t) {
            }
        }

        return null;
    }

    /*
    public function terminate($output = null)
    {
        if ($output !== null) {
            if ($this->isJsonRequest()) {
                if (is_string($output)) {
                    $decode = json_decode($output, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $decode['modals'] = $this->getRenderedModals();
                        $output = $decode;
                    }
                } elseif (is_array($output)) {
                    $output['modals'] = $this->getRenderedModals();
                }
                $this->outputResponseJSON($output);
            } elseif (isset($_GET['__atk_tab'])) {
                // ugly hack for TABS
                // because fomantic ui tab only deal with html and not JSON
                // we need to hack output to include app modal.
                $keys = null;
                $remove_function = '';
                foreach ($this->getRenderedModals() as $key => $modal) {
                    // add modal rendering to output
                    $keys[] = '#'.$key;
                    $output['atkjs'] = $output['atkjs'].';'.$modal['js'];
                    $output['html'] = $output['html'].$modal['html'];
                }
                if ($keys) {
                    $ids = implode(',', $keys);
                    $remove_function = "$('.ui.dimmer.modals.page').find('${ids}').remove();";
                }
                $output = '<script>var $=jQuery.noConflict();(function() {'.$remove_function.$output['atkjs'].'})($);</script>'.$output['html'];
                $this->outputResponseHtml($output);
            } else {
                $this->outputResponseHTML($output);
            }
        }

        $this->run_called = true; // prevent shutdown function from triggering.
        $this->callExit();
    }
    */
}
