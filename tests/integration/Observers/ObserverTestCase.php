<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Observers\Observers;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;

class ObserverTestCase extends TestCase
{
    /** @var LogRepository */
    protected $logRepository;
    /** @var RedirectRepository */
    protected $redirectRepository;

    public function setUp()
    {
        parent::setUp();
        $this->logRepository = new LogRepository(new DB);
        $this->redirectRepository = new RedirectRepository(new DB);
        // Make sure we are admin so we may call edit_post();
        $userID = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userID);
        Observers::bootstrap($this->logRepository, $this->redirectRepository);
    }
}