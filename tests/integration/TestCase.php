<?php

namespace Bonnier\WP\Redirect\Tests\integration;

use Bonnier\WP\Redirect\Models\Redirect;
use Codeception\TestCase\WPTestCase;

class TestCase extends WPTestCase
{
    public static function _setUpBeforeClass()
    {
        /** @var \WP_Rewrite $wp_rewrite */
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%category%/%postname%/');
        $wp_rewrite->add_permastruct('category', '/%category%');
        $wp_rewrite->add_permastruct('post_tag', '/%post_tag%');
        $wp_rewrite->flush_rules();
        return parent::_setUpBeforeClass();
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

    protected function assertRedirect(
        int $wpID,
        Redirect $redirect,
        string $fromSlug,
        string $toSlug,
        string $type,
        int $status = 301
    ) {
        $this->assertSame($fromSlug, $redirect->getFrom(), 'Expected \'from\'-slug does not match actual \'from\'-slug');
        $this->assertSame($toSlug, $redirect->getTo(), 'Expected \'to\'-slug does not match actual \'to\'-slug');
        $this->assertSame($status, $redirect->getCode(), 'Expected redirect code does not match actual redirect code');
        $this->assertSame($wpID, $redirect->getWpID(), 'Expected WP ID does not match actual WP ID');
        $this->assertSame($type, $redirect->getType(), 'Expected redirect type does not match actual redirect type');
    }

    protected function assertSameRedirects(Redirect $expectedRedirect, Redirect $actualRedirect)
    {
        $this->assertSame($expectedRedirect->getID(), $actualRedirect->getID());
        $this->assertSame($expectedRedirect->getFrom(), $actualRedirect->getFrom());
        $this->assertSame($expectedRedirect->getFromHash(), $actualRedirect->getFromHash());
        $this->assertSame($expectedRedirect->getTo(), $actualRedirect->getTo());
        $this->assertSame($expectedRedirect->getToHash(), $actualRedirect->getToHash());
        $this->assertSame($expectedRedirect->getType(), $actualRedirect->getType());
        $this->assertSame($expectedRedirect->getWpID(), $actualRedirect->getWpID());
        $this->assertSame($expectedRedirect->getLocale(), $actualRedirect->getLocale());
        $this->assertSame($expectedRedirect->getCode(), $actualRedirect->getCode());
        $this->assertSame($expectedRedirect->getParamlessFromHash(), $actualRedirect->getParamlessFromHash());
    }

    protected function getData(string $filename): string
    {
        return sprintf('%s/_data/%s', rtrim(dirname(__DIR__), '/'), ltrim($filename, '/'));
    }

    private function trimUrl(string $url)
    {
        return rtrim(parse_url($url, PHP_URL_PATH), '/');
    }
}
