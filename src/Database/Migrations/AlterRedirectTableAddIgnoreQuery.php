<?php

namespace Bonnier\WP\Redirect\Database\Migrations;

use Illuminate\Support\Str;
use League\Csv\CannotInsertRecord;
use League\Csv\Writer;

class AlterRedirectTableAddIgnoreQuery implements Migration
{
    public static function migrate()
    {
        if (self::verify()) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . Migrate::REDIRECTS_TABLE;

        self::deduplicate($wpdb, $table);

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

    private static function deduplicate(\wpdb $wpdb, string $table)
    {
        if (defined('WP_ENV') && WP_ENV === 'testing') {
            return;
        }
        $csv = Writer::createFromPath(sprintf('%s_deleted_duplicates.csv', strtotime('now')), 'w+');
        try {
            $csv->insertOne(['id', 'from', 'to', 'locale']);
        } catch (CannotInsertRecord $exception) {
            return;
        }
        $wpdb->update($table, ['locale' => 'nb'], ['locale' => 'no']);
        $wpdb->delete($table, ['locale' => '']);
        $sql = "SELECT `locale` FROM `$table` GROUP BY `locale`";
        $locales = $wpdb->get_results($sql);
        foreach ($locales as $locale) {
            $results = $wpdb->get_results("
              SELECT `from_hash`, count(id) AS cnt
              FROM `$table`
              WHERE `locale` = '$locale->locale'
              GROUP BY `from` HAVING cnt > 1
            ");
            foreach ($results as $result) {
                $redirects = $wpdb->get_results("
                  SELECT * FROM `$table`
                  WHERE `from_hash` = '$result->from_hash'
                  AND `locale` = '$locale->locale'
                  ORDER BY id DESC
                ");
                array_shift($redirects); // Keep the newest redirect
                foreach ($redirects as $redirect) {
                    $wpdb->delete($table, ['id' => $redirect->id]);
                    try {
                        $csv->insertOne([$redirect->id, $redirect->from, $redirect->to, $redirect->locale]);
                    } catch (CannotInsertRecord $exception) {
                        continue;
                    }
                }
            }
        }
    }
}
