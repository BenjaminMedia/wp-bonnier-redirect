<?php

namespace Bonnier\WP\Redirect\Tests\unit\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Codeception\Test\Unit;

class RedirectRepositoryTest extends Unit
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
            $repo = new RedirectRepository($database);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instatiating repository (%s)', $exception->getMessage()));
            return;
        }
        $this->assertInstanceOf(RedirectRepository::class, $repo);
    }
}
