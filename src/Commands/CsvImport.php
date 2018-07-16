<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Http\BonnierRedirect;
use Exception;
use League\Csv\Reader;
use WP_CLI;
use WP_CLI_Command;


class CsvImport extends WP_CLI_Command
{
    const CMD_NAMESPACE = 'bonnier redirect-import';

    public static function register() {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }

    /**
     * Imports redirects from a csv generated drupal file
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
     * wp bonnier redirect-import csv <file> <locale>
     *
     * @param $args
     * @param $assoc_args
     *
     * @return null
     * @throws \WP_CLI\ExitException
     */
    public function csv( $args, $assoc_args ) {

        list($file, $locale) = $args;
        list($csv, $headers) = $this->getCsv($file);

        $count = 0;


        $csv->setOffset(1)->fetchAll(function ($row, $offset) use ($headers, $locale, &$count) {

            $data = array_combine($headers, $row);

            if(isset($data['path']) && isset($data['redirect_url'])) {
                $response = BonnierRedirect::createRedirect($data['path'], $data['redirect_url'], $locale, 'imported', null);
                if($response['success']) {
                    WP_CLI::success(sprintf('Created redirect from: %s to: %s', $data['path'], $data['redirect_url']));
                    $count++;
                } else {
                    WP_CLI::warning(sprintf('Failed creating redirect from: %s to: %s', $data['path'], $data['redirect_url']));
                }
            }
        });

        WP_CLI::success(sprintf('Import done %s redirects were imported', $count));
    }


    private function getCsv($file) {

        WP_CLI::line(sprintf('Trying to get file from: %s', $file));

        try {
            $csv = Reader::createFromPath($file, 'r');
            $csv->setDelimiter(',');
            $csv->getInputEncoding('UTF-8');
        } catch (Exception $e) {
            return WP_CLI::error('Failed loading csv file double check path');
        }
        // Get the headers/keys to match with values
        $headers = $csv->fetchOne();

        return [$csv, $headers];
    }


}
