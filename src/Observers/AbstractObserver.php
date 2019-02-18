<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Observers\Interfaces\ObserverInterface;
use Bonnier\WP\Redirect\Repositories\LogRepository;

abstract class AbstractObserver implements ObserverInterface
{
    /** @var LogRepository */
    protected $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }
}
