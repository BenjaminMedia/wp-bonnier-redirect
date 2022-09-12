<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Log;
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

    public function _setUp()
    {
        parent::_setUp();
        try {
            $this->logRepository = new LogRepository(new DB);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instantiating a LogRepository (%s)', $exception->getMessage()));
        }
        try {
            $this->redirectRepository = new RedirectRepository(new DB);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instantiating a RedirectRepository (%s)', $exception->getMessage()));
        }
        // Make sure we are admin so we may call edit_post();
        $userID = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userID);
        Observers::bootstrap($this->logRepository, $this->redirectRepository);
    }

    protected function assertLog(\WP_Post $post, Log $log, ?string $slug = null)
    {
        if ($slug) {
            $this->assertSame($slug, $log->getSlug());
        } else {
            $url = parse_url(get_permalink($post), PHP_URL_PATH);
            $this->assertSame(rtrim($url, '/'), $log->getSlug());
        }
        $this->assertSame($post->ID, $log->getWpID());
        $this->assertSame($post->post_type, $log->getType());
    }

    protected function findAllRedirects()
    {
        try {
            return $this->redirectRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting redirects (%s)', $exception->getMessage()));
        }
    }

    protected function findAllLogs()
    {
        try {
            return $this->logRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding logs (%s)', $exception->getMessage()));
        }
    }
}
