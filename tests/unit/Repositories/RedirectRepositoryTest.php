<?php

namespace Bonnier\WP\Redirect\Tests\unit\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Codeception\Test\Unit;

class RedirectRepositoryTest extends Unit
{
    public function testCanInstantiateRepository()
    {
        $database = $this->makeEmpty(DB::class);
        $repo = new RedirectRepository($database);

        $this->assertInstanceOf(RedirectRepository::class, $repo);
    }
}
