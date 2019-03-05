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

        $expectedTos = [[
            'id' => $category->term_id,
            'type' => 'category-slug-change',
            'slug' => '/dinosaur-fossils',
        ]];
        $this->assertSame('/dinosaur-fossils', $this->getCategorySlug($category));

        foreach ($posts as $post) {
            $expectedTos[] = [
                'id' => $post->ID,
                'type' => 'post-slug-change',
                'slug' => '/dinosaur-fossils/' . $post->post_name
            ];
            $this->assertSame('/dinosaur-fossils/' . $post->post_name, $this->getPostSlug($post));
        }

        try {
            $redirects = $this->redirectRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }
        // One redirect per post (30) and one redirect for the category - 31 in total.
        $this->assertCount(31, $redirects);

        foreach ($expectedFroms as $index => $expectedFrom) {
            $expectedTo = $expectedTos[$index];
            $redirect = $redirects->first(function (Redirect $redirect) use ($expectedTo) {
                return $redirect->getType() === $expectedTo['type'] && $redirect->getWpID() === $expectedTo['id'];
            });
            $this->assertRedirect(
                $expectedTo['id'],
                $redirect,
                $expectedFrom,
                $expectedTo['slug'],
                $expectedTo['type']
            );
        }
    }

    public function testRedirectsCreatedForAllCategoriesAndPostsWhenTopCategorySlugChanges()
    {
        $topCategory = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);
        $subCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $topCategory->term_id,
        ]);

        $this->assertSame('/dinosaur', $this->getCategorySlug($topCategory));
        $this->assertSame('/dinosaur/carnivorous', $this->getCategorySlug($subCategory));

        $expectedFroms = [
            '/dinosaur',
            '/dinosaur/carnivorous'
        ];

        $posts = [];
        foreach (range(1, 30) as $index) {
            $post = $this->getPost([
                'post_title' => 'Post ' . $index,
                'post_name' => 'post-' . $index,
                'post_category' => [$subCategory->term_id],
            ]);
            $posts[$index] = $post;
            $slug = '/dinosaur/carnivorous/post-' . $index;
            $this->assertSame($slug, $this->getPostSlug($post));
            $expectedFroms[] = $slug;
        }

        wp_update_category([
            'cat_ID' => $topCategory->term_id,
            'category_nicename' => 'dinosaur-fossils',
        ]);

        $this->assertSame('/dinosaur-fossils', $this->getCategorySlug($topCategory));
        $this->assertSame('/dinosaur-fossils/carnivorous', $this->getCategorySlug($subCategory));
        $expectedTos = [
            '/dinosaur-fossils',
            '/dinosaur-fossils/carnivorous'
        ];
        foreach ($posts as $index => $post) {
            $this->assertSame('/dinosaur-fossils/carnivorous/post-' . $index, $this->getPostSlug($post));
            $expectedTos[] = '/dinosaur-fossils/carnivorous/post-' . $index;
        }

        try {
            $redirects = $this->redirectRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }
        // One redirect per post (30) and one redirect for the top category and sub category - 32 in total.
        $this->assertCount(32, $redirects);

        $redirectFromAndTos = $redirects->mapWithKeys(function (Redirect $redirect) {
            return [$redirect->getFrom() => $redirect->getTo()];
        })->toArray();

        $this->assertArraysAreEqual($expectedFroms, array_keys($redirectFromAndTos));
        $this->assertArraysAreEqual($expectedTos, array_values($redirectFromAndTos));
    }

    public function testCanHandleRedirectsForMultipleChildCategoriesOnSlugChange()
    {
        $parentCategory = $this->getCategory();
        $initialSlug = $parentCategory->slug;

        $expectedFroms = [
            $this->getCategorySlug($parentCategory),
        ];

        $subCategories = [
            ['category' => $this->getCategory(['parent' => $parentCategory->term_id])],
            ['category' => $this->getCategory(['parent' => $parentCategory->term_id])],
            ['category' => $this->getCategory(['parent' => $parentCategory->term_id])],
            ['category' => $this->getCategory(['parent' => $parentCategory->term_id])],
        ];
        foreach ($subCategories as $index => $subCategory) {
            $category = $subCategory['category'];
            $expectedFroms[] = $this->getCategorySlug($category);
            $subCategories[$index]['posts'] = [
                $this->getPost(['post_category' => [$category->term_id]]),
                $this->getPost(['post_category' => [$category->term_id]]),
                $this->getPost(['post_category' => [$category->term_id]]),
                $this->getPost(['post_category' => [$category->term_id]]),
                $this->getPost(['post_category' => [$category->term_id]]),
            ];
            foreach ($subCategories[$index]['posts'] as $post) {
                $expectedFroms[] = $this->getPostSlug($post);
            }
        }

        $subSubCategory = $this->getCategory([
            'parent' => $subCategories[0]['category']->term_id,
        ]);
        $expectedFroms[] = $this->getCategorySlug($subSubCategory);

        $subSubPosts = [
            $this->getPost(['post_category' => [$subSubCategory->term_id]]),
            $this->getPost(['post_category' => [$subSubCategory->term_id]]),
            $this->getPost(['post_category' => [$subSubCategory->term_id]]),
            $this->getPost(['post_category' => [$subSubCategory->term_id]]),
            $this->getPost(['post_category' => [$subSubCategory->term_id]]),
        ];
        foreach ($subSubPosts as $subSubPost) {
            $expectedFroms[] = $this->getPostSlug($subSubPost);
        }

        wp_update_category([
            'cat_ID' => $parentCategory->term_id,
            'category_nicename' => 'new-category-slug',
        ]);

        $expectedTos = array_map(function ($slug) use ($initialSlug) {
            return str_replace('/' . $initialSlug, '/new-category-slug', $slug);
        }, $expectedFroms);

        // Now we should see 6 redirects for the parent category page and the 4 subcategories and the subsubcategory.
        // Every subcategory has 5 articles, which should also be redirected - 20 additional redirects
        // That should give a total of 31 redirects.
        try {
            $redirects = $this->redirectRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }
        $this->assertCount(31, $redirects);
        $this->assertCount(31, $expectedFroms);

        $redirectFromAndTos = $redirects->mapWithKeys(function (Redirect $redirect) {
            return [$redirect->getFrom() => $redirect->getTo()];
        })->toArray();

        $this->assertArraysAreEqual($expectedFroms, array_keys($redirectFromAndTos));
        $this->assertArraysAreEqual($expectedTos, array_values($redirectFromAndTos));
    }
}
