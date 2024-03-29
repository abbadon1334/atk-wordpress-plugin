<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress;

use Atk4\AtkWordpress\Components\Metabox;
use Atk4\AtkWordpress\Controllers\ComponentController;
use Atk4\AtkWordpress\Controllers\ModelController;
use Atk4\AtkWordpress\Interfaces\IPlugin;
use Atk4\Core\CollectionTrait;
use Atk4\Core\ConfigTrait;
use Atk4\Core\Factory;
use Atk4\Data\Persistence\Sql;
use Atk4\Ui\App;
use Atk4\Ui\Exception\UnhandledCallbackExceptionError;

abstract class AtkWordpress implements IPlugin
{
    use CollectionTrait;
    use ConfigTrait {
        readConfig as private loadConfigFromFolder;
    }

    public string $defaultLayout = 'layout.html';

    private Sql    $dbConnection;

    private string $pluginName;

    private ?array $activatedComponent = null;

    private int    $componentCount = 0;

    private string          $pluginBaseUrl;

    private AtkWordpressApp $atkApp;

    private string          $pluginBasePath;

    private array $template_paths = [];

    /** @var ComponentController[] */
    public array $controllers = [];

    public function __construct(string $plugin_name)
    {
        $this->setDbConnection();
        $this->_addIntoCollection(ModelController::class, Factory::factory([ModelController::class]), 'controllers');
        $this->_addIntoCollection(
            ComponentController::class,
            Factory::factory([ComponentController::class]),
            'controllers'
        );
        $this->pluginName = $plugin_name;
    }

