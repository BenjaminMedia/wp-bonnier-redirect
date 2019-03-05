<?php

namespace Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepository;

use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepositoryTestCase;

class WildcardRedirectTest extends RedirectRepositoryTestCase
{
    /**
     * @dataProvider wildcardRedirectProvider
     *
     * @param string $path
     * @param string $from
     */
    public function testCanFindWildcardRedirect(string $path, string $from)
    {
        $redirect = $this->createRedirect($from, '/destination');

        $this->assertTrue($redirect->isWildcard(), 'The created redirect wasn\'t a wildcard redirect!');

        try {
            $foundRedirect = $this->repository->findRedirectByPath($path, 'da');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirect (%s)', $exception->getMessage()));
            return;
        }
        $this->assertInstanceOf(
            Redirect::class,
            $foundRedirect,
            sprintf('Could not find redirect where path was \'%s\'', $path)
        );

        $this->assertSameRedirects($redirect, $foundRedirect);
    }

    public function testPrefersExactRedirectMatchInsteadOfWildcardRedirect()
    {
        $wildcard = $this->createRedirect('/from/wildcard/*', '/destination');
        $notWildcard = $this->createRedirect('/from/wildcard/exact', '/destination');

        try {
            $createdRedirects = $this->repository->findAll()->take(-2);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating redirect (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSameRedirects($wildcard, $createdRedirects->first());
        $this->assertSameRedirects($notWildcard, $createdRedirects->last());

        try {
            $foundRedirect = $this->repository->findRedirectByPath('from/wildcard/exact/', 'da');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirect (%s)', $exception->getMessage()));
            return;
        }
        $this->assertInstanceOf(Redirect::class, $foundRedirect);
        $this->assertSameRedirects($notWildcard, $foundRedirect);
    }

    public function testPrefersWildcardIfNoExactMatchExists()
    {
        $wildcard = $this->createRedirect('/from/wildcard/*', '/destination');
        $notWildcard = $this->createRedirect('/from/wildcard/exact', '/destination');

        try {
            $createdRedirects = $this->repository->findAll()->take(-2);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating redirect (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSameRedirects($wildcard, $createdRedirects->first());
        $this->assertSameRedirects($notWildcard, $createdRedirects->last());

        try {
            $foundRedirect = $this->repository->findRedirectByPath('from/wildcard/exact/path/', 'da');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirect (%s)', $exception->getMessage()));
            return;
        }
        $this->assertInstanceOf(Redirect::class, $foundRedirect);
        $this->assertSameRedirects($wildcard, $foundRedirect);
    }

    public function wildcardRedirectProvider()
    {
        return [
            'Polopoly redirects' => ['/polopoly.jsp?id=1234&gcid=abc123', '/polopoly.jsp*'],
            'Archive redirects' => ['/archive/my-article', '/archive*'],
            'Archive redirects with slash' => ['/archive/my-article', '/archive/*'],
        ];
    }
}
