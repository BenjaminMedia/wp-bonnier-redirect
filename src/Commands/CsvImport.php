<?php

namespace Bonnier\WP\Redirect\Commands;

use Exception;
use League\Csv\Reader;
use WP_CLI;
use WP_CLI_Command;

/**
 * Class Migrate
 *
 * @package \Bonnier\WP\ContentHub\Commands
 */
class CsvImport extends WP_CLI_Command
{
    const CMD_NAMESPACE = 'bonnier redirect import';

    /**
     * Migrates Content Hub ACF fields to latest version
     *
     * ## OPTIONS
     *
     * <file>
     * : The file path
     *
     * ## EXAMPLES
     *
     * wp bonner redirect import <file>
     *
     * @synopsis <file>
     */
    public function run( $args, $assoc_args ) {
        list( $file ) = $args;

        WP_CLI::line(WP_CLI::colorize("%GThe script is running!%n"));

        try {
            $csv = Reader::createFromPath($file);
            $csv->setDelimiter(',');
            $csv->getInputEncoding('UTF-8');
        } catch (Exception $e) {
            return WP_CLI::error("The script failed loading a file at the specified path");
        }

        // Get the headers/keys to match with values
        $headers = $csv->fetchOne();

        $csv->each(function ($row, $offset) use ($headers) {
            // Skip the first one to avoid headers
            if($offset == 0) {
                return true;
            }

            $data = array_combine($headers, $row);

            if(isset($data['source']) && isset($data['redirect'])) {

                return true;
            }
            WP_CLI::error("The script failed no to and/or from in row");
            return false;
        });
        WP_CLI::success("The script ran successfully, all redirects imported.");
    }

    public static function register() {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }
}