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
            $redirectsMade      = 0;

            $categories = $this->getCategories($post->ID);

            $cat1 = BonnierRedirect::trimAddSlash($categories[0] ?? null);
            $cat2 = BonnierRedirect::trimAddSlash($categories[1] ?? null);
            $cat3 = BonnierRedirect::trimAddSlash($categories[2] ?? null);

            /**
             * CASE Z: $cat1
             * CASE X: $cat/$cat2
             * CASE Y: $cat1/$cat2/$cat3
             * CASE O: $cat2
             * CASE P: $cat2/$cat3
             * CASE Q: $cat3
             */
            if ($cat1) { // CASE Z
                $categoryZA = BonnierRedirect::trimAddSlash($cat1.$wpTitleSlug);
                $categoryZB = BonnierRedirect::trimAddSlash($cat1.$postName);
                $this->makeCategoryRedirects($categoryZA, $categoryZB, $redirectTo, $postLocale, $post, $redirectsMade);
            }

            if ($cat1 && $cat2) { // CASE X
                $categoryXA = BonnierRedirect::trimAddSlash($cat1.$cat2.$wpTitleSlug);
                $categoryXB = BonnierRedirect::trimAddSlash($cat1.$cat2.$postName);
                $this->makeCategoryRedirects($categoryXA, $categoryXB, $redirectTo, $postLocale, $post, $redirectsMade);
            }

            if ($cat1 && $cat2 && $cat3) { // CASE Y
                $categoryYA = BonnierRedirect::trimAddSlash($cat1.$cat2.$cat3.$wpTitleSlug);
                $categoryYB = BonnierRedirect::trimAddSlash($cat1.$cat2.$cat3.$postName);
                $this->makeCategoryRedirects($categoryYA, $categoryYB, $redirectTo, $postLocale, $post, $redirectsMade);
            }

            if ($cat2) { // CASE O
                $categoryOA = BonnierRedirect::trimAddSlash($cat2.$wpTitleSlug);
                $categoryOB = BonnierRedirect::trimAddSlash($cat2.$postName);
                $this->makeCategoryRedirects($categoryOA, $categoryOB, $redirectTo, $postLocale, $post, $redirectsMade);
            }

            if ($cat2 && $cat3) { // CASE P
                $categoryPA = BonnierRedirect::trimAddSlash($cat2.$cat3.$wpTitleSlug);
                $categoryPB = BonnierRedirect::trimAddSlash($cat2.$cat3.$postName);
                $this->makeCategoryRedirects($categoryPA, $categoryPB, $redirectTo, $postLocale, $post, $redirectsMade);
            }

            if ($cat3) { // CASE Q
                $categoryQA = BonnierRedirect::trimAddSlash($cat3.$wpTitleSlug);
                $categoryQB = BonnierRedirect::trimAddSlash($cat3.$postName);
                $this->makeCategoryRedirects($categoryQA, $categoryQB, $redirectTo, $postLocale, $post, $redirectsMade);
            }

            if ($wpTitleSlug && $wpTitleSlug != $redirectTo) {
                if(BonnierRedirect::handleRedirect($wpTitleSlug, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true)) {
                    $redirectsMade++;
                }
            }
            if ($postName && $postName != $redirectTo) {
                if(BonnierRedirect::handleRedirect($postName, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true)) {
                    $redirectsMade++;
                }
            }
            if ($customPermalink && $customPermalink != $redirectTo) {
                if(BonnierRedirect::handleRedirect($customPermalink, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true)) {
                    $redirectsMade++;
                }
            }

            BonnierRedirect::removeFrom($redirectTo, $postLocale);

            WP_CLI::line(sprintf("Made %s redirects on %s: %s (%s)", $redirectsMade, $post->ID, $post->post_title, $postLocale));
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

    private function makeCategoryRedirects($catA, $catB, $redirectTo, $postLocale, $post, &$redirectsMade)
    {
        if ($catA != $redirectTo) {
            if(BonnierRedirect::handleRedirect($catA, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true)) {
                $redirectsMade++;
            }
        }
        if ($catB != $redirectTo) {
            if(BonnierRedirect::handleRedirect($catB, $redirectTo, $postLocale, static::TYPE, $post->ID, 301, true)) {
                $redirectsMade++;
            }
        }
    }
}