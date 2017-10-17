<?php

namespace Bonnier\WP\Redirect\Model;


use Bonnier\WP\Redirect\Http\BonnierRedirect;

class Post extends AbstractRedirectionModel
{
    private static $post;

    public static function register() {
        add_action('save_page', [__CLASS__, 'save'], 5, 2);
        add_action('save_post', [__CLASS__, 'save'], 5, 2);
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

    public static function save($id, $post) {
        if (wp_is_post_revision($id) || wp_is_post_autosave($id)) {
            return;
        }

        if($post && isset($post->post_status) && $post->post_status !== 'publish') {
            return;
        }

        self::$post = $post;

        $original_link = self::trimAddSlash(wp_make_link_relative(get_permalink($id)));
        $slug = $_REQUEST['custom_permalink'] ?? '';
        $new_link = self::trimAddSlash($slug);
        // If new URL
        if ($new_link != '/' && !empty($new_link) && $new_link != $original_link ) {
            if($error = self::invalidSlug($slug)) {
                $parsedUrl = parse_url($slug);
                $toBeRemoved = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
                self::setError('The slug \'' . $slug . '\' seems to be an invalid slug');
            } else if ($error = !BonnierRedirect::handleRedirect($original_link, $new_link, pll_get_post_language($id), self::type(), $id)) {
                self::setError('The URL ' . $new_link . ' has already been used.');
            }
            if($error) {
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

    public static function setError($errorMessage) {
        if(self::$post) {
            set_transient(BonnierRedirect::getErrorString(self::type(), self::$post->ID), $errorMessage, 45);
        }
    }

}