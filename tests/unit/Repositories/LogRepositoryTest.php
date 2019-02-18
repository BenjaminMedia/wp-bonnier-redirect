<?php

namespace Bonnier\WP\Redirect\Tests\unit\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;

class LogRepositoryTest extends Unit
{
    public function testCanInstantiateRepository()
    {
        $database = $this->makeEmpty(DB::class);
        $repo = new LogRepository($database);

        $this->assertInstanceOf(LogRepository::class, $repo);
    }

    public function testCanInsertLog()
    {
        $data = [
            'slug' => '/test/slug',
            'hash' => hash('md5', '/test/slug'),
            'type' => 'post',
            'wp_id' => 1,
            'created_at' => (new \DateTime('-1 hour'))->format('Y-m-d H:i:s')
        ];
        $log = new Log();
        $log->fromArray($data);
        $this->assertSame(0, $log->getID());
        $database = $this->makeEmpty(DB::class, [
            'insert' => Expected::once(10),
            'update' => Expected::never(),
        ]);
        $repo = new LogRepository($database);
        $this->assertSame(0, $log->getID());
        $savedLog = $repo->save($log);

        $this->assertSame(10, $savedLog->getID());
    }
}
