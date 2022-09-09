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

    public function _setUp()
    {
        parent::_setUp();

        try {
            $this->logRepository = new LogRepository(new DB());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instantiating LogRepository (%s)', $exception->getMessage()));
        }
    }

    public function testCanSaveLog()
    {
        $log = new Log();
        $log->setSlug('/path/to/post')
            ->setType('post')
            ->setWpID(1);

        $log = $this->save($log);

        $savedLog = $this->logRepository->findById($log->getID());

        $this->assertInstanceOf(Log::class, $savedLog);
        $this->assertSame($log->toArray(), $savedLog->toArray());
    }

    public function testCanSaveLogsWithSameSlug()
    {
        for ($i = 0; $i < 10; $i++) {
            $log = new Log();
            $log->setSlug('/path/to/post')
                ->setType('post')
                ->setWpID(1);
            $this->save($log);
        }

        $this->assertCount(10, $this->findAll());
    }

    private function save(Log &$log)
    {
        try {
            return $this->logRepository->save($log);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed saving log (%s)', $exception->getMessage()));
            return null;
        }
    }

    private function findAll()
    {
        try {
            return $this->logRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting logs (%s)', $exception->getMessage()));
            return null;
        }
    }
}
