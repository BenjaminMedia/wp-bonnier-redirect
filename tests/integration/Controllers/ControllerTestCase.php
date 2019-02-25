<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ControllerTestCase extends TestCase
{
    /** @var RedirectRepository */
    protected $redirectRepository;

    public function setUp()
    {
        parent::setUp();
        try {
            $this->redirectRepository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            $this->fail('Failed setting up RedirectRepository for tests');
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
        return Request::create('/wp-admin/admin.php?page=add-redirect', 'POST', $args);
    }

    protected function createGetRequest(array $args = []): Request
    {
        return Request::create('/wp-admin/admin.php', 'GET', $args);
    }

    protected function createRedirect(
        string $fromSlug,
        string $toSlug,
        string $type = 'manual',
        string $locale = 'da',
        int $code = 301
    ): ?Redirect {
        $redirect = new Redirect();
        $redirect->setFrom($fromSlug)
            ->setTo($toSlug)
            ->setType($type)
            ->setLocale($locale)
            ->setCode($code);

        try {
            return $this->redirectRepository->save($redirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed saving redirect (%s)', $exception->getMessage()));
        }

        return null;
    }
}
