<?php

namespace Bonnier\WP\Redirect\Model;


use Bonnier\WP\Redirect\Http\BonnierRedirect;

class Post extends AbstractRedirectionModel
{

    public static function register() {
        add_action('save_page', [__CLASS__, 'save'], 5);
        add_action('save_post', [__CLASS__, 'save'], 5);
        add_action('post_updated_messages', function () {
            global $post;
            if(isset($post->ID)) {
                self::preventSuccessMessageWhenError($post->ID);
            }
        });
        add_action('admin_notices', function() {
            global $post;
            if(isset($post->ID)) {
                self::displayErrorMessage($post->ID);
            }
        });
        add_action('delete_post', [__CLASS__, 'delete'], 8);
    }

    public static function save($id) {
        $original_link = self::trimAddSlash(wp_make_link_relative(get_permalink($id)));
        $new_link = self::trimAddSlash($_REQUEST['custom_permalink'] ?? '');

        // If new URL
        if (!empty($new_link) && $new_link != $original_link ) {
            $result = BonnierRedirect::handleRedirect($original_link, $new_link, pll_get_post_language($id), self::type(), $id);
            if(!$result) {
                global $post;
                set_transient("bonner_redirect_save_post_error_{$post->ID}", 'The URL ' . $new_link . ' has already been used.', 45);
                $_REQUEST['custom_permalink'] = $original_link;
            }
        }
    }

    public static function delete($id) {
        BonnierRedirect::deleteRedirectFor(self::type(), $id);
    }

    public static function type()
    {
        return 'post';
    }
}