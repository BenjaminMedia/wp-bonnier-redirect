<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Observers\Observers;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;

class ObserverTestCase extends TestCase
{
    /** @var LogRepository */
    protected $logRepository;

    public function setUp()
    {
        parent::setUp();
        global $wpdb;
        $database = new DB($wpdb);
        $this->logRepository = new LogRepository($database);
        // Make sure we are admin so we may call edit_post();
        $userID = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userID);
        Observers::bootstrap($this->logRepository);
    }
}
