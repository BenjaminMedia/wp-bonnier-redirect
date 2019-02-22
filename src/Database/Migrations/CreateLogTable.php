<?php

namespace Bonnier\WP\Redirect\Database\Migrations;

class CreateLogTable implements Migration
{
    public static function migrate()
    {
        if (self::verify()) {
            return;
        }

        global $wpdb;
        $logTable = $wpdb->prefix . Migrate::LOG_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "
        SET sql_notes = 1;
        CREATE TABLE `$logTable` (
          `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
          `slug` text CHARACTER SET utf8 NOT NULL,
          `hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `type` text CHARACTER SET utf8 NOT NULL,
          `wp_id` INT(11) unsigned NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
          PRIMARY KEY (`id`),
          KEY `hash` (`hash`)
        ) $charset;
        SET sql_notes = 1;
        ";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Verify that the migration was run successfully
     *
     * @return bool
     */
    public static function verify(): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . Migrate::LOG_TABLE;
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }
}
