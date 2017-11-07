<?php

namespace Bonnier\WP\Redirect\Http;


use Bonnier\WP\Cache\Services\CacheApi;

class BonnierRedirect
{
    public static function register() {
        add_action('template_redirect', function(){
            $requestURI = $_SERVER['REQUEST_URI'];
            // Ask for final redirects
            $redirect = self::recursiveRedirectFinder(self::trimAddSlash($requestURI));
            // If an redirect is found
            if($redirect && isset($redirect->to)) {
                // Redirect to it
                self::redirectTo($redirect->to, $redirect->type, $requestURI);
            }
            // Check case redirect
            if(is_page() || is_category() || preg_match('/^\/(tags)\/?/', $requestURI)) {
                $urlPath = parse_url($requestURI, PHP_URL_PATH);
                if(preg_match('/[A-Z]/', $urlPath))
                {
                    self::redirectTo(strtolower($urlPath), 'case', $requestURI);
                }
            }
            // Else do nothing and let WordPress take over redirection.
        });
    }

    private static function redirectTo($to, $case, $requestURI) {
        header('X-Bonnier-Redirect: '.$case);
        wp_redirect($to . (parse_url($requestURI, PHP_URL_QUERY) ? '?' : '') . parse_url($requestURI, PHP_URL_QUERY), $redirect->code ?? 301);
    }

    public static function getErrorString($type, $id) {
        return "bonner_redirect_save_{$type}_error_{$id}";
    }

    public static function handleRedirect($from, $to, $locale, $type, $id, $code = 301, $suppressWarnings = false) {
        $urlEncodedTo = str_replace('%2F', '/', urlencode($to));
        if(self::redirectExists($urlEncodedTo, $locale)) {
            return false;
        }

        // If a redirect exists from /a to /b and we are trying to make
        // a redirect from /b to /a. Then we need to make sure that
        // /a to /b is removed so we don't make an infinite loop
        self::removeReverse($from, $urlEncodedTo, $locale, $suppressWarnings);

        // After making sure we don't create a redirect loop, we add the new redirect.
        return self::addRedirect($from, $urlEncodedTo, $locale, $type, $id, $code, $suppressWarnings);
    }

