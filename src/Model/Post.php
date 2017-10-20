<?php

namespace Bonnier\WP\Redirect\Model;


use Bonnier\WP\Redirect\Http\BonnierRedirect;

class Post extends AbstractRedirectionModel
{
    // Figure out a way to dynamically fetch this variable - otherwise
    // REMEMBER to update this field whenever ACF field keys are updated
    // if they ever are.
    const ACF_CATEGORY_ID = 'field_58e39a7118284';

    private static $newPost;

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

    public static function save($id, $newPost) {
        global $post;
        if (wp_is_post_revision($id) || wp_is_post_autosave($id)) {
            return;
        }

        if($newPost && isset($newPost->post_status) && $newPost->post_status !== 'publish') {
            return;
        }

        self::$newPost = $newPost;

        $oldCategory = null;
        $newCategory = null;

        $oldCategoryTerms = get_the_category($post->ID);
        if($oldCategoryTerms && !empty($oldCategoryTerms)) {
            $oldCategory = $oldCategoryTerms[0];
        }

        $newCategoryId = $_REQUEST['acf'][static::ACF_CATEGORY_ID] ?? null;
        if($newCategoryId) {
            $newCategory = get_term($newCategoryId);
        }

        $oldLink = self::getCategories($oldCategory).'/'.$post->post_name;
        $newLink = self::getCategories($newCategory).'/'.$newPost->post_name;

        if($oldLink != $newLink) {
            if(self::invalidSlug($newLink)) {
                self::setError('The slug \'' . $newLink . '\' seems to be an invalid slug');
            } else if (!BonnierRedirect::handleRedirect($oldLink, $newLink, pll_get_post_language($id), self::type(), $id)) {
                self::setError('The URL ' . $newLink . ' has already been used.');
            }
        }
    }

    public static function delete($id) {
        BonnierRedirect::deleteRedirectFor(self::type(), $id);
    }

    private static function getCategories($category)
    {
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

        return '/'.trim($slugs->reverse()->implode('/'), '/');
    }

    public static function type()
    {
        return 'post';
    }

    public static function setError($errorMessage) {
        if(self::$newPost) {
            set_transient(BonnierRedirect::getErrorString(self::type(), self::$newPost->ID), $errorMessage, 45);
        }
    }

}