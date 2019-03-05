<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Post;

use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class CategoryChangeTest extends ObserverTestCase
{
    public function testChangingCategoryCreatesRedirects()
    {
        $category = $this->getCategory();
        $post = $this->getPost([
            'post_category' => [$category->term_id],
        ]);

        try {
            $this->assertNull($this->redirectRepository->findAll());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }

        $newCategory = $this->getCategory();
        $this->updatePost($post->ID, [
            'post_category' => [$newCategory->term_id],
        ]);

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $redirects);
            $this->assertRedirect(
                $post->ID,
                $redirects->first(),
                sprintf('/%s/%s', $category->slug, $post->post_name),
                sprintf('/%s/%s', $newCategory->slug, $post->post_name),
                'post-slug-change'
            );
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
    }

    public function testChangingCategoryMultipleTimesDoesNotCreateRedirectChains()
    {
        $firstCategory = $this->getCategory();
        $categories = [
            $this->getCategory(),
            $this->getCategory(),
            $this->getCategory(),
            $this->getCategory(),
            $this->getCategory(),
        ];

        $post = $this->getPost([
            'post_category' => [$firstCategory->term_id],
        ]);

        $slugs = array_map(function (\WP_Term $category) use ($post) {
            return sprintf('/%s/%s', $category->slug, $post->post_name);
        }, array_merge([$firstCategory], $categories));

        foreach ($categories as $index => $category) {
            $this->updatePost($post->ID, [
                'post_category' => [$category->term_id],
            ]);
            $newSlug = sprintf('/%s/%s', $category->slug, $post->post_name);

            try {
                $redirects = $this->redirectRepository->findAll();
                $this->assertCount($index + 1, $redirects);
                $redirects->each(function (Redirect $redirect, int $index) use ($post, $newSlug, $slugs) {
                    $this->assertRedirect(
                        $post->ID,
                        $redirect,
                        $slugs[$index],
                        $newSlug,
                        'post-slug-change'
                    );
                });
            } catch (\Exception $exception) {
                $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            }
        }
    }

    public function testChangingSlugAndCategoryCreatesRedirect()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);
        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$category->term_id],
        ]);

        $this->assertSame('/dinosaur/t-rex', $this->getPostSlug($post));

        $newCategory = $this->getCategory([
            'name' => 'Fossils',
            'slug' => 'fossils',
        ]);

        $this->updatePost($post->ID, [
            'post_title' => 'T-Rex is Awesome',
            'post_name' => 't-rex-is-awesome',
            'post_category' => [$newCategory->term_id]
        ]);

        $this->assertSame('/fossils/t-rex-is-awesome', $this->getPostSlug($post));

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $redirects);
            $this->assertRedirect(
                $post->ID,
                $redirects->first(),
                '/dinosaur/t-rex',
                '/fossils/t-rex-is-awesome',
                'post-slug-change'
            );
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
    }
}
