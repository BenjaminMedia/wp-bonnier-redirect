<?php

namespace Bonnier\WP\Redirect\Database\Migrations;

class AlterRedirectTableAddWildcardColumn implements Migration
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
        ADD `is_wildcard` tinyint(1) DEFAULT 0;
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
        return isset($result['Create Table']) && str_contains($result['Create Table'], 'is_wildcard');
    }
}
