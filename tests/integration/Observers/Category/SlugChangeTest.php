<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Category;

use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class SlugChangeTest extends ObserverTestCase
{
    public function testRedirectsCreatedForCategoryAndPostsWhenCategorySlugChanges()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur',
        ]);

        $this->assertSame('/dinosaur', $this->getCategorySlug($category));

        $posts = [];
        foreach (range(1, 30) as $index) {
            $posts[$index] = $this->getPost([
                'post_title' => 'Post ' . $index,
                'post_name' => 'post-' . $index,
                'post_category' => [$category->term_id],
            ]);
        }

        $expectedFroms = array_merge([$this->getCategorySlug($category)], array_map(function (\WP_Post $post) {
            return $this->getPostSlug($post);
        }, $posts));

        wp_update_category(['cat_ID' => $category->term_id, 'category_nicename' => 'dinosaur-fossils']);

        $expectedTos = [
            '/dinosaur-fossils',
        ];
        $this->assertSame('/dinosaur-fossils', $this->getCategorySlug($category));

        foreach ($posts as $index => $post) {
            $expectedTos[] = '/dinosaur-fossils/post-' . $index;
            $this->assertSame('/dinosaur-fossils/post-' . $index, $this->getPostSlug($post));
        }

        $redirects = $this->redirectRepository->findAll();
        // One redirect per post (30) and one redirect for the category - 31 in total.
        $this->assertCount(31, $redirects);

        $redirectFromAndTos = $redirects->mapWithKeys(function (Redirect $redirect) {
            return [$redirect->getFrom() => $redirect->getTo()];
        })->toArray();

        $this->assertArraysAreEqual($expectedFroms, array_keys($redirectFromAndTos));
        $this->assertArraysAreEqual($expectedTos, array_values($redirectFromAndTos));
    }
}
