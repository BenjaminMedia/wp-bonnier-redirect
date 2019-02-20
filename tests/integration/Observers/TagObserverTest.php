<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers;

use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Observers\Loggers\TagObserver;
use Bonnier\WP\Redirect\Observers\TagSubject;
use Codeception\Stub\Expected;

class TagObserverTest extends ObserverTestCase
{
    public function testObserverIsNotified()
    {
        $tag = $this->factory()->tag->create_and_get();

        $observer = $this->makeEmpty(TagObserver::class, [
            'update' => Expected::once(),
        ]);
        $subject = new TagSubject();
        $subject->attach($observer);

        wp_update_term($tag->term_id, $tag->taxonomy, [
            'slug' => 'updated-tag',
        ]);
    }

    public function testLogIsCreated()
    {
        $tag = $this->factory()->tag->create_and_get();

        $logs = $this->logRepository->findAll();
        $this->assertCount(1, $logs);
        $this->assertSame('post_tag', $logs->first()->getType());
        $this->assertSame($tag->term_id, $logs->first()->getWpID());

        wp_update_term($tag->term_id, $tag->taxonomy, [
            'slug' => 'updated-tag',
        ]);

        $logs = $this->logRepository->findAll();
        $this->assertCount(2, $logs);
        /** @var Log $log */
        $log = $logs->last();
        $this->assertSame($tag->term_id, $log->getWpID());
        $this->assertSame('post_tag', $log->getType());
        $this->assertSame('/tags/updated-tag', $log->getSlug());
    }
}
