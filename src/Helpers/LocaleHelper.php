<?php

namespace Bonnier\WP\Redirect\Helpers;

class LocaleHelper
{
    public static function getPostLocale(int $postID)
    {
        if (function_exists('pll_get_post_language')) {
            return pll_get_post_language($postID);
        }
        return substr(get_locale(), 0, 2);
    }
}
