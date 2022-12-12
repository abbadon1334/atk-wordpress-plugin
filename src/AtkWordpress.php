<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress;

use Atk4\AtkWordpress\Controllers\ComponentController;
use Atk4\AtkWordpress\Controllers\ModelController;
use Atk4\AtkWordpress\Interfaces\IMetaboxField;
use Atk4\AtkWordpress\Interfaces\IPlugin;
use Atk4\Core\CollectionTrait;
use Atk4\Core\ConfigTrait;
use Atk4\Core\Factory;
use Atk4\Data\Persistence\Sql;
use Atk4\Ui\Message;
use Atk4\Ui\Text;

abstract class AtkWordpress implements IPlugin
{
    use CollectionTrait;
    use ConfigTrait {
        readConfig as private loadConfigFromFolder;
    }

    private Sql    $dbConnection;
    private string $pluginName;
    private array  $controllers = [];
    private ?array $activatedComponent = null;
    private int    $componentCount = 0;

    private string          $pluginBaseUrl;
    private AtkWordpressApp $atkApp;
    private string $pluginBasePath;
    private string $pluginAtkTemplatePath;
    private bool   $ajaxMode = false;

    public string $defaultLayout = 'layout.html';
    private array $template_paths = [];

    public function __construct(string $plugin_name)
    {
        $this->setDbConnection();
        $this->_addIntoCollection(ModelController::class, Factory::factory([ModelController::class]), 'controllers');
        $this->_addIntoCollection(ComponentController::class, Factory::factory([ComponentController::class]), 'controllers');
        $this->pluginName = $plugin_name;
    }

    public function loadConfigFromFolder($path, $type = 'php')
    {
        $this->readConfig(glob($path . '/*'), $type);
    }

    public function getModelController(): ModelController
    {
        /** @var ModelController $ctrl */
        $ctrl = $this->_getFromCollection(ModelController::class, 'controllers');

        return $ctrl;
    }

    public function getComponentController(): ComponentController
    {
        /** @var ComponentController $ctrl */
        $ctrl = $this->_getFromCollection(ComponentController::class, 'controllers');

        return $ctrl;
    }

