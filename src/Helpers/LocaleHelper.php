<?php

namespace Bonnier\WP\Redirect\Helpers;

use Illuminate\Support\Str;

class LocaleHelper
{
    private static $languageList;

    /**
     * @param int $postID
     * @return bool|string
     */
    public static function getPostLocale(int $postID)
    {
        if (function_exists('pll_get_post_language')) {
            return pll_get_post_language($postID) ?: self::getDefaultLanguage();
        }
        return self::getDefaultLanguage();
    }

    /**
     * @param int $termID
     * @return bool|string
     */
    public static function getTermLocale(int $termID)
    {
        if (function_exists('pll_get_term_language')) {
            return pll_get_term_language($termID) ?: self::getDefaultLanguage();
        }
        return self::getDefaultLanguage();
    }

    public static function getUrlLocale(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return null;
        }
        foreach (self::getLocalizedUrls() as $locale => $localizedUrl) {
            if (Str::contains($localizedUrl, $host)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public static function getLanguages(): array
    {
        if (function_exists('pll_languages_list')) {
            return pll_languages_list();
        } elseif(self::$languageList) {
            return self::$languageList;
        }

        return [self::getDefaultLanguage()];
    }

    /**
     * @return array|bool
     */
    public static function getTerm($parentId, $lang)
    {
        if (function_exists('pll_get_term')) {
            return pll_get_term($parentId, $lang);
        }

        return [];
    }

    /**
     * @return string
     */
    public static function getLanguage(): string
    {
        if (function_exists('pll_current_language')) {
            return pll_current_language() ?: self::getDefaultLanguage();
        }

        return self::getDefaultLanguage();
    }

    /**
     * @return array
     */
    public static function getLocalizedUrls(): array
    {
        if (($settings = get_option('polylang')) && $domains = $settings['domains']) {
            return $domains;
        }

        return [self::getDefaultLanguage() => home_url()];
    }

    /**
     * This method is purely for testing, since we cannot partially mock this helper class
     *
     * @param array $languageList
     */
    public static function setLanguageList(array $languageList) {
        self::$languageList = $languageList;
    }

    /**
     * @return bool|string
     */
    private static function getDefaultLanguage()
    {
        return substr(get_locale(), 0, 2);
    }
}
