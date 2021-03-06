<?php

namespace Bonnier\WP\Redirect\Helpers;

use Illuminate\Support\Str;

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
        $beginsWithSlash = Str::start($path, '/');
        $endsWithoutSlash = rtrim($beginsWithSlash, '/');
        return mb_strtolower($endsWithoutSlash);
    }

    /**
     * Normalize a path, sorting query params, ensuring formatting of path etc.
     *
     * @param string $url
     * @param bool $disallowWildcard
     * @return string
     */
    public static function normalizePath(string $url, bool $disallowWildcard = false): string
    {
        if (Str::startsWith($url, 'www.')) {
            $url = 'http://' . $url;
        }
        $decoded = self::decode($url);
        $path = self::sanitizePath($decoded);
        if ($queryParams = self::parseQueryParams($decoded)) {
            $params = '?';
            foreach ($queryParams as $key => $value) {
                $params .= sprintf('%s=%s&', $key, $value);
            }
            $params = mb_substr($params, 0, -1);
            $path = sprintf('%s%s', $path, $params);
        }

        if ($disallowWildcard) {
            $path = rtrim(rtrim($path, '*'), '/');
        }

        if (mb_substr($path, 0, 1) === '?') {
            $path = Str::start($path, '/');
        }

        return $path ?: '/';
    }

    /**
     * @param string $url
     * @param bool $disallowWildcard
     * @return string
     */
    public static function normalizeUrl(string $url, bool $disallowWildcard = false): string
    {
        if (Str::startsWith($url, 'www.')) {
            $url = 'http://' . $url;
        }
        $parsedUrl = parse_url(self::decode($url));
        $scheme = isset($parsedUrl['scheme']) ? mb_strtolower($parsedUrl['scheme']) . '://' : '';
        $host = mb_strtolower($parsedUrl['host'] ?? '');
        $normalizedUrl = rtrim($scheme . $host . self::normalizePath($url, $disallowWildcard), '/');
        foreach (LocaleHelper::getLocalizedUrls() as $domain) {
            if (Str::startsWith($normalizedUrl, $domain)) {
                return Str::after($normalizedUrl, $domain) ?: '/';
            }
        }
        if (mb_substr($normalizedUrl, 0, 1) === '?') {
            $normalizedUrl = Str::start($normalizedUrl, '/');
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
