<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Http\BonnierRedirect;
use Exception;
use League\Csv\Reader;
use WP_CLI;
use WP_CLI_Command;

class CsvImport extends WP_CLI_Command
{
    const CMD_NAMESPACE = 'bonnier redirect import';

    public static function register()
    {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }

    /**
     * Imports redirects from a csv with from and to columns
     *
     * ## OPTIONS
     *
     * <file>
     * : The path to the csv file
     *
     * <locale>
     * : The locale of the redirects being imported
     *
     * ## EXAMPLES
     *
     * wp bonnier redirect import csv <file> <locale>
     *
     * @param $args
     * @param $assoc_args
     *
     * @return null
     * @throws \WP_CLI\ExitException
     */
    public function csv( $args, $assoc_args )
    {
        list($file, $locale) = $args;

        $csv = $this->getCsv($file);

        $count = 0;

        collect($csv->getRecords())->each(function ($data) use ($locale, &$count) {

            if (($redirectSource = $data['from'] ?? null) &&
                ($redirectDestination = $data['to'] ?? null)
            ) {
                $response = BonnierRedirect::createRedirect(
                    $redirectSource,
                    $redirectDestination,
                    $locale,
                    'csv-import',
                    null
                );
                if ($response['success']) {
                    WP_CLI::success(sprintf(
                        'Created redirect from: %s to: %s',
                        $redirectSource,
                        $redirectDestination
                    ));
                    $count++;
                } else {
                    WP_CLI::warning(sprintf(
                        'Failed creating redirect from: %s to: %s',
                        $data['from'],
                        $data['to']
                    ));
                }
            }
        });

        WP_CLI::success(sprintf('Import done %s redirects were imported', $count));
    }

    private function getCsv($file)
    {
        WP_CLI::line(sprintf('Trying to get file from: %s', $file));

        try {
            $csv = Reader::createFromPath($file, 'r');
            $csv->setDelimiter(',');
        } catch (Exception $e) {
            return WP_CLI::error('Failed loading csv file double check path');
        }

        // Get the headers/keys to match with values
        $csv->setHeaderOffset(0);

        return $csv;
    }
}