    private function setDbConnection(): void
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        $this->dbConnection = new SQL($dsn, DB_USER, DB_PASSWORD);
    }

    public function loadConfigFromFolder($path, string $type = 'php'): void
    {
        $this->readConfig(glob($path . '/*'), $type);
    }

    public function getModelController(): ModelController
    {
        /** @var ModelController $ctrl */
        $ctrl = $this->_getFromCollection(ModelController::class, 'controllers');

        return $ctrl;
    }

    public function getDbConnection(): Sql
    {
        return $this->dbConnection;
    }

    public function boot(string $filePath): void
    {
        $this->pluginBasePath = plugin_dir_path($filePath);
        $this->pluginBaseUrl = plugin_dir_url($filePath);

        $this->template_paths = [];

        foreach ($this->getConfig('plugin/template_locations') as $path) {
            $this->template_paths[] = trailingslashit($this->pluginBasePath . $path);
        }

        $this->initApp();

        // setup plugin activation / deactivation hook.
        register_activation_hook($filePath, function (): void {
            $this->activatePlugin();
        });
        register_deactivation_hook($filePath, function (): void {
            $this->deactivatePlugin();
        });

        $this->getComponentController()->setup($this);

        // register ajax action for this plugin
        add_action(sprintf('wp_ajax_%s', $this->pluginName), function (...$args): void {
            $this->wpAjaxExecute();
        });

        if ($this->getConfig('plugin/use_ajax_front', false)) {
            // enable Wp ajax front end action.
            add_action(sprintf('wp_ajax_nopriv_%s', $this->pluginName), function (...$args): void {
                $this->wpAjaxExecute();
            });
        }
    }

    private function initApp(): void
    {
        $this->atkApp = new AtkWordpressApp([
            'plugin' => $this,
        ]);
        $this->atkApp->invokeInit();
    }

    public function getComponentController(): ComponentController
    {
        return $this->controllers[ComponentController::class];
    }

    public function wpAjaxExecute(): void
    {
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
            $this->atkApp->run();
        } catch (\Throwable $throwable) {
            $this->caughtException($throwable);
        }

        $this->atkApp->callExit();
    }

    /**
     * Catch exception.
     */
    public function caughtException(\Throwable $exception): void
    {
        while ($exception instanceof UnhandledCallbackExceptionError) {
            $exception = $exception->getPrevious();
        }

        $this->initApp();

        $this->atkApp->catchRunawayCallbacks = false;

        // just replace layout to avoid any extended App->_construct problems
        // it will maintain everything as in the original app StickyGet, logger, Events
        $this->getAtkAppView($this->defaultLayout, $this->pluginName);

        $this->atkApp->wpHtml->template->dangerouslySetHtml('Content', $this->atkApp->renderExceptionHtml($exception));

        // remove header
        $this->atkApp->wpHtml->template->tryDel('Header');

        if (($this->atkApp->isJsUrlRequest() || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
            && !isset($_GET['__atk_tab'])) {
            $privateMethod = new \ReflectionMethod(App::class, 'outputResponseJson');
            $privateMethod->setAccessible(true);

            $privateMethod->invoke($this->atkApp, [
                'success' => false,
                'message' => $this->atkApp->wpHtml->getHtml(),
            ]);
        } else {
            $this->atkApp->setResponseStatusCode(500);
            $this->atkApp->run();
        }

        // Process is already in shutdown/stop
        // no need of call exit function

        $privateMethod = new \ReflectionMethod(App::class, 'callBeforeExit');
        $privateMethod->setAccessible(true);

        $privateMethod->invoke($this->atkApp);
    }

    public function getAtkAppView($template, $name): AtkWordpressView
    {
        return $this->atkApp->initWpLayout(new AtkWordpressView(), $template, $name);
    }

    public function wpMetaBoxExecute(\WP_Post $post, array $param): void
    {
        // set the view to output.
        $this->activatedComponent = $this->getComponentController()->searchComponentByType('metabox', $param['id']);

        try {
            $view = new $this->activatedComponent['uses'](['args' => $param['args']]);
            /** @var Metabox $metaBox */
            $metaBox = $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName);
            $metaBox->setFieldInput($post->ID, $this->getComponentController());
            $this->atkApp->run();
        } catch (\Throwable $throwable) {
            $this->caughtException($throwable);
        }
    }

    public function wpShortcodeExecute(array $shortcode, array $args)
    {
        $this->activatedComponent = $shortcode;
        ++$this->componentCount;

        try {
            $view = new $this->activatedComponent['uses'](['args' => $args]);
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName . '-' . $this->componentCount);

            return $this->atkApp->render($this->atkApp->isJsUrlRequest());
        } catch (\Throwable $throwable) {
            $this->caughtException($throwable);
        }
    }

    public function wpPageExecute(array $page, array $args)
    {
        $this->activatedComponent = $page;

        try {
            $view = new $this->activatedComponent['uses'](['args' => $args]);
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName);

            ob_start();

            $this->atkApp->run();

            return ob_get_clean();
        } catch (\Throwable $throwable) {
            $this->caughtException($throwable);
        }
    }

    public function wpDashboardExecute($key, $dashboard, $configureMode = false): void
    {
        $componentCtrl = $this->getComponentController();

        $this->activatedComponent = $componentCtrl->searchComponentByType('dashboard', $dashboard['id']);

        try {
            $view = new $this->activatedComponent['uses'](['configureMode' => $configureMode]);
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName);
            $this->atkApp->run();
        } catch (\Throwable $throwable) {
            $this->caughtException($throwable);
        }
    }

    public function wpPanelExecute(): void
    {
        global $hook_suffix;

        $componentCtrl = $this->getComponentController();

        $this->activatedComponent = $componentCtrl->searchComponentByType('panel', $hook_suffix, 'hook');

        try {
            $view = new $this->activatedComponent['uses']();
            $this->atkApp->initWpLayout($view, $this->defaultLayout, $this->pluginName);
            $this->atkApp->run();
        } catch (\Throwable $throwable) {
            $this->caughtException($throwable);
        }
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
            : $this->activatedComponent;
    }

    public function getTemplateLocation(string $filename): array
    {
        $paths = [];
        foreach ($this->template_paths as $path) {
            $paths[] = $path . $filename;
        }

        return $paths;
    }

    /*
    public function caughtException(\Throwable $e)
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
    */

    public function newAtkAppView($template, $name): AtkWordpressView
    {
        $app = new AtkWordpressApp([
            'plugin' => $this,
        ]);

        $atkView = new AtkWordpressView();

        return $app->initWpLayout($atkView, $template, $name);
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
