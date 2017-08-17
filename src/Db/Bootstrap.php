<?php

namespace Bonnier\WP\Redirect\Db;

class Bootstrap
{
    public static function create_redirects_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bonnier_redirects';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "SET sql_notes = 1;
            CREATE TABLE IF NOT EXISTS `$table_name` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `from` text CHARACTER SET utf8 NOT NULL,
              `to` text CHARACTER SET utf8 NOT NULL,
              `locale` varchar(2) CHARACTER SET utf8 NOT NULL DEFAULT '',
              `type` text CHARACTER SET utf8 NOT NULL,
              `wp_id` text CHARACTER SET utf8 NOT NULL,
              `code` int(3) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) $charset_collate;
            SET sql_notes = 1;
            ";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}