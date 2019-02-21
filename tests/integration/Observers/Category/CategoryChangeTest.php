<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Category;

use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class CategoryChangeTest extends ObserverTestCase
{
    public function testCanChangeParentCategoryAndHaveRedirectsCreated()
    {
        $initialCategory = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur',
        ]);
        $childCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $initialCategory->term_id,
        ]);
        $newCategory = $this->getCategory([
            'name' => 'Animals',
            'slug' => 'animals',
        ]);

        $this->assertSame('/dinosaur', $this->getCategorySlug($initialCategory));
        $this->assertSame('/dinosaur/carnivorous', $this->getCategorySlug($childCategory));
        $this->assertSame('/animals', $this->getCategorySlug($newCategory));

        $posts = [
            $this->getPost(['post_category' => [$childCategory->term_id]]),
            $this->getPost(['post_category' => [$childCategory->term_id]]),
            $this->getPost(['post_category' => [$childCategory->term_id]]),
            $this->getPost(['post_category' => [$childCategory->term_id]]),
        ];

        foreach ($posts as $post) {
            $this->assertSame('/dinosaur/carnivorous/' . $post->post_name, $this->getPostSlug($post));
        }

        wp_update_category(['cat_ID' => $childCategory->term_id, 'category_parent' => $newCategory->term_id]);

        $this->assertSame('/animals/carnivorous', $this->getCategorySlug($childCategory));
        foreach ($posts as $post) {
            $this->assertSame('/animals/carnivorous/' . $post->post_name, $this->getPostSlug($post));
        }

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(5, $redirects);

        $categoryRedirect = $redirects->shift();
        $this->assertSame('/dinosaur/carnivorous', $categoryRedirect->getFrom());
        $this->assertSame('/animals/carnivorous', $categoryRedirect->getTo());

        foreach ($posts as $index => $post) {
            $redirect = $redirects->get($index);
            $this->assertSame('/dinosaur/carnivorous/' . $post->post_name, $redirect->getFrom());
            $this->assertSame('/animals/carnivorous/' . $post->post_name, $redirect->getTo());
        }
    }

    /**
     * Long test to ensure that child categories and child posts have redirects created
     * and are moved to their proper new slug
     */
    public function testCanChangeParentCategoryAndHaveRedirectsCreatedOnMultipleLevels()
    {
        $initialCategory = $this->getCategory();
        $newCategory = $this->getCategory();
        $cat1 = $this->getCategory(['parent' => $initialCategory->term_id]);
        $cat2 = $this->getCategory(['parent' => $cat1->term_id]);
        $cat3 = $this->getCategory(['parent' => $cat2->term_id]);

        $expectedFroms = [
            sprintf('/%s/%s', $initialCategory->slug, $cat1->slug),
            sprintf('/%s/%s/%s', $initialCategory->slug, $cat1->slug, $cat2->slug),
            sprintf('/%s/%s/%s/%s', $initialCategory->slug, $cat1->slug, $cat2->slug, $cat3->slug),
        ];
        $expectedTos = [
            sprintf('/%s/%s', $newCategory->slug, $cat1->slug),
            sprintf('/%s/%s/%s', $newCategory->slug, $cat1->slug, $cat2->slug),
            sprintf('/%s/%s/%s/%s', $newCategory->slug, $cat1->slug, $cat2->slug, $cat3->slug),
        ];

        $this->assertSame('/' . $initialCategory->slug, $this->getCategorySlug($initialCategory));
        $this->assertSame('/' . $newCategory->slug, $this->getCategorySlug($newCategory));
        $this->assertSame(
            sprintf('/%s/%s', $initialCategory->slug, $cat1->slug),
            $this->getCategorySlug($cat1)
        );
        $this->assertSame(
            sprintf('/%s/%s/%s', $initialCategory->slug, $cat1->slug, $cat2->slug),
            $this->getCategorySlug($cat2)
        );
        $this->assertSame(
            sprintf('/%s/%s/%s/%s', $initialCategory->slug, $cat1->slug, $cat2->slug, $cat3->slug),
            $this->getCategorySlug($cat3)
        );

        $cat1Posts = [
            $this->getPost(['post_category' => [$cat1->term_id]]),
            $this->getPost(['post_category' => [$cat1->term_id]]),
            $this->getPost(['post_category' => [$cat1->term_id]]),
        ];
        $cat2Posts = [
            $this->getPost(['post_category' => [$cat2->term_id]]),
            $this->getPost(['post_category' => [$cat2->term_id]]),
            $this->getPost(['post_category' => [$cat2->term_id]]),
        ];
        $cat3Posts = [
            $this->getPost(['post_category' => [$cat3->term_id]]),
            $this->getPost(['post_category' => [$cat3->term_id]]),
            $this->getPost(['post_category' => [$cat3->term_id]]),
        ];
        foreach ($cat1Posts as $post) {
            $this->assertSame(
                sprintf('/%s/%s/%s', $initialCategory->slug, $cat1->slug, $post->post_name),
                $this->getPostSlug($post)
            );
            $expectedFroms[] = sprintf('/%s/%s/%s', $initialCategory->slug, $cat1->slug, $post->post_name);
            $expectedTos[] = sprintf('/%s/%s/%s', $newCategory->slug, $cat1->slug, $post->post_name);
        }
        foreach ($cat2Posts as $post) {
            $this->assertSame(
                sprintf('/%s/%s/%s/%s', $initialCategory->slug, $cat1->slug, $cat2->slug, $post->post_name),
                $this->getPostSlug($post)
            );
            $expectedFroms[] = sprintf(
                '/%s/%s/%s/%s',
                $initialCategory->slug,
                $cat1->slug,
                $cat2->slug,
                $post->post_name
            );
            $expectedTos[] = sprintf(
                '/%s/%s/%s/%s',
                $newCategory->slug,
                $cat1->slug,
                $cat2->slug,
                $post->post_name
            );
        }
        foreach ($cat3Posts as $post) {
            $this->assertSame(
                sprintf(
                    '/%s/%s/%s/%s/%s',
                    $initialCategory->slug,
                    $cat1->slug,
                    $cat2->slug,
                    $cat3->slug,
                    $post->post_name
                ),
                $this->getPostSlug($post)
            );
            $expectedFroms[] = sprintf(
                '/%s/%s/%s/%s/%s',
                $initialCategory->slug,
                $cat1->slug,
                $cat2->slug,
                $cat3->slug,
                $post->post_name
            );
            $expectedTos[] = sprintf(
                '/%s/%s/%s/%s/%s',
                $newCategory->slug,
                $cat1->slug,
                $cat2->slug,
                $cat3->slug,
                $post->post_name
            );
        }

        wp_update_category([
            'cat_ID' => $cat1->term_id,
            'category_parent' => $newCategory->term_id,
        ]);

        $this->assertSame(
            sprintf('/%s/%s', $newCategory->slug, $cat1->slug),
            $this->getCategorySlug($cat1)
        );
        $this->assertSame(
            sprintf('/%s/%s/%s', $newCategory->slug, $cat1->slug, $cat2->slug),
            $this->getCategorySlug($cat2)
        );
        $this->assertSame(
            sprintf('/%s/%s/%s/%s', $newCategory->slug, $cat1->slug, $cat2->slug, $cat3->slug),
            $this->getCategorySlug($cat3)
        );
        foreach ($cat1Posts as $post) {
            $this->assertSame(
                sprintf('/%s/%s/%s', $newCategory->slug, $cat1->slug, $post->post_name),
                $this->getPostSlug($post)
            );
        }
        foreach ($cat2Posts as $post) {
            $this->assertSame(
                sprintf('/%s/%s/%s/%s', $newCategory->slug, $cat1->slug, $cat2->slug, $post->post_name),
                $this->getPostSlug($post)
            );
        }
        foreach ($cat3Posts as $post) {
            $this->assertSame(
                sprintf(
                    '/%s/%s/%s/%s/%s',
                    $newCategory->slug,
                    $cat1->slug,
                    $cat2->slug,
                    $cat3->slug,
                    $post->post_name
                ),
                $this->getPostSlug($post)
            );
        }

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(12, $redirects);

        $redirectFromAndTos = $redirects->mapWithKeys(function (Redirect $redirect) {
            return [$redirect->getFrom() => $redirect->getTo()];
        })->toArray();

        $this->assertArraysAreEqual($expectedFroms, array_keys($redirectFromAndTos));
        $this->assertArraysAreEqual($expectedTos, array_values($redirectFromAndTos));
    }
}
