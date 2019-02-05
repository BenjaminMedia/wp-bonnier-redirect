<?php

namespace Bonnier\WP\Redirect;

use Bonnier\WP\Redirect\Admin\Dashboard;

class WpBonnierRedirect
{
    /**
     * @var object Instance of this class.
     */
    private static $instance;

    /**
     * @var string Filename of this class.
     */
    private $file;

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
     * Do not load this more than once.
     */
    private function __construct()
    {
        // Set plugin file variables
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->pluginDir = plugin_dir_path($this->file);
        $this->pluginUrl = plugin_dir_url($this->file);
        $this->viewsDir = sprintf('%s/Views/', rtrim($this->pluginDir, '/'));

        // Load admin menu
        add_action('admin_menu', [Dashboard::class, 'addPluginMenus']);
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
}
