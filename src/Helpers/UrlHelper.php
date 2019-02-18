<?php

namespace Bonnier\WP\Redirect\Helpers;

class UrlHelper
{
    /**
     * Decode url, ensuring formatting of path, removing query params.
     *
     * @param string $url
     * @return string
     */
    public static function sanitizePath(string $url): string
    {
        $decoded = self::decode($url);
        $path = parse_url($decoded, PHP_URL_PATH);
        $beginsWithSlash = str_start($path, '/');
        $endsWithoutSlash = rtrim($beginsWithSlash, '/');
        return $endsWithoutSlash;
    }

    /**
     * Normalize a path, sorting query params, ensuring formatting of path etc.
     *
     * @param string $url
     * @return string
     */
    public static function normalizePath(string $url): string
    {
        $path = self::sanitizePath($url);
        if ($queryParams = self::parseQueryParams($url)) {
            $params = '?';
            foreach ($queryParams as $key => $value) {
                $params .= sprintf('%s=%s&', $key, $value);
            }
            $params = substr($params, 0, -1);
            return sprintf('%s%s', $path, $params);
        }

        return $path;
    }

    /**
     * Convert a url to an associative array, sorted by it's keys.
     *
     * @param string $url
     * @return array|null
     */
    public static function parseQueryParams(string $url): ?array
    {
        if ($query = parse_url($url, PHP_URL_QUERY)) {
            return collect(explode('&', $query))->mapWithKeys(function ($queryParam) {
                $parts = explode('=', $queryParam);
                return [$parts[0] => $parts[1]];
            })->sortKeys()->toArray();
        }

        return null;
    }

    /**
     * Recursively urldecode a url.
     *
     * @param string $url
     * @return string
     */
    public static function decode(string $url): string
    {
        $decoded = urldecode($url);
        if ($decoded === $url) {
            return $decoded;
        }

        return self::decode($decoded);
    }
}
