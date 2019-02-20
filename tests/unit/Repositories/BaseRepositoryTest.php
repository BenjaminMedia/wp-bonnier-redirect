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
            $database = $this->makeEmpty(DB::class);
            new BaseRepository($database);
        } catch (\Exception $exception) {
            $this->assertSame('Missing required property \'$tableName\'', $exception->getMessage());
            return;
        }

        $this->fail('Failed throwing exception, when instantiating BaseRepository without a tableName');
    }
}