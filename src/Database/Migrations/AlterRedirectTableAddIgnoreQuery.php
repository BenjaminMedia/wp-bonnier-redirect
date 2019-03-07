<?php

namespace Bonnier\WP\Redirect\Database\Migrations;

use Illuminate\Support\Str;

class AlterRedirectTableAddIgnoreQuery implements Migration
{
    public static function migrate()
    {
        if (self::verify()) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . Migrate::REDIRECTS_TABLE;

        $sql = "
        ALTER TABLE `$table`
        ADD `keep_query` tinyint(1) DEFAULT 0,
        ADD UNIQUE KEY `from_hash_locale` (`from_hash`, `locale`);
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
        return isset($result['Create Table']) && Str::contains($result['Create Table'], 'keep_query');
    }
}
