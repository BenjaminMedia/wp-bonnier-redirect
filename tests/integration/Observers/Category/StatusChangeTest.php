<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Category;

use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class StatusChangeTest extends ObserverTestCase
{
    public function testCategoryDeletionCreatesRedirect()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);

        $this->assertSame('/dinosaur', $this->getCategorySlug($category));

        wp_delete_category($category->term_id);

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            $category->term_id,
            $redirects->first(),
            '/dinosaur',
            '/',
            'category-deleted'
        );
    }

    public function testSubcategoryDeletionCreatesRedirects()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);
        $subCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $category->term_id,
        ]);

        $posts = [
            $this->getPost(['post_category' => [$subCategory->term_id]]),
            $this->getPost(['post_category' => [$subCategory->term_id]]),
            $this->getPost(['post_category' => [$subCategory->term_id]]),
            $this->getPost(['post_category' => [$subCategory->term_id]]),
        ];

        $this->assertSame('/dinosaur/carnivorous', $this->getCategorySlug($subCategory));

        foreach ($posts as $post) {
            $this->assertSame('/dinosaur/carnivorous/' . $post->post_name, $this->getPostSlug($post));
        }

        wp_delete_category($subCategory->term_id);

        foreach ($posts as $post) {
            $this->assertSame('/dinosaur/' . $post->post_name, $this->getPostSlug($post));
        }

        $redirects = $this->findAllRedirects();

        $this->assertCount(5, $redirects);

        $firstRedirect = $redirects->shift();

        $this->assertRedirect(
            $subCategory->term_id,
            $firstRedirect,
            '/dinosaur/carnivorous',
            '/dinosaur',
            'category-deleted'
        );
        foreach ($posts as $index => $post) {
            $redirect = $redirects->get($index);
            $this->assertRedirect(
                $post->ID,
                $redirect,
                '/dinosaur/carnivorous/' . $post->post_name,
                '/dinosaur/' . $post->post_name,
                'post-slug-change'
            );
        }
    }

    public function testCanDeleteTopCategoryAndRedirectsAreCreated()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);

        $posts = [
            $this->getPost(['post_category' => [$category->term_id]]),
            $this->getPost(['post_category' => [$category->term_id]]),
            $this->getPost(['post_category' => [$category->term_id]]),
            $this->getPost(['post_category' => [$category->term_id]]),
        ];

        $this->assertSame('/dinosaur', $this->getCategorySlug($category));

        foreach ($posts as $post) {
            $this->assertSame('/dinosaur/' . $post->post_name, $this->getPostSlug($post));
        }

        wp_delete_category($category->term_id);

        foreach ($posts as $post) {
            $this->assertSame('/uncategorized/' . $post->post_name, $this->getPostSlug($post));
        }

        $redirects = $this->findAllRedirects();
        $this->assertCount(5, $redirects);

        $categoryRedirect = $redirects->shift();
        $this->assertRedirect(
            $category->term_id,
            $categoryRedirect,
            '/dinosaur',
            '/',
            'category-deleted'
        );

        foreach ($posts as $index => $post) {
            $redirect = $redirects->get($index);
            $this->assertRedirect(
                $post->ID,
                $redirect,
                '/dinosaur/' . $post->post_name,
                '/uncategorized/' . $post->post_name,
                'post-slug-change'
            );
        }
    }
}
