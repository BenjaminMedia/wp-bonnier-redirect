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
     * ## EXAMPLES
     *
     * wp bonner redirect import redirect
     */
    public function redirect( $args, $assoc_args ) {
        try {
            list($csv, $headers) = $this->getCsv(['GDS-path_redirect.csv']);
        } catch (Exception $e) {
            return WP_CLI::error("The script failed loading a file at the specified path");
        }

        $aliases = $this->getAliasesCollection();

        $csv->each(function ($row, $offset) use ($headers, $aliases) {
            // Skip the first one to avoid headers
            if($offset == 0) {
                return true;
            }

            $data = array_combine($headers, $row);

            if(isset($data['source']) && isset($data['redirect'])) {
                $source = $this->cleanUrl($data['source']);
                $redirect = $this->cleanUrl($this->findBottomAlias($aliases, $data['redirect']));
                $languages = (isset($data['language']) && !empty(trim($data['language']))) ? [$this->languageConverter($data['language'])] : pll_languages_list();
                collect($languages)->each(function ($locale) use ($data, $source, $redirect) {
                    try {
                        BonnierRedirect::addRedirect(
                            $source,
                            BonnierRedirect::trimAddSlash($redirect, true),
                            $locale, 'drupal', null, 301, true
                        );
                    } catch (Exception $e) {
                        WP_CLI::error("Scripted found double redirect");
                    }
                });
                return true;
            }

            WP_CLI::error("The script failed");
            return false;
        });

        self::removeSelfDirects();
        self::cleanHashes();

        WP_CLI::success("The script ran successfully, all redirects imported.");
    }

    private function getAliasesCollection() {
        try {
            list($csv, $headers) = $this->getCsv(['GDS-url_alias.csv']);
        } catch (Exception $e) {
            return WP_CLI::error("The script failed loading a file at GDS-url_alias.csv");
        }

        $collected = collect([]);
        $csv->each(function ($row, $offset) use ($headers, $collected) {
            // Skip the first one to avoid headers
            if($offset == 0) {
                return true;
            }
            $data = array_combine($headers, $row);
            $collected->push($data);
            return true;
        });
        return $collected;
    }

    public function findBottomAlias($collection, $path) {
        $filtered = $collection->where('src', $path);
        if($filtered->count()) {
            $new = $filtered->first();
            $path = $this->findBottomAlias($collection, $new['dst']);
        }
        return $path;
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

    private function cleanUrl($url) {
        $trimmed = BonnierRedirect::trimAddSlash($url, true);
        $unDrupaled = str_replace("/<front>", '/', $trimmed);
        return $unDrupaled;
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

    private static function correctAlias($source, $destination, $locale, $suppressErrors = false) {
        global $wpdb;
        if ($suppressErrors) {
            $wpdb->suppress_errors(true);
        }
        try {
            $wpdb->update(
                'wp_bonnier_redirects',
                ['to' => BonnierRedirect::trimAddSlash($destination)],
                ['to' => BonnierRedirect::trimAddSlash($source), 'locale' => $locale]
            );
        } catch (\Exception $e) {
            return null;
        }
        return true;
    }

    private static function removeSelfDirects($suppressErrors = false) {
        global $wpdb;
        if ($suppressErrors) {
            $wpdb->suppress_errors(true);
        }
        try {
            $wpdb->get_row(
                "DELETE FROM wp_bonnier_redirects WHERE `from` = `to`"
            );
        } catch (\Exception $e) {
            return null;
        }
        return true;
    }

    private static function cleanHashes($suppressErrors = false) {
        global $wpdb;
        if ($suppressErrors) {
            $wpdb->suppress_errors(true);
        }
        try {
            $wpdb->get_row(
                "UPDATE wp_bonnier_redirects
                  SET `to_hash` = MD5(`to`),
                  `from_hash` = MD5(`from`)
                "
            );
        } catch (\Exception $e) {
            return null;
        }
        return true;
    }
}