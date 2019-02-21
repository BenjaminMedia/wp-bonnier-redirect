<?php

namespace Bonnier\WP\Redirect\Tests\integration;

use Codeception\TestCase\WPTestCase;

class TestCase extends WPTestCase
{
    public static function setUpBeforeClass()
    {
        /** @var \WP_Rewrite $wp_rewrite */
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%category%/%postname%/');
        $wp_rewrite->add_permastruct('category', '/%category%');
        $wp_rewrite->add_permastruct('post_tag', '/%post_tag%');
        $wp_rewrite->flush_rules();
        return parent::setUpBeforeClass();
    }

    protected function updatePost(int $postID, array $args)
    {
        $_POST['post_ID'] = $postID;
        $_POST = array_merge($_POST, $args);
        edit_post();
    }

    protected function getPostSlug(\WP_Post $post)
    {
        return $this->trimUrl(get_permalink($post->ID));
    }

    protected function getPost(array $args = []): \WP_Post
    {
        return $this->factory()->post->create_and_get($args);
    }

    protected function getCategory(array $args = []): \WP_Term
    {
        return $this->factory()->category->create_and_get($args);
    }

    protected function getCategorySlug(\WP_Term $category)
    {
        return $this->trimUrl(get_category_link($category->term_id));
    }

    protected function getTag(array $args = []): \WP_Term
    {
        return $this->factory()->tag->create_and_get($args);
    }

    protected function getTagSlug(\WP_Term $tag)
    {
        return $this->trimUrl(get_tag_link($tag->term_id));
    }

    /**
     * Asserts whether or not two arrays contains the same
     * items, ignoring the order of the items.
     *
     * @param array $expectedArray
     * @param array $actualArray
     */
    protected function assertArraysAreEqual(array $expectedArray, array $actualArray)
    {
        $this->assertEmpty(array_diff($expectedArray, $actualArray));
    }

    private function trimUrl(string $url)
    {
        return rtrim(parse_url($url, PHP_URL_PATH), '/');
    }
}
