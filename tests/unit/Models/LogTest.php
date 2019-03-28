<?php

namespace Bonnier\WP\Redirect\Tests\unit\Models;

use Bonnier\WP\Redirect\Models\Log;
use Codeception\Test\Unit;

class LogTest extends Unit
{
    public function testPrefersHashFromDatabase()
    {
        $log = new Log();
        $log->fromArray([
            'id' => 1,
            'slug' => 'test-slug',
            'hash' => 'database-hash',
            'type' => 'post',
            'wp_id' => 100,
        ]);

        $this->assertSame('database-hash', $log->getHash());
    }

    public function testGeneratesHashCorrectly()
    {
        $log = new Log();
        $log->fromArray([
            'slug' => '/path/to/the/post',
            'type' => 'post',
            'wp_id' => 100,
        ]);

        $expectedHash = hash('md5', '/path/to/the/post');

        $this->assertSame($expectedHash, $log->getHash());
    }
}
