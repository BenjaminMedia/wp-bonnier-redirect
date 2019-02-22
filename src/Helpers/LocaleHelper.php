<?php

namespace Bonnier\WP\Redirect\Helpers;

class LocaleHelper
{
    public static function getPostLocale(int $postID)
    {
        if (function_exists('pll_get_post_language')) {
            return pll_get_post_language($postID);
        }
        return self::getDefaultLanguage();
    }

    public static function getTermLocale(int $termID)
    {
        if (function_exists('pll_get_term_language')) {
            return pll_get_term_language($termID);
        }
        return self::getDefaultLanguage();
    }

    public static function getLanguages(): array
    {
        if (function_exists('pll_languages_list')) {
            return pll_languages_list();
        }

        return [self::getDefaultLanguage()];
    }

    public static function getLanguage(): string
    {
        if (function_exists('pll_current_language')) {
            return pll_current_language() ?: self::getDefaultLanguage();
        }

        return self::getDefaultLanguage();
    }

    private static function getDefaultLanguage()
    {
        return substr(get_locale(), 0, 2);
    }
}