    private function setDbConnection(): void
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        $this->dbConnection = new SQL($dsn, DB_USER, DB_PASSWORD);
    }

    public function getDbConnection(): Sql
    {
        return $this->dbConnection;
    }

    public function boot(string $filePath)
    {
        $this->pluginBasePath = plugin_dir_path($filePath);
        $this->pluginBaseUrl = plugin_dir_url($filePath);

        $this->template_paths = [];

        foreach ($this->getConfig('plugin/template_locations') as $path) {
            $this->template_paths[] = trailingslashit($this->pluginBasePath . $path);
        }

        $this->initApp();
        $this->init();

        // setup plugin activation / deactivation hook.
        register_activation_hook($filePath, [$this, 'activatePlugin']);
        register_deactivation_hook($filePath, [$this, 'deactivatePlugin']);

        $this->getComponentController()->setup($this);

        // register ajax action for this plugin
        add_action("wp_ajax_{$this->pluginName}", [$this, 'wpAjaxExecute']);

        if ($this->getConfig('plugin/use_ajax_front', false)) {
            // enable Wp ajax front end action.
            add_action("wp_ajax_nopriv_{$this->pluginName}", [$this, 'wpAjaxExecute']);
        }
    }

    public function wpMetaBoxExecute(\WP_Post $post, array $param)
    {
        // set the view to output.
        $this->activatedComponent = $this->getComponentController()->searchComponentByType('metabox', $param['id']);

        try {
            $view = new $this->activatedComponent['uses'](['args' => $param['args']]);
            /** @var IMetaboxField $metaBox */
            $metaBox = $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName);
            $metaBox->setFieldInput($post->ID, $this->getComponentController());
            $this->atkApp->execute();
        } catch (\Throwable $e) {
            $this->caughtException($e);
        }
    }

    public function wpShortcodeExecute(array $shortcode, array $args)
    {
        $this->activatedComponent = $shortcode;
        ++$this->componentCount;

        try {
            $view = new $this->activatedComponent['uses'](['args' => $args]);
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName . '-' . $this->componentCount);

            return $this->atkApp->render(false);
        } catch (\Throwable $e) {
            $this->caughtException($e);
        }
    }

    public function wpDashboardExecute($key, $dashboard, $configureMode = false)
    {
        $componentCtrl = $this->getComponentController();

        $this->activatedComponent = $componentCtrl->searchComponentByType('dashboard', $dashboard['id']);

        try {
            $view = new $this->activatedComponent['uses'](['configureMode' => $configureMode]);
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName);
            $this->atkApp->execute();
        } catch (\Throwable $e) {
            $this->caughtException($e);
        }
    }

    public function wpPanelExecute()
    {
        global $hook_suffix;

        $componentCtrl = $this->getComponentController();

        $this->activatedComponent = $componentCtrl->searchComponentByType('panel', $hook_suffix, 'hook');

        try {
            $view = new $this->activatedComponent['uses']();
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName);
            $this->atkApp->execute();
        } catch (\Throwable $e) {
            $this->caughtException($e);
        }
    }

    public function wpAjaxExecute()
    {
        $this->ajaxMode = true;

        $componentCtrl = $this->getComponentController();

        if ($this->getConfig('plugin/use_nounce', false)) {
            check_ajax_referer($this->pluginName);
        }

        $request = $_REQUEST['atkwp'] ?? false;
        if ($request) {
            $this->activatedComponent = $componentCtrl->searchComponentByKey($request);
        }

        $name = $this->pluginName;

        // check if this component has been output more than once
        // and adjust name accordingly.
        if ($count = $_REQUEST['atkwp-count'] ?? null) {
            $name = $this->pluginName . '-' . $count;
        }

        try {
            $view = new $this->activatedComponent['uses']();
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $name);
            $this->atkApp->execute($this->ajaxMode);
        } catch (\Throwable $e) {
            $this->caughtException($e);
        }
        $this->atkApp->callExit();
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function getComponentCount(): int
    {
        return $this->componentCount;
    }

    public function getWpComponentId(): ?string
    {
        return $this->getActivatedComponent('id');
    }

    public function getActivatedComponent(string $key = null)
    {
        return $key
            ? ($this->activatedComponent[$key] ?? null)
            : $this->activatedComponent
        ;
    }

    private function initApp()
    {
        $this->atkApp = new AtkWordpressApp($this);
    }

    public function getTemplateLocation(string $filename): array
    {
        $paths = [];
        foreach ($this->template_paths as $path) {
            $paths[] = $path . $filename;
        }

        return $paths;
    }

    public function newAtkAppView($template, $name): AtkWordpressView
    {
        $app = new AtkWordpressApp($this);

        $atkView = new AtkWordpressView();

        return $app->initWpLayout($atkView, $template, $name);
    }

    public function getAtkAppView($template, $name): AtkWordpressView
    {
        return $this->atkApp->initWpLayout(new AtkWordpressView(), $template, $name);
    }

    private function caughtException(\Throwable $e)
    {
        $view = $this->newAtkAppView('layout.html', $this->pluginName);

        switch (true) {
            case $e instanceof \Atk4\Core\Exception:
                $view->template->dangerouslySetHtml('Content', $e->getHTML());

                break;
            default:
                Message::addTo($view, [
                    get_class($e) . ': ' . $e->getMessage() . ' (in ' . $e->getFile() . ':' . $e->getLine() . ')',
                ])->addClass('error');
                Text::addTo($view, [
                    nl2br($e->getTraceAsString()),
                ]);

                break;
        }

        $view->template->tryDel('Header');

        if ($this->ajaxMode) {
            $view->getApp()->terminateJson([
                'success' => false,
                'message' => $view->getApp()->wpHtml->getHTML(),
            ]);
        } else {
            $view->getApp()->execute(false);
        }

        $this->atkApp->callExit();
    }

    public function getPluginBaseUrl(): string
    {
        return $this->pluginBaseUrl;
    }

    public function getPluginBasePath(): string
    {
        return $this->pluginBasePath;
    }
}
