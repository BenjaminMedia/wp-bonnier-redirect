<?php

namespace Bonnier\WP\Redirect\Database;

class Bootstrap
{
    const REDIRECTS_TABLE = 'bonnier_redirects';

    public static function createRedirectsTable()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::REDIRECTS_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "SET sql_notes = 1;
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
              UNIQUE KEY `hashes` (`from_hash`,`to_hash`,`locale`),
              UNIQUE KEY `from_hash_locale` (`from_hash`, `locale`),
              KEY `from_hash_2` (`from_hash`,`to_hash`,`locale`),
              KEY `from_hash_3` (`from_hash`),
              KEY `paramless_from_hash` (`paramless_from_hash`)
            ) $charset;
            SET sql_notes = 1;
            ";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
