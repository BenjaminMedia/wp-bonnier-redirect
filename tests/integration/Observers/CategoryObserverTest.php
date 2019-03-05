<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers;

use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Observers\CategorySubject;
use Bonnier\WP\Redirect\Observers\Loggers\CategoryObserver;
use Codeception\Stub\Expected;

class CategoryObserverTest extends ObserverTestCase
{
    public function testObserverIsNotified()
    {
        $category = $this->factory()->category->create_and_get();

        try {
            /** @var CategoryObserver $observer */
            $observer = $this->makeEmpty(CategoryObserver::class, [
                'update' => Expected::once(),
            ]);
            $subject = new CategorySubject();
            $subject->attach($observer);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating a mocked CategoryObserver (%s)', $exception->getMessage()));
        }

        wp_update_term($category->term_id, $category->taxonomy, [
            'slug' => 'updated-category',
        ]);
    }

    public function testLogIsCreated()
    {
        $category = $this->factory()->category->create_and_get();

        $logs = $this->logRepository->findAll();
        $this->assertCount(1, $logs);
        $this->assertSame('category', $logs->first()->getType());
        $this->assertSame($category->term_id, $logs->first()->getWpID());

        wp_update_term($category->term_id, $category->taxonomy, [
            'slug' => 'updated-category',
        ]);

        $logs = $this->logRepository->findAll();
        $this->assertCount(2, $logs);
        /** @var Log $log */
        $log = $logs->last();
        $this->assertSame($category->term_id, $log->getWpID());
        $this->assertSame('category', $log->getType());
        $this->assertSame('/updated-category', $log->getSlug());
    }
}
