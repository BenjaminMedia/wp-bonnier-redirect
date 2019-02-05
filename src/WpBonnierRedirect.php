<?php

namespace Bonnier\WP\Redirect;


class WpBonnierRedirect
{
    /**
     * Text domain for translators
     */
    const TEXT_DOMAIN = 'wp-bonnier-redirect';

    const CLASS_DIR = 'src';

    /**
     * @var object Instance of this class.
     */
    private static $instance;

    public $settings;

    /**
     * @var string Filename of this class.
     */
    public $file;

    /**
     * @var string Basename of this class.
     */
    public $basename;

    /**
     * @var string Plugins directory for this plugin.
     */
    public $pluginDir;

    /**
     * @var string Plugins url for this plugin.
     */
    public $pluginUrl;

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

        // Load textdomain
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename) . '/languages');
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
}
