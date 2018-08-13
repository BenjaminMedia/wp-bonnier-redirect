<?php
/**
 * Plugin Name: WP Bonnier Redirect
 * Version: 2.0.0
 * Plugin URI: https://github.com/BenjaminMedia/wp-bonnier-redirect
 * Description: This plugin creates redirects with support for Polylang
 * Author: Bonnier - Nicklas Kevin Frank
 * License: GPL v3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return \Bonnier\WP\Redirect\WpBonnierRedirect $instance returns an instance of the plugin
 */
function loadBonnierRedirect()
{
    return \Bonnier\WP\Redirect\WpBonnierRedirect::instance();
}

register_activation_hook(__FILE__, function () {
    \Bonnier\WP\Redirect\Db\Bootstrap::createRedirectsTable();
});

add_action('plugins_loaded', 'loadBonnierRedirect', 0);
