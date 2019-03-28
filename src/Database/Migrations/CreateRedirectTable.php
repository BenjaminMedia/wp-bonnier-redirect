<?php

namespace Bonnier\WP\Redirect\Database\Migrations;

class CreateRedirectTable implements Migration
{
    public static function migrate()
    {
        if (self::verify()) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . Migrate::REDIRECTS_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "
        SET sql_notes = 1;
        CREATE TABLE `$table` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `from` text CHARACTER SET utf8 NOT NULL,
          `from_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `paramless_from_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `to` text CHARACTER SET utf8 NOT NULL,
          `to_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `locale` varchar(2) CHARACTER SET utf8 NOT NULL,
          `type` text CHARACTER SET utf8 NOT NULL,
          `wp_id` text CHARACTER SET utf8 DEFAULT NULL,
          `code` int(3) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `from_hash` (`from_hash`,`to_hash`,`locale`),
          KEY `from_hash_2` (`from_hash`,`to_hash`,`locale`),
          KEY `from_hash_3` (`from_hash`),
          KEY `paramless_from_hash` (`paramless_from_hash`)
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
        $table = $wpdb->prefix . Migrate::REDIRECTS_TABLE;
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }
}
