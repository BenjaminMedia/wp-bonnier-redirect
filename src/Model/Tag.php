<?php

namespace Bonnier\WP\Redirect\Model;


use Bonnier\WP\Redirect\Http\BonnierRedirect;

class Tag extends AbstractRedirectionModel
{
    public static function register() {
        add_action('edit_post_tag', [__CLASS__, 'save']);
        add_action('create_post_tag', [__CLASS__, 'save']);
        add_action('term_updated_messages', function () {
            global $tag;
            if(isset($tag->term_id)) {
                self::preventSuccessMessageWhenError($tag->term_id);
            }
        });
        add_action('admin_notices', function() {
            global $tag;
            if(isset($tag->term_id)) {
                self::displayErrorMessage($tag->term_id);
            }
        });
        add_action('delete_post_tag', [__CLASS__, 'delete']);
    }

    public static function save($id) {
        if ( !isset($_REQUEST['custom_permalinks_edit']) || isset($_REQUEST['post_ID']) ) return;
        $slug = $_REQUEST['custom_permalink'] ?? '';
        $newPermalink = self::trimAddSlash($slug);
        $original_link = self::trimAddSlash(wp_make_link_relative(get_term_link($id)));

        if ( $newPermalink == $original_link) {
            $newPermalink = '';
        }

        // If new URL
        if (!empty($newPermalink)) {
            if($error = self::invalidSlug($slug)) {
                $parsedUrl = parse_url($slug);
                $toBeRemoved = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
                self::setError('The slug \'' . $slug . '\' seems to be an invalid slug', $id);
            } else if ($error = !BonnierRedirect::handleRedirect($original_link, $newPermalink, pll_get_term_language($id), self::type(), $id)) {
                self::setError('The URL ' . $newPermalink . ' has already been used.', $id);
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
        return 'tag';
    }

    public static function setError($errorMessage, $id) {
        set_transient(BonnierRedirect::getErrorString(self::type(), $id), $errorMessage, 45);
    }
}