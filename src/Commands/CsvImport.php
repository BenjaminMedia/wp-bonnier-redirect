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


    /**
     * Imports redirects from a csv generated drupal file
     *
     * ## OPTIONS
     *
     * <file>
     * : The file path
     *
     * ## EXAMPLES
     *
     * wp bonner redirect import redirect <file>
     *
     * @synopsis <file>
     */
    public function redirect( $args, $assoc_args ) {
        try {
            list($csv, $headers) = $this->getCsv($args);
        } catch (Exception $e) {
            return WP_CLI::error("The script failed loading a file at the specified path");
        }

        $csv->each(function ($row, $offset) use ($headers) {
            // Skip the first one to avoid headers
            if($offset == 0) {
                return true;
            }

            $data = array_combine($headers, $row);

            if(isset($data['source']) && isset($data['redirect'])) {
                list($source, $redirect) = $this->cleanUrls($data);

                if(empty($data['language'])) {
                    collect(pll_languages_list())->each(function ($locale) use ($data, $source, $redirect) {
                        try {
                            BonnierRedirect::addRedirect($source, $redirect, $locale, 'drupal', null, 301, true);
                        } catch (Exception $e) {
                            WP_CLI::error("Scripted found double redirect");
                        }
                    });
                } else {
                    try {
                        BonnierRedirect::addRedirect($source, $redirect, $this->languageConverter($data['language']), 'drupal', 0, 301, true);
                    } catch (Exception $e) {
                        WP_CLI::error("Scripted found double redirect");
                    }
                }
                return true;
            }

            WP_CLI::error("The script failed");
            return false;
        });
        WP_CLI::success("The script ran successfully, all redirects imported.");
    }

    /**
     * Imports aliases from a csv generated rupal file
     *
     * ## OPTIONS
     *
     * <file>
     * : The file path
     *
     * ## EXAMPLES
     *
     * wp bonner redirect import alias <file>
     *
     * @synopsis <file>
     */
    public function alias( $args, $assoc_args ) {
        try {
            list($csv, $headers) = $this->getCsv($args);
        } catch (Exception $e) {
            return WP_CLI::error("The script failed loading a file at the specified path");
        }

        $csv->each(function ($row, $offset) use ($headers) {
            // Skip the first one to avoid headers
            if($offset == 0) {
                return true;
            }

            $data = array_combine($headers, $row);

            if(isset($data['src']) && isset($data['dst'])) {
                if(empty($data['language'])) {
                    collect(pll_languages_list())->each(function ($locale) use ($data) {
                        try {
                            BonnierRedirect::addRedirect($data['src'], $data['dst'], $locale, 'drupal', null, 301, true);
                        } catch (Exception $e) {
                            WP_CLI::error("Scripted found double redirect");
                        }
                    });
                } else {
                    try {
                        BonnierRedirect::addRedirect($data['src'], $data['dst'], $this->languageConverter($data['language']), 'drupal', 0, 301, true);
                    } catch (Exception $e) {
                        WP_CLI::error("Scripted found double redirect");
                    }
                }
                return true;
            }
            WP_CLI::error("The script failed - no to and/or from in row");
            return false;
        });
        WP_CLI::success("The script ran successfully, all redirects imported.");
    }

    public static function register() {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }

    private function getCsv($args) {
        list( $file ) = $args;

        WP_CLI::line(WP_CLI::colorize("%GThe script is running!%n"));

        $csv = Reader::createFromPath($file);
        $csv->setDelimiter(',');
        $csv->getInputEncoding('UTF-8');

        // Get the headers/keys to match with values
        $headers = $csv->fetchOne();

        return [$csv, $headers];
    }

    private function cleanUrls($data) {
        $source = BonnierRedirect::trimAddSlash($data['source'], true);
        $redirect = BonnierRedirect::trimAddSlash($data['redirect'], true);

        $source = str_replace("/<front>", '/', $source);
        $redirect = str_replace("/<front>", '/', $redirect);

        return [
            $source,
            $redirect,
        ];
    }

    private function languageConverter($language) {
        return Collect([
            'da_dk' => 'da',
            'da-dk' => 'da',
            'sv_se' => 'sv',
            'sv-se' => 'sv',
            'fi_fi' => 'fi',
            'fi-fi' => 'fi',
            'nb_no' => 'nb',
            'nb-no' => 'nb'
        ])->get(strtolower($language), '');
    }
}