<?php

namespace Bonnier\WP\Redirect\Model;

use Bonnier\WP\Redirect\Http\BonnierRedirect;

class Tag extends AbstractRedirectionModel
{
    public static function register()
    {
        add_action('edit_post_tag', [__CLASS__, 'save']);
        add_action('create_post_tag', [__CLASS__, 'save']);
        add_action('term_updated_messages', function () {
            global $tag;
            if (isset($tag->term_id)) {
                self::preventSuccessMessageWhenError($tag->term_id);
            }
        });
        add_action('admin_notices', function () {
            global $tag;
            if (isset($tag->term_id)) {
                self::displayErrorMessage($tag->term_id);
            }
        });
        add_action('delete_post_tag', [__CLASS__, 'delete']);
    }

    public static function save($tagId)
    {
        if (!isset($_REQUEST['custom_permalinks_edit']) || isset($_REQUEST['post_ID'])) {
            return;
        }
        $slug = $_REQUEST['custom_permalink'] ?? '';
        $newPermalink = self::trimAddSlash($slug);
        $originalLink = self::trimAddSlash(wp_make_link_relative(get_term_link($tagId)));

        if ($newPermalink == $originalLink) {
            $newPermalink = '';
        }

        // If new URL
        if (!empty($newPermalink)) {
            if ($error = self::invalidSlug($slug)) {
                self::setError('The slug \'' . $slug . '\' seems to be an invalid slug', $tagId);
            } elseif ($error = !BonnierRedirect::handleRedirect(
                $originalLink,
                $newPermalink,
                pll_get_term_language($tagId),
                self::type(),
                $tagId
            )) {
                self::setError('The URL ' . $newPermalink . ' has already been used.', $tagId);
            }
            if ($error) {
                $_REQUEST['custom_permalink'] = $originalLink;
            }
        }
    }

    public static function delete($tagId)
    {
        BonnierRedirect::deleteRedirectFor(self::type(), $tagId);
    }

    public static function type()
    {
        return 'tag';
    }

    public static function setError($errorMessage, $tagId)
    {
        set_transient(BonnierRedirect::getErrorString(self::type(), $tagId), $errorMessage, 45);
    }
}
