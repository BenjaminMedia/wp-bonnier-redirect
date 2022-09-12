<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Tag;

use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class StatusChangeTest extends ObserverTestCase
{
    public function _setUp()
    {
        parent::_setUp();
        add_filter('tag_link', function ($tagLink) {
            $parts = parse_url($tagLink);
            return sprintf(
                '%s://%s/tags/%s',
                $parts['scheme'],
                $parts['host'],
                ltrim($parts['path'], '/')
            );
        });
        add_filter('category_link', function ($categoryLink) {
            $parts = parse_url($categoryLink);
            return sprintf(
                '%s://%s/categories/%s',
                $parts['scheme'],
                $parts['host'],
                ltrim($parts['path'], '/')
            );
        });
    }

    public function testDeletingTagRedirectsToFrontpage()
    {
        $tag = $this->getTag([
            'name' => 'Lizards',
            'slug' => 'lizards'
        ]);

        $this->assertSame('/tags/lizards', $this->getTagSlug($tag));

        wp_delete_term($tag->term_id, $tag->taxonomy);

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $tag->term_id,
            $redirects->first(),
            '/tags/lizards',
            '/',
            'tag-deleted'
        );
    }

    public function testDeletingTagWithSameNameAsCategoryRedirectsToCategory()
    {
        $category = $this->getCategory();
        $tag = $this->getTag([
            'name' => $category->name,
            'slug' => $category->slug
        ]);

        $this->assertSame($category->name, $tag->name);
        $this->assertSame($category->slug, $tag->slug);

        wp_delete_term($tag->term_id, $tag->taxonomy);

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $tag->term_id,
            $redirects->first(),
            sprintf('/tags/%s', $tag->slug),
            sprintf('/categories/%s', $category->slug),
            'tag-deleted'
        );
    }

    public function testDeletingTagWithSameNameAsSubCategoryRedirectsToSubCategory()
    {
        $parentCategory = $this->getCategory();
        $subCategory = $this->getCategory([
            'parent' => $parentCategory->term_id
        ]);
        $tag = $this->getTag([
            'name' => $subCategory->name,
            'slug' => $subCategory->slug
        ]);

        $this->assertSame($subCategory->name, $tag->name);
        $this->assertSame($subCategory->slug, $tag->slug);

        wp_delete_term($tag->term_id, $tag->taxonomy);

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $tag->term_id,
            $redirects->first(),
            sprintf('/tags/%s', $tag->slug),
            sprintf('/categories/%s/%s', $parentCategory->slug, $subCategory->slug),
            'tag-deleted'
        );
    }
}
