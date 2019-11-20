<?php

namespace Bonnier\WP\Redirect\Database\Migrations;

use Illuminate\Support\Str;

class AlterRedirectLogTableAddIdTypeIndex implements Migration
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
        $table = $wpdb->prefix . Migrate::LOG_TABLE;

        $changeTypeToVarchar = "
        ALTER TABLE `$table`
        MODIFY `type` VARCHAR(30);
        ";
        $wpdb->query($changeTypeToVarchar);

        $addIndex = "
        CREATE INDEX `wp_id_and_type`
        ON `$table` (`wp_id`,`type`(30));
        ";
        $wpdb->query($addIndex);
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
        $result = $wpdb->get_row("SHOW CREATE TABLE $table", ARRAY_A);
        return isset($result['Create Table']) && Str::contains($result['Create Table'], 'wp_id_and_type');
    }
}
