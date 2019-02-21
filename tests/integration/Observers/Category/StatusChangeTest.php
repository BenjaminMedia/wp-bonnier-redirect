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

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);

        $this->assertSame('/dinosaur', $redirects->first()->getFrom());
        $this->assertSame('/', $redirects->first()->getTo());
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

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(5, $redirects);

        $firstRedirect = $redirects->shift();

        $this->assertSame('/dinosaur/carnivorous', $firstRedirect->getFrom());
        $this->assertSame('/dinosaur', $firstRedirect->getTo());
        foreach ($posts as $index => $post) {
            $redirect = $redirects->get($index);
            $this->assertSame('/dinosaur/carnivorous/' . $post->post_name, $redirect->getFrom());
            $this->assertSame('/dinosaur/' . $post->post_name, $redirect->getTo());
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

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(5, $redirects);

        $categoryRedirect = $redirects->shift();
        $this->assertSame('/dinosaur', $categoryRedirect->getFrom());
        $this->assertSame('/', $categoryRedirect->getTo());

        foreach ($posts as $index => $post) {
            $redirect = $redirects->get($index);
            $this->assertSame('/dinosaur/' . $post->post_name, $redirect->getFrom());
            $this->assertSame('/uncategorized/' . $post->post_name, $redirect->getTo());
        }
    }
}
