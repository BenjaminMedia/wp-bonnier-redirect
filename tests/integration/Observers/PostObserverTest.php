<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers;

use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Observers\Loggers\PostObserver;
use Bonnier\WP\Redirect\Observers\PostSubject;
use Codeception\Stub\Expected;

class PostObserverTest extends ObserverTestCase
{
    public function testObserverIsNotified()
    {
        $post = $this->getPost();

        $observer = $this->makeEmpty(PostObserver::class, [
            'update' => Expected::once(),
        ]);
        $subject = new PostSubject();
        $subject->attach($observer);

        $this->updatePost($post->ID, [
            'post_name' => 'test-post-name',
        ]);
    }

    public function testLogIsCreated()
    {
        $post = $this->getPost();

        $logs = $this->logRepository->findAll();
        $this->assertCount(1, $logs);
        $this->assertLog($post, $logs->first());

        $this->updatePost($post->ID, [
            'post_name' => 'log-created',
        ]);

        $logs = $this->logRepository->findAll();
        $this->assertCount(2, $logs); // Create and update logs
        $this->assertLog($post, $logs->last(), '/uncategorized/log-created');
    }

    public function testSlugChangeCreatesRedirect()
    {
        $post = $this->getPost();

        $initialSlug = '/uncategorized/' . $post->post_name;

        $createdLogs = $this->logRepository->findAll();
        $this->assertCount(1, $createdLogs);
        $this->assertLog($post, $createdLogs->first(), $initialSlug);

        $this->updatePost($post->ID, [
            'post_name' => 'new-post-slug'
        ]);

        $logs = $this->logRepository->findAll();
        $this->assertCount(2, $logs);
        $this->assertLog($post, $logs->last(), '/uncategorized/new-post-slug');

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->first(),
            $initialSlug,
            '/uncategorized/new-post-slug',
            'post-slug-change'
        );
    }

    public function testSlugChangesDoesntCreateRedirectChains()
    {
        $post = $this->getPost();
        $initialPostName = $post->post_name;
        $postNames = [
            'post-slug-one',
            'post-slug-two',
            'post-slug-three',
            'post-slug-four',
            'post-slug-five',
            'post-slug-six',
            'post-slug-seven',
            'post-slug-eight',
            'post-slug-nine',
            'post-slug-ten',
        ];
        $slugs = array_map(function ($slug) {
            return '/uncategorized/' . $slug;
        }, array_merge([$initialPostName], $postNames));

        foreach ($postNames as $index => $postName) {
            $redirectsBefore = $this->redirectRepository->findAll();
            if ($index === 0) {
                $this->assertNull($redirectsBefore);
            } else {
                $this->assertCount($index, $redirectsBefore);
            }
            $this->updatePost($post->ID, [
                'post_name' => $postName,
            ]);
            $newSlug = '/uncategorized/' . $postName;
            $redirectsAfter = $this->redirectRepository->findAll();
            $this->assertCount($index + 1, $redirectsAfter);
            $redirectsAfter->each(function (Redirect $redirect, int $index) use ($post, $newSlug, $slugs) {
                $this->assertRedirect(
                    $post,
                    $redirect,
                    $slugs[$index],
                    $newSlug,
                    'post-slug-change'
                );
            });
        }
    }

    public function testChangingCategoryCreatesRedirects()
    {
        $category = $this->getCategory();
        $post = $this->getPost([
            'post_category' => [$category->term_id],
        ]);

        $this->assertNull($this->redirectRepository->findAll());

        $newCategory = $this->getCategory();
        $this->updatePost($post->ID, [
            'post_category' => [$newCategory->term_id],
        ]);
        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->first(),
            sprintf('/%s/%s', $category->slug, $post->post_name),
            sprintf('/%s/%s', $newCategory->slug, $post->post_name),
            'post-slug-change'
        );
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
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount($index + 1, $redirects);
            $redirects->each(function (Redirect $redirect, int $index) use ($post, $newSlug, $slugs) {
                $this->assertRedirect(
                    $post,
                    $redirect,
                    $slugs[$index],
                    $newSlug,
                    'post-slug-change'
                );
            });
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

        $this->assertSame('/fossils/t-rex-is-awesome', rtrim(parse_url(get_permalink($post->ID), PHP_URL_PATH), '/'));

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->first(),
            '/dinosaur/t-rex',
            '/fossils/t-rex-is-awesome',
            'post-slug-change'
        );
    }

    public function testTrashedPostCreatesRedirectToParentCategory()
    {
        $parentCategory = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur',
        ]);
        $postCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $parentCategory->term_id,
        ]);

        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$postCategory->term_id],
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'trash',
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-trash'
        );
    }

    public function testUnpublishPostCreatesRedirectToParentCategory()
    {
        $parentCategory = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur',
        ]);
        $postCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $parentCategory->term_id,
        ]);

        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$postCategory->term_id],
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'draft',
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-draft'
        );
    }

    public function testCanUnpublishAndRepublishPostWithNewSlug()
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
        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'draft'
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-draft'
        );

        $this->updatePost($post->ID, [
            'post_name' => 't-rex-is-awesome',
            'post_status' => 'publish'
        ]);

        $this->assertSame('/dinosaur/carnivorous/t-rex-is-awesome', $this->getPostSlug($post));

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->first(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous/t-rex-is-awesome',
            'post-slug-change'
        );
    }

    public function testCanUnpublishAndRepublishPostWithSameSlug()
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
        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'draft'
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-draft'
        );

        $this->updatePost($post->ID, [
            'post_status' => 'publish'
        ]);

        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->assertNull($this->redirectRepository->findAll());
    }

    public function testCanDeletePostAndCreateNewPostWithSameSlug()
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
        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'trash'
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-trash'
        );

        $newPost = $this->getPost([
            'post_title' => 'New T-rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);

        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($newPost));

        $this->assertNull($this->redirectRepository->findAll());
    }

    private function assertLog(\WP_Post $post, Log $log, ?string $slug = null)
    {
        if ($slug) {
            $this->assertSame($slug, $log->getSlug());
        } else {
            $url = parse_url(get_permalink($post), PHP_URL_PATH);
            $this->assertSame(rtrim($url, '/'), $log->getSlug());
        }
        $this->assertSame($post->ID, $log->getWpID());
        $this->assertSame($post->post_type, $log->getType());
    }

    private function assertRedirect(
        \WP_Post $post,
        Redirect $redirect,
        string $fromSlug,
        string $toSlug,
        string $type,
        int $status = 301
    ) {
        $this->assertSame($fromSlug, $redirect->getFrom());
        $this->assertSame($toSlug, $redirect->getTo());
        $this->assertSame($status, $redirect->getCode());
        $this->assertSame($post->ID, $redirect->getWpID());
        $this->assertSame($type, $redirect->getType());
    }
}
