<?php
/**
 * Plugin Name: WP Bonnier Redirect
 * Version: 4.0.0
 * Plugin URI: https://github.com/BenjaminMedia/wp-bonnier-redirect
 * Description: This plugin creates redirects with support for Polylang
 * Author: Bonnier Publications
 * License: GPL v3
 */

if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, function () {
    \Bonnier\WP\Redirect\Db\Bootstrap::createRedirectsTable();
});

add_action('plugins_loaded', function () {
    \Bonnier\WP\Redirect\WpBonnierRedirect::boot();
}, 0);
