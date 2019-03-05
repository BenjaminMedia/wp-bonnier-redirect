<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Post;

use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class SlugChangesTest extends ObserverTestCase
{
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

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $redirects);
            $this->assertRedirect(
                $post->ID,
                $redirects->first(),
                $initialSlug,
                '/uncategorized/new-post-slug',
                'post-slug-change'
            );
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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
            try {
                $redirectsBefore = $this->redirectRepository->findAll();
                if ($index === 0) {
                    $this->assertNull($redirectsBefore);
                } else {
                    $this->assertCount($index, $redirectsBefore);
                }
            } catch (\Exception $exception) {
                $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            }
            
            $this->updatePost($post->ID, [
                'post_name' => $postName,
            ]);
            $newSlug = '/uncategorized/' . $postName;

            try {
                $redirectsAfter = $this->redirectRepository->findAll();
                $this->assertCount($index + 1, $redirectsAfter);
                $redirectsAfter->each(function (Redirect $redirect, int $index) use ($post, $newSlug, $slugs) {
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
}
