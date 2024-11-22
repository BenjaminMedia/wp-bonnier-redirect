<?php
/**
 * Plugin Name: WP Bonnier Redirect
 * Version: 4.15.2
 * Plugin URI: https://github.com/BenjaminMedia/wp-bonnier-redirect
 * Description: This plugin creates redirects with support for Polylang
 * Author: Bonnier Publications
 * License: GPL v3
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', function () {
    \Bonnier\WP\Redirect\WpBonnierRedirect::boot();
}, 0);
