<?php

namespace Bonnier\WP\Redirect;

use Bonnier\WP\Redirect\Controllers\Dashboard;
use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Controllers\ListController;
use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Symfony\Component\HttpFoundation\Request;

class WpBonnierRedirect
{
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

    /**
     * Do not load this more than once.
     */
    private function __construct()
    {
        // Set plugin file variables
        $this->dir = __DIR__;
        $this->basename = plugin_basename($this->dir);
        $this->pluginDir = plugin_dir_path($this->dir);
        $this->pluginUrl = plugin_dir_url($this->dir);
        $this->viewsDir = sprintf('%s/src/Views/', rtrim($this->pluginDir, '/'));
        $this->assetsDir = sprintf('%s/assets', rtrim($this->pluginDir, '/'));
        $this->assetsUrl = sprintf('%s/assets', rtrim($this->pluginUrl, '/'));

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
        global $wpdb;
        $database = new DB($wpdb);
        $redirectRepository = new RedirectRepository($database);
        $request = Request::createFromGlobals();
        $listController = new ListController($redirectRepository, $request);
        add_menu_page(
            'Bonnier Redirects',
            'Bonnier Redirects',
            'manage_options',
            'bonnier-redirects',
            [$listController, 'displayRedirectsTable'],
            'dashicons-external'
        );
        $allRedirectsPageHook = add_submenu_page(
            'bonnier-redirects',
            'All Redirects',
            'All Redirects',
            'manage_options',
            'bonnier-redirects',
            [$listController, 'displayRedirectsTable']
        );
        $managePageHook = add_submenu_page(
            'bonnier-redirects',
            'Add New',
            'Add New',
            'manage_options',
            'add-redirect',
            [CrudController::class, 'displayAddRedirectPage']
        );

        add_action(sprintf('load-%s', $allRedirectsPageHook), [ListController::class, 'loadRedirectsTable']);
        add_action(sprintf('load-%s', $managePageHook), [CrudController::class, 'handlePost']);
        add_action(sprintf('load-%s', $managePageHook), [CrudController::class, 'registerScripts']);
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
}
