<?php


namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Http\BonnierRedirect;
use WP_CLI;
use Bonnier\WP\ContentHub\Editor\Models\WpComposite;

class RedirectFixer
{
    const CMD_NAMESPACE = 'bonnier redirect fix';
    const TYPE = 'Google Index Fix';

    public static function register() {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }


    /**
     * Runs the redirect fix
     *
     * ## OPTIONS
     *
     * ## EXAMPLES
     *
     * wp bonnier redirect fix run
     */
    public function run()
    {
        WpComposite::map_all(function (\WP_Post $post) {
            global $locale;
            $postLocale         = pll_get_post_language($post->ID);
            $locale             = $this->parseLocale($postLocale);
            $title              = $post->post_title;
            $wpTitleSlug        = BonnierRedirect::trimAddSlash(sanitize_title($title));
            $postName           = BonnierRedirect::trimAddSlash($post->post_name);
            $customPermalink    = BonnierRedirect::trimAddSlash(get_post_meta($post->ID, 'custom_permalink', true));
            $redirectTo         = BonnierRedirect::trimAddSlash(parse_url(get_permalink($post->ID), PHP_URL_PATH));

            $categories = $this->getCategories($post->ID);

            $cat1 = BonnierRedirect::trimAddSlash($categories[0] ?? null);
            $cat2 = BonnierRedirect::trimAddSlash($categories[1] ?? null);
            $cat3 = BonnierRedirect::trimAddSlash($categories[2] ?? null);

            if ($cat1) {
                $category1a = BonnierRedirect::trimAddSlash($cat1.$wpTitleSlug);
                $category1b = BonnierRedirect::trimAddSlash($cat1.$postName);
                if ($category1a != $redirectTo) {
                    BonnierRedirect::handleRedirect($category1a, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
                }
                if ($category1b != $redirectTo) {
                    BonnierRedirect::handleRedirect($category1b, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
                }
            }
            if ($cat1 && $cat2) {
                $category2a = BonnierRedirect::trimAddSlash($cat1.$cat2.$wpTitleSlug);
                $category2b = BonnierRedirect::trimAddSlash($cat1.$cat2.$postName);
                if ($category2a != $redirectTo) {
                    BonnierRedirect::handleRedirect($category2a, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
                }
                if ($category2b != $redirectTo) {
                    BonnierRedirect::handleRedirect($category2b, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
                }
            }

            if ($cat1 && $cat2 && $cat3) {
                $category3a = BonnierRedirect::trimAddSlash($cat1.$cat2.$cat3.$wpTitleSlug);
                $category3b = BonnierRedirect::trimAddSlash($cat1.$cat2.$cat3.$postName);
                if ($category3a != $redirectTo) {
                    BonnierRedirect::handleRedirect($category3a, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
                }
                if ($category3b != $redirectTo) {
                    BonnierRedirect::handleRedirect($category3b, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
                }
            }

            if ($wpTitleSlug && $wpTitleSlug != $redirectTo) {
                BonnierRedirect::handleRedirect($wpTitleSlug, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
            }
            if ($postName && $postName != $redirectTo) {
                BonnierRedirect::handleRedirect($postName, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
            }
            if ($customPermalink && $customPermalink != $redirectTo) {
                BonnierRedirect::handleRedirect($customPermalink, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true);
            }

            WP_CLI::line(sprintf("Fixed %s: %s (%s)", $post->ID, $post->post_title, $postLocale));
        });

        WP_CLI::success("DONE!");
    }

    private function parseLocale($locale)
    {
        $locales = [
            'da' => 'da_DK',
            'sv' => 'sv_SE',
            'nb' => 'nb_NO',
            'fi' => 'fi_FI',
        ];
        return $locales[$locale];
    }

    private function getCategories($postID)
    {
        $terms = wp_get_object_terms($postID, 'category');
        if (empty($terms)) { // no category attached we mus use the default url generated by WordPress
            return collect([]);
        }

        $category = $terms[0];
        $slugs = collect([]);
        $hasParent = true;
        while ($hasParent) {
            $slugs->push($category->slug);
            if ($category->parent === 0) {
                $hasParent = false;
            } else {
                $category = get_term($category->parent);
            }
        }

        return $slugs->reverse()->values();
    }
}