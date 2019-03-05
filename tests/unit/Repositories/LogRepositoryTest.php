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
        try {
            /** @var DB $database */
            $database = $this->makeEmpty(DB::class);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed mocking DB (%s)', $exception->getMessage()));
        }
        try {
            $repo = new LogRepository($database);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instatiating repository (%s)', $exception->getMessage()));
            return;
        }
        $this->assertInstanceOf(LogRepository::class, $repo);
    }

    public function testCanInsertLog()
    {
        try {
            $createdAt = (new \DateTime('-1 hour'))->format('Y-m-d H:i:s');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating a timestamp (%s)', $exception->getMessage()));
            return;
        }

        $data = [
            'slug' => '/test/slug',
            'hash' => hash('md5', '/test/slug'),
            'type' => 'post',
            'wp_id' => 1,
            'created_at' => $createdAt
        ];
        $log = new Log();
        $log->fromArray($data);
        $this->assertSame(0, $log->getID());
        try {
            /** @var DB $database */
            $database = $this->makeEmpty(DB::class, [
                'insert' => Expected::once(10),
                'update' => Expected::never(),
            ]);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed mocking DB (%s)', $exception->getMessage()));
            return;
        }
        try {
            $repo = new LogRepository($database);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed inserting log (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame(0, $log->getID());
        $savedLog = $repo->save($log);

        $this->assertSame(10, $savedLog->getID());
    }
}
