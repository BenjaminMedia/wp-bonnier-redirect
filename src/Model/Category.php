<?php

namespace Bonnier\WP\Redirect\Model;


use Bonnier\WP\Redirect\Http\BonnierRedirect;

// THIS CLASS IS DEPRECATED
class Category extends AbstractRedirectionModel
{
    public static function register() {
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

    public static function save($id) {
        if ( !isset($_REQUEST['custom_permalinks_edit']) || isset($_REQUEST['post_ID']) ) return;
        $newPermalink = ltrim(stripcslashes($_REQUEST['custom_permalink']),"/");
        $original_link = custom_permalinks_permalink_for_term($id);

        if ( $newPermalink == $original_link ) {
            $newPermalink = '';
        }

        $term = get_term($id, 'category');

        // If new URL
        if (!empty($newPermalink)) {
            $result = BonnierRedirect::handleRedirect($original_link, $newPermalink, pll_get_term_language($id), self::type(), $id);
            if(!$result) {
                set_transient(BonnierRedirect::getErrorString(self::type(), $id), 'The URL ' . $newPermalink . ' has already been used.', 45);
                $_REQUEST['custom_permalinks_edit'] = $original_link;
            }
        }
    }

    public static function delete($id) {
        BonnierRedirect::deleteRedirectFor(self::type(), $id);
    }

    public static function type()
    {
        return 'category';
    }
}