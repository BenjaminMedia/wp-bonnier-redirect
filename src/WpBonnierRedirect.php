<?php

namespace Bonnier\WP\Redirect;

use Bonnier\WP\Redirect\Commands\Commands;
use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Controllers\ListController;
use Bonnier\WP\Redirect\Controllers\ToolController;
use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Migrations\Migrate;
use Bonnier\WP\Redirect\Observers\Observers;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Symfony\Component\HttpFoundation\Request;

class WpBonnierRedirect
{
    const FILTER_SLUG_IS_LIVE = 'redirect/slug-is-live';
    /**
     * @var WpBonnierRedirect Instance of this class.
     */
    private static $instance;

    /**
     * @var string Filename of this class.
     */
    private $dir;

    /**
     * @var string Basename of this class.
     */
    private $basename;

    /**
     * @var string Plugins directory for this plugin.
     */
    private $pluginDir;

    /**
     * @var string Plugins url for this plugin.
     */
    private $pluginUrl;

    /**
     * @var string Path to the Views folder
     */
    private $viewsDir;

    /**
     * @var string Path to the Assets folder
     */
    private $assetsDir;

    /**
     * @var string Assets url
     */
    private $assetsUrl;

    /** @var LogRepository */
    private $logRepository;

    /** @var RedirectRepository */
    private $redirectRepository;

    /** @var Request */
    private $request;

    /** @var ListController */
    private $listController;

    /** @var CrudController */
    private $crudController;

    /** @var ToolController */
    private $toolController;

    /**
     * Do not load this more than once.
     */
    private function __construct()
    {
        add_option(Migrate::OPTION);
        Migrate::run();
        // Set plugin file variables
        $this->dir = __DIR__;
        $this->basename = plugin_basename($this->dir);
        $this->pluginDir = plugin_dir_path($this->dir);
        $this->pluginUrl = plugin_dir_url($this->dir);
        $this->viewsDir = sprintf('%s/src/Views/', rtrim($this->pluginDir, '/'));
        $this->assetsDir = sprintf('%s/assets', rtrim($this->pluginDir, '/'));
        $this->assetsUrl = sprintf('%s/assets', rtrim($this->pluginUrl, '/'));

        try {
            $this->redirectRepository = new RedirectRepository(new DB);
        } catch (\Exception $exception) {
            wp_die($exception->getMessage());
        }
        try {
            $this->logRepository = new LogRepository(new DB);
        } catch (\Exception $exception) {
            wp_die($exception->getMessage());
        }
        $this->request = Request::createFromGlobals();
        Observers::bootstrap($this->logRepository, $this->redirectRepository);

        Commands::register();

        add_filter(self::FILTER_SLUG_IS_LIVE, function (bool $isLive) {
            return $isLive;
        });

        // Load admin menu
        add_action('admin_menu', [$this, 'loadAdminMenus']);
    }

    /**
     * Returns the instance of this class.
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
            /**
             * Run after the plugin has been loaded.
             */
            do_action('wp_bonnier_redirect_loaded');
        }

        return self::$instance;
    }

    public static function boot()
    {
        self::$instance = new self;
    }

    /**
     * @param string $viewFile
     * @return string
     */
    public function getViewPath(string $viewFile)
    {
        $fileName = sprintf('%s/%s', rtrim($this->viewsDir, '/'), ltrim($viewFile, '/'));
        if (!file_exists($fileName)) {
            throw new \RuntimeException(sprintf('The file \'%s\' does not exist!', $viewFile));
        }
        return $fileName;
    }

    public function loadAdminMenus()
    {
        $topLevelListPageHook = add_menu_page(
            'Bonnier Redirects',
            'Bonnier Redirects',
            'manage_categories', // Editor role
            'bonnier-redirects',
            [$this, 'displayRedirectsTable'],
            'dashicons-external'
        );
        $listPageHook = add_submenu_page(
            'bonnier-redirects',
            'All Redirects',
            'All Redirects',
            'manage_categories', // Editor role
            'bonnier-redirects',
            [$this, 'displayRedirectsTable']
        );
        $crudPageHook = add_submenu_page(
            'bonnier-redirects',
            'Add New',
            'Add New',
            'manage_categories', // Editor role
            'add-redirect',
            [$this, 'displayAddRedirectPage']
        );
        $toolPageHook = add_submenu_page(
            'bonnier-redirects',
            'Tools',
            'Tools',
            'manage_options', // Admin role
            'redirect-tools',
            [$this, 'displayToolPage']
        );

        add_action(sprintf('load-%s', $topLevelListPageHook), [$this, 'loadRedirectsTable']);
        add_action(sprintf('load-%s', $listPageHook), [$this, 'loadRedirectsTable']);
        add_action(sprintf('load-%s', $crudPageHook), [$this, 'loadCrudController']);
        add_action(sprintf('load-%s', $toolPageHook), [$this, 'loadToolController']);
    }

    public function displayRedirectsTable()
    {
        $this->listController->displayRedirectsTable();
    }

    public function displayAddRedirectPage()
    {
        $this->crudController->displayAddRedirectPage();
    }

    public function displayToolPage()
    {
        $this->toolController->displayToolPage();
    }

    public function loadRedirectsTable()
    {
        $this->listController = new ListController($this->redirectRepository, $this->request);
        $this->listController->loadRedirectsTable();
    }

    public function loadCrudController()
    {
        $this->crudController = new CrudController($this->logRepository, $this->redirectRepository, $this->request);
        $this->crudController->handlePost();
        $this->crudController->registerScripts();
    }

    public function loadToolController()
    {
        $this->toolController = new ToolController($this->redirectRepository, $this->request);
        $this->toolController->handlePost();
        $this->toolController->registerScripts();
    }

    public function assetPath(string $file, bool $create = false)
    {
        if (!$create && !file_exists(sprintf('%s/%s', rtrim($this->assetsDir, '/'), ltrim($file, '/')))) {
            throw new \RuntimeException(sprintf('The asset file \'%s\' does not exist!', $file));
        }

        return sprintf('%s/%s', rtrim($this->assetsDir, '/'), ltrim($file, '/'));
    }

    public function assetURI(string $file)
    {
        if (!file_exists(sprintf('%s/%s', rtrim($this->assetsDir, '/'), ltrim($file, '/')))) {
            throw new \RuntimeException(sprintf('The asset file \'%s\' does not exist!', $file));
        }

        return sprintf('%s/%s', rtrim($this->assetsUrl, '/'), ltrim($file, '/'));
    }

    public function assetVersion(string $file)
    {
        $filename = sprintf('%s/%s', rtrim($this->assetsDir, '/'), ltrim($file, '/'));
        if (!file_exists($filename)) {
            throw new \RuntimeException(sprintf('The asset file \'%s\' does not exist!', $file));
        }

        return filemtime($filename);
    }

    public function getRedirectRepository(): RedirectRepository
    {
        return $this->redirectRepository;
    }

    public function getLogRepository(): LogRepository
    {
        return $this->logRepository;
    }
}
