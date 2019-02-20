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
        $this->assertLog($post, $logs->last(), '/log-created');
    }

    public function testSlugChangeCreatesRedirect()
    {
        $post = $this->getPost();

        $initialSlug = str_start($post->post_name, '/');

        $createdLogs = $this->logRepository->findAll();
        $this->assertCount(1, $createdLogs);
        $this->assertLog($post, $createdLogs->first(), $initialSlug);

        $this->updatePost($post->ID, [
            'post_name' => 'new-post-slug'
        ]);

        $logs = $this->logRepository->findAll();
        $this->assertCount(2, $logs);
        $this->assertLog($post, $logs->last(), '/new-post-slug');

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect($post, $redirects->first(), $initialSlug, '/new-post-slug', 'post-slug-change');
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
            return str_start($slug, '/');
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
            $redirectsAfter = $this->redirectRepository->findAll();
            $this->assertCount($index + 1, $redirectsAfter);
            $redirectsAfter->each(function (Redirect $redirect, int $index) use ($post, $postName, $slugs) {
                $this->assertRedirect($post, $redirect, $slugs[$index], str_start($postName, '/'), 'post-slug-change');
            });
        }
    }

    private function assertLog(\WP_Post $post, Log $log, ?string $slug = null)
    {
        if ($slug) {
            $this->assertSame($slug, $log->getSlug());
        } else {
            $this->assertSame(str_start($post->post_name, '/'), $log->getSlug());
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
