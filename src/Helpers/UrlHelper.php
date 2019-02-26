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
        return strtolower($endsWithoutSlash);
    }

    /**
     * Normalize a path, sorting query params, ensuring formatting of path etc.
     *
     * @param string $url
     * @return string
     */
    public static function normalizePath(string $url): string
    {
        $decoded = self::decode($url);
        $path = self::sanitizePath($decoded);
        if ($queryParams = self::parseQueryParams($decoded)) {
            $params = '?';
            foreach ($queryParams as $key => $value) {
                $params .= sprintf('%s=%s&', $key, $value);
            }
            $params = substr($params, 0, -1);
            return sprintf('%s%s', $path, $params);
        }

        return $path ?: '/';
    }

    public static function normalizeUrl(string $url): string
    {
        $parsedUrl = parse_url(self::decode($url));
        $scheme = isset($parsedUrl['scheme']) ? strtolower($parsedUrl['scheme']) . '://' : '';
        $host = strtolower($parsedUrl['host'] ?? '');
        $normalizedUrl = rtrim($scheme . $host . self::normalizePath($url), '/');
        foreach (LocaleHelper::getLocalizedUrls() as $domain) {
            if (starts_with($normalizedUrl, $domain)) {
                return str_after($normalizedUrl, $domain) ?: '/';
            }
        }
        return $normalizedUrl ?: '/';
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
            parse_str($query, $params);
            ksort($params);
            foreach ($params as $key => $item) {
                if (is_array($item)) {
                    sort($item);
                }
                $params[$key] = $item;
            }
            return $params;
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