    public static function removeFrom($url, $locale) {
        if(self::redirectExists($url, $locale)) {
            global $wpdb;
            try {
                $wpdb->delete('wp_bonnier_redirects', ['from' => $url]);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Check that new url isn't already redirecting
     *
     * @param $new
     * @param $locale
     * @param int $code
     * @return bool|null
     */
    public static function redirectExists($new, $locale, $code = 301) {
        global $wpdb;
        try {
            return $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT count(1) as `count` 
                    FROM wp_bonnier_redirects
                    WHERE `from` = %s AND `locale` = %s",
                        $new,
                        $locale
                    )
                )->count > 0;
        } catch (\Exception $e) {
            return null;
        }
        return false;
    }

    /**
     * @param $from
     * @param $to
     * @param $locale
     * @param bool $suppressErrors
     * @return bool|null
     */
    private static function removeReverse($from, $to, $locale, $suppressErrors = false) {
        global $wpdb;
        if ($suppressErrors) {
            $wpdb->suppress_errors(true);
        }
        $removed = 0;
        try {
            $removed = $wpdb->delete('wp_bonnier_redirects', ['from' => $to, 'to' => $from, 'locale' => $locale]);
        } catch (\Exception $e) {
            return null;
        }
        if($removed > 0) {
            self::cleanBonnierCache($to);
            return true;
        }
        return false;
    }

    /**
     * @param $url
     * @param $locale
     * @param int $code
     * @return bool|null
     */
    public static function urlIsPartOfRedirect($url, $locale, $code = 301) {
        global $wpdb;
        try {
            return $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT count(1) as `count` 
                    FROM wp_bonnier_redirects
                    WHERE `from` = %s AND `locale` = %s",
                        $url,
                        $url,
                        $locale
                    )
                )->count > 0;
        } catch (\Exception $e) {
            return null;
        }
        return false;
    }

    /**
     * @param $from
     * @param $to
     * @param $locale
     * @param $type
     * @param $id
     * @param int $code
     * @param bool $suppressErrors
     * @return bool|null
     */
    public static function addRedirect($from, $to, $locale, $type, $id, $code = 301, $suppressErrors = false) {
        global $wpdb;
        if ($suppressErrors) {
            $wpdb->suppress_errors(true);
        }
        try {
            $wpdb->get_row(
                $wpdb->prepare(
                    "INSERT INTO `wp_bonnier_redirects` 
                    (`from`, `from_hash`, `paramless_from_hash`, `to`, `to_hash`, `locale`, `type`, `wp_id`, `code`) 
                    VALUES (%s, MD5(%s), MD5(%s), %s, MD5(%s), %s, %s, %s, %d)",
                    [
                        $fromUrl = self::trimAddSlash($from),
                        $fromUrl,
                        self::trimAddSlash($fromUrl, false),
                        $toUrl = self::trimAddSlash($to),
                        $toUrl,
                        $locale,
                        $type,
                        $id,
                        $code
                    ]
                )
            );
        } catch (\Exception $e) {
            return null;
        }
        self::cleanBonnierCache($from);
        return true;
    }

    public static function deleteRedirect($id, $suppressErrors = false) {
        global $wpdb;
        if ($suppressErrors) {
            $wpdb->suppress_errors(true);
        }
        $from = self::getFromInRedirect($id);
        try {
            $wpdb->delete('wp_bonnier_redirects', ['id' => $id]);
        } catch (\Exception $e) {
            return null;
        }
        self::cleanBonnierCache($from);
        return true;
    }

    private static function cleanBonnierCache($url) {
        // This doesn't actually work since the class has to exist
        // to even import the class in the class, but it shows
        // a pretty logical dependency for calling this.
        if(class_exists(CacheApi::class)) {
            CacheApi::post(CacheApi::CACHE_UPDATE, rtrim('/', pll_home_url()) . $url);
        }
    }

    private static function getFromInRedirect($id) {
        try {
            global $wpdb;
            $from = $wpdb->get_var("SELECT `from` FROM wp_bonnier_redirects WHERE id = $id");
        } catch (\Exception $e) {
            $from = '';
        }
        return $from;
    }

    /**
     * Finds the final redirect of a given uri
     *
     * @param $from
     * @return mixed
     */
    private static function recursiveRedirectFinder($from) {
        $redirect = self::findRedirectFor($from);
        // If it is an actual redirect
        if($redirect && isset($redirect->to)) {
            // Find next redirect
            $next = self::recursiveRedirectFinder($redirect->to);
            // If it the next is also an redirect
            if($next && isset($next->to)) {
                // Update self with new final destination
                self::updateRedirect($redirect->from, $next->to);
                // Return final destination
                return $next;
            }
        }
        // return original since no redirect was found
        return $redirect;
    }

    /**
     * Finds a redirect in the database for a given uri
     *
     * @param $uri
     * @return array|null|object|void['a' => $newTo, 'b' => $from]
     */
    private static function findRedirectFor($uri) {
        global $wpdb;
        try {
            $redirect = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `wp_bonnier_redirects` WHERE `from` = %s AND `locale` = %s",
                    $uri,
                    function_exists('pll_current_language') ? pll_current_language() : ''
                )
            );
        } catch (\Exception $e) {
            $redirect = null;
        }
        return $redirect;
    }

    /**
     * Updates a redirect in the database
     *
     * @param $from
     * @param $newTo
     * @return bool
     */
    private static function updateRedirect($from, $newTo) {
        global $wpdb;
        try {
            $wpdb->get_row(
                $wpdb->prepare(
                    "UPDATE wp_bonnier_redirects
                     SET `to` = %s
                     WHERE `from` = %s;
                    ",
                    $newTo,
                    $from
                )
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public static function deleteRedirectFor($type, $id) {
        global $wpdb;
        try {
            $wpdb->get_row(
                $wpdb->prepare(
                    "DELETE FROM wp_bonnier_redirects
                     WHERE `type` = %s
                     AND `wp_id` = %s
                    ",
                    $type,
                    $id
                )
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public static function trimAddSlash($url, $withQueryParams = true, $start = true, $end = false) {
        if(empty($url)) {
            return null;
        }
        return ($start ? '/' : '')
            . trim(parse_url($url, PHP_URL_PATH), '/')
            . ($withQueryParams ? self::sortQueryParams($url) : '')
            . ($end ? '/' : '');
    }

    private static function sortQueryParams($url) {
        $params = preg_split('/\&/', parse_url($url, PHP_URL_QUERY), -1, PREG_SPLIT_NO_EMPTY);
        if(empty($params) || !sort($params)) {
            return '';
        }
        return '?' . implode('&', $params);
    }

    public static function paginateFetchRedirect($page, $filterTo, $filterFrom, $locale, $perPage = 20) {
        global $wpdb;
        try {
            $count = $wpdb->get_results(
                $wpdb->prepare(
                    "
                        SELECT count(*) 
                        FROM `wp_bonnier_redirects` 
                        WHERE `to` LIKE '%%%s%%' AND 
                        `from` LIKE '%%%s%%' AND 
                        `locale` LIKE '%%%s%%'
                    ",
                    $filterTo,
                    $filterFrom,
                    $locale,
                    $perPage,
                    $perPage * ($page - 1)
                )
            );
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "
                        SELECT * 
                        FROM `wp_bonnier_redirects` 
                        WHERE `to` LIKE '%%%s%%' AND 
                        `from` LIKE '%%%s%%' AND 
                        `locale` LIKE '%%%s%%'
                        ORDER BY id
                        LIMIT %d
                        OFFSET %d
                    ",
                    $filterTo,
                    $filterFrom,
                    $locale,
                    $perPage,
                    $perPage * ($page - 1)
                )
            );
            return [$results, (int) (isset($count['0']) ? $count['0']->{'count(*)'} : 0) ?? 0];
        } catch (\Exception $e) {
            return false;
        }
        return null;
    }
}