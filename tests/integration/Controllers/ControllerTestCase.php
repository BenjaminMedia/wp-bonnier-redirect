<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ControllerTestCase extends TestCase
{
    /** @var RedirectRepository */
    protected $redirectRepository;
    /** @var LogRepository */
    protected $logRepository;

    public function setUp()
    {
        parent::setUp();
        // Set the locale of WordPress to be danish
        global $locale;
        $locale = 'da_DK';
        try {
            $this->redirectRepository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            $this->fail('Failed setting up RedirectRepository for tests');
        }

        try {
            $this->logRepository = new LogRepository(new DB());
        } catch (\Exception $exception) {
            $this->fail('Failed setting up LogRepository for tests');
        }
    }

    protected function assertManualRedirect(
        Redirect $redirect,
        string $fromUrl,
        string $toUrl,
        string $locale = 'da',
        int $code = 301
    ) {
        $this->assertSame($fromUrl, $redirect->getFrom());
        $this->assertSame($toUrl, $redirect->getTo());
        $this->assertSame('manual', $redirect->getType());
        $this->assertSame($code, $redirect->getCode());
        $this->assertSame($locale, $redirect->getLocale());
        $this->assertSame(0, $redirect->getWpID());
    }

    protected function createPostRequest(array $args = []): Request
    {
        return Request::create('/wp-admin/admin.php?page=' . CrudController::PAGE, 'POST', $args);
    }

    protected function createGetRequest(array $args = []): Request
    {
        return Request::create('/wp-admin/admin.php', 'GET', $args);
    }

    protected function createRedirect(
        string $fromSlug,
        string $toSlug,
        string $type = 'manual',
        int $wpID = 0,
        string $locale = 'da',
        int $code = 301
    ): ?Redirect {
        $redirect = new Redirect();
        $redirect->setFrom($fromSlug)
            ->setTo($toSlug)
            ->setType($type)
            ->setWpID($wpID)
            ->setLocale($locale)
            ->setCode($code);

        return $this->save($redirect);
    }

    protected function assertRedirectCreated(Redirect $redirect, int $count = 1)
    {
        $redirects = $this->findAllRedirects();
        $this->assertCount($count, $redirects);
        $this->assertSameRedirects($redirect, $redirects->last());
    }

    protected function assertNoticeWasSaveRedirectMessage(array $notices)
    {
        $this->assertNoticeIs($notices, 'success', 'The redirect was saved!');
    }

    protected function assertNoticeWasInvalidInputMessage(array $notices)
    {
        $this->assertNoticeIs($notices, 'error', 'Invalid data was submitted - fix fields marked with red.');
    }

    protected function assertNoticeIs(array $notices, string $type, string $message)
    {
        $expected = [
            ['type' => $type, 'message' => $message],
        ];
        $this->assertNotices($expected, $notices);
    }

    protected function assertNotices(array $expected, array $actual)
    {
        $this->assertCount(count($expected), $actual);
        foreach ($expected as $index => $notice) {
            $this->assertArrayHasKey($notice['type'], $actual[$index]);
            $this->assertContains($notice['message'], $actual[$index][$notice['type']]);
        }
    }

    protected function save(Redirect &$redirect)
    {
        try {
            return $this->redirectRepository->save($redirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed saving redirect (%s)', $exception->getMessage()));
            return null;
        }
    }

    protected function findAllRedirects()
    {
        try {
            return $this->redirectRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return null;
        }
    }

    protected function getCrudController(Request $request)
    {
        $crudController = new CrudController($this->logRepository, $this->redirectRepository, $request);
        $crudController->handlePost();
        return $crudController;
    }
}
