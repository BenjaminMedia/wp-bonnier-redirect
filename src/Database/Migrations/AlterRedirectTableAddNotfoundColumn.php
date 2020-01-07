<?php

namespace Bonnier\WP\Redirect\Database\Migrations;

use Illuminate\Support\Str;

class AlterRedirectTableAddNotfoundColumn implements Migration
{
    /**
     * Run the migration
     */
    public static function migrate()
    {
        if (self::verify()) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . Migrate::REDIRECTS_TABLE;

        $sql = "
        ALTER TABLE `$table`
        ADD `notfound` tinyint(1) DEFAULT NULL,
        ADD `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        ADD `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
        ";
        $wpdb->query($sql);
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
        $result = $wpdb->get_row("SHOW CREATE TABLE $table", ARRAY_A);
        return isset($result['Create Table']) && Str::contains($result['Create Table'], 'notfound');
    }
}
