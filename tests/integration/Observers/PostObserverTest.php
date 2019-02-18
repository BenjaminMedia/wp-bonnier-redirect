<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers;

use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Observers\PostObserver;
use Bonnier\WP\Redirect\Observers\PostSubject;
use Codeception\Stub\Expected;

class PostObserverTest extends ObserverTestCase
{
    public function testObserverIsNotified()
    {
        /** @var \WP_Post $post */
        $post = $this->factory()->post->create_and_get();

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
        $post = $this->factory()->post->create_and_get();

        $logs = $this->logRepository->findAll();
        $this->assertCount(1, $logs);
        $this->assertSame($post->ID, $logs->first()->getWpID());

        $this->updatePost($post->ID, [
            'post_name' => 'log-created',
        ]);

        $logs = $this->logRepository->findAll();
        $this->assertCount(2, $logs); // Create and update logs
        /** @var Log $log */
        $log = $logs->last();
        $this->assertSame($post->ID, $log->getWpID());
        $this->assertSame('post', $log->getType());
        $this->assertSame('/log-created', $log->getSlug());
    }
}
