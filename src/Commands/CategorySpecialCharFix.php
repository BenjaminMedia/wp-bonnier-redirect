<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\ContentHub\Editor\Models\WpComposite;
use Bonnier\WP\Redirect\Http\BonnierRedirect;
use WP_CLI;

class CategorySpecialCharFix
{
    const CMD_NAMESPACE = 'bonnier redirect category slug';

    public static function register()
    {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }

    /**
     * ## OPTIONS
     *
     * <search>
     * : the current slug
     *
     * <replace>
     * : the old category slug
     *
     * <locale>
     * : The name of the person to greet.
     *
     * wp bonnier redirect category slug special_char_fix
     */
    public function special_char_fix($args, $assocArgs)
    {
        list( $search, $replace, $locale ) = $args;

        collect(get_posts([
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'post_type' => WpComposite::POST_TYPE,
        ]))->each(function(\WP_Post $post) use($search, $replace, $locale) {
            $currentPermalink = get_permalink($post->ID);
            $currentPath = parse_url($currentPermalink, PHP_URL_PATH);
            $splitPath = explode('/', $currentPath);
            for($i=0; $i<sizeof($splitPath)-1; $i++){
                if($splitPath[$i] === $search) {
                    $splitPath[$i] = $replace;
                    $fromPath = implode('/',$splitPath);
                    WP_CLI::line(sprintf('creating redirect from: %s to: %s', $fromPath, $currentPath));
                    $result = BonnierRedirect::createRedirect($fromPath, $currentPath, $locale, 'category-special-char-fix', $post->ID);
                    if($result['success']) {
                        WP_CLI::success($result['message']);
                    } else {
                        WP_CLI::warning($result['message']);
                    }
                }
            }
        });

        WP_CLI::success("Done fixing redirects");
    }
}
