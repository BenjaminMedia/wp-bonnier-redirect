<?php

namespace Bonnier\WP\Redirect\Model;

use Bonnier\WP\Redirect\Http\BonnierRedirect;

// THIS CLASS IS DEPRECATED
class Category extends AbstractRedirectionModel
{
    public static function register()
    {
//        add_action('edited_category', [__CLASS__, 'save'], 1);
//        add_action('create_category', [__CLASS__, 'save'], 1);
//        add_action('post_updated_messages', function () {
//            global $tag;
//            if(isset($tag->term_id)) {
//                self::preventSuccessMessageWhenError($tag->term_id);
//            }
//        });
//        add_action('admin_notices', function() {
//            global $tag;
//            if(isset($tag->term_id)) {
//                self::displayErrorMessage($tag->term_id);
//            }
//        });
//        add_action('delete_post_category', [__CLASS__, 'delete']);
    }

    public static function save($categoryId)
    {
        if (!isset($_REQUEST['custom_permalinks_edit']) || isset($_REQUEST['post_ID'])) {
            return;
        }
        $newPermalink = ltrim(stripcslashes($_REQUEST['custom_permalink']), "/");
        $originalLink = custom_permalinks_permalink_for_term($categoryId);

        if ($newPermalink == $originalLink) {
            $newPermalink = '';
        }

        // $term = get_term($categoryId, 'category');

        // If new URL
        if (!empty($newPermalink)) {
            $result = BonnierRedirect::handleRedirect(
                $originalLink,
                $newPermalink,
                pll_get_term_language($categoryId),
                self::type(),
                $categoryId
            );
            if (!$result) {
                set_transient(
                    BonnierRedirect::getErrorString(self::type(), $categoryId),
                    'The URL ' . $newPermalink . ' has already been used.',
                    45
                );
                $_REQUEST['custom_permalinks_edit'] = $originalLink;
            }
        }
    }

    public static function delete($categoryId)
    {
        BonnierRedirect::deleteRedirectFor(self::type(), $categoryId);
    }

    public static function type()
    {
        return 'category';
    }
}
