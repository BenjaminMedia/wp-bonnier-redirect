<?php

namespace Bonnier\WP\Redirect\Database;

class Bootstrap
{
    const REDIRECTS_TABLE = 'bonnier_redirects';
    const LOG_TABLE = 'bonnier_redirects_log';

    public static function createRedirectsTable()
    {
        global $wpdb;
        $redirectTable = $wpdb->prefix . self::REDIRECTS_TABLE;
        $logTable = $wpdb->prefix . self::LOG_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "SET sql_notes = 1;
            CREATE TABLE `$redirectTable` (
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
}
