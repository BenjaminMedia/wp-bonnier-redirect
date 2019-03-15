<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Post;

use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class LogTest extends ObserverTestCase
{
    public function testLogIsCreated()
    {
        $post = $this->getPost();

        $logs = $this->findAllLogs();
        $this->assertCount(1, $logs);
        $this->assertLog($post, $logs->first());

        $this->updatePost($post->ID, [
            'post_name' => 'log-created',
        ]);

        $logs = $this->findAllLogs();
        $this->assertCount(2, $logs); // Create and update logs
        $this->assertLog($post, $logs->last(), '/uncategorized/log-created');
    }
}
