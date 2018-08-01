<?php
/**
 * Plugin Name: WP Bonnier Redirect
 * Version: 1.3.13
 * Plugin URI: https://github.com/BenjaminMedia/wp-bonnier-redirect
 * Description: This plugin creates redirects with support for Polylang
 * Author: Bonnier - Nicklas Kevin Frank
 * License: GPL v3
 */

namespace Bonnier\WP\Redirect;

// Do not access this file directly
use Bonnier\WP\Redirect\Commands\CsvImport;
use Bonnier\WP\Redirect\Commands\ParamLessHasher;
use Bonnier\WP\Redirect\Commands\RedirectFixer;
use Bonnier\WP\Redirect\Db\Bootstrap;
use Bonnier\WP\Redirect\Http\BonnierRedirect;
use Bonnier\WP\Redirect\Model\Post;
use Bonnier\WP\Redirect\Model\Tag;
use Bonnier\WP\Redirect\Page\RedirectPage;

if (!defined('ABSPATH')) {
    exit;
}

// Handle autoload so we can use namespaces
spl_autoload_register(function ($className) {
    if (strpos($className, __NAMESPACE__) !== false) {
        $classPath = str_replace( // Replace namespace with directory separator
            "\\",
            DIRECTORY_SEPARATOR,
            str_replace( // Replace namespace with path to class dir
                __NAMESPACE__,
                __DIR__ . DIRECTORY_SEPARATOR . Plugin::CLASS_DIR,
                $className
            )
        );
        require_once($classPath . '.php');
    }
});

// Load dependencies from includes
require_once( __DIR__ . '/includes/vendor/autoload.php' );

class Plugin
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
    public $plugin_dir;

    /**
     * @var string Plugins url for this plugin.
     */
    public $plugin_url;

    /**
     * Do not load this more than once.
     */
    private function __construct()
    {
        // Set plugin file variables
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = plugin_dir_url($this->file);

        // Load textdomain
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename) . '/languages');

        if ( defined('WP_CLI') && WP_CLI ) {
            CsvImport::register();
            RedirectFixer::register();
            ParamLessHasher::register();
        }

        BonnierRedirect::register();

        RedirectPage::register();

        Post::register();
        Tag::register();
    }

    /**
     * Returns the instance of this class.
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
            global $wp_bonnier_redirect;
            $wp_bonnier_redirect = self::$instance;

            /**
             * Run after the plugin has been loaded.
             */
            do_action('wp_bonnier_redirect_loaded');
        }

        return self::$instance;
    }

}

/**
 * @return Plugin $instance returns an instance of the plugin
 */
function instance()
{
    return Plugin::instance();
}

register_activation_hook( __FILE__, function(){
    Bootstrap::create_redirects_table();
});

add_action('plugins_loaded', __NAMESPACE__ . '\instance', 0);
