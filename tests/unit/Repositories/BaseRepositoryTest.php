<?php

namespace Bonnier\WP\Redirect\Tests\unit\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Repositories\BaseRepository;
use Codeception\Test\Unit;

class BaseRepositoryTest extends Unit
{
    public function testThrowsErrorWhenInstantiatedWithoutTable()
    {
        try {
            /** @var DB $database */
            $database = $this->makeEmpty(DB::class);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed mocking DB (%s)', $exception->getMessage()));
        }
        try {
            new BaseRepository($database);
        } catch (\Exception $exception) {
            $this->assertSame('Missing required property \'$tableName\'', $exception->getMessage());
            return;
        }

        $this->fail('Failed throwing exception, when instantiating BaseRepository without a tableName');
    }
}
