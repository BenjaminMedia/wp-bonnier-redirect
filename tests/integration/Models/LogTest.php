<?php

namespace Bonnier\WP\Redirect\Tests\Integration\Models;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;

class LogTest extends TestCase
{
    /** @var LogRepository */
    private $logRepository;

    public function setUp()
    {
        parent::setUp();

        global $wpdb;
        $database = new DB($wpdb);
        $this->logRepository = new LogRepository($database);
    }

    public function testCanSaveLog()
    {
        $log = new Log();
        $log->setSlug('/path/to/post')
            ->setType('post')
            ->setWpID(1);
        $log = $this->logRepository->save($log);

        $savedLog = $this->logRepository->findById($log->getID());

        $this->assertInstanceOf(Log::class, $savedLog);
        $this->assertSame($log->toArray(), $savedLog->toArray());
    }

    public function testCanSaveLogsWithSameSlug()
    {
        foreach (range(1, 10) as $index) {
            $log = new Log();
            $log->setSlug('/path/to/post')
                ->setType('post')
                ->setWpID(1);
            $this->logRepository->save($log);
        }

        $this->assertCount(10, $this->logRepository->findAll());
    }
}
