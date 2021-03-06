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

        $foundRedirect = $this->findRedirectByPath($path);

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

        $createdRedirects = $this->findAllRedirects()->take(-2);

        $this->assertSameRedirects($wildcard, $createdRedirects->first());
        $this->assertSameRedirects($notWildcard, $createdRedirects->last());

        $foundRedirect = $this->findRedirectByPath('from/wildcard/exact/');
        $this->assertInstanceOf(Redirect::class, $foundRedirect);
        $this->assertSameRedirects($notWildcard, $foundRedirect);
    }

    public function testPrefersWildcardIfNoExactMatchExists()
    {
        $wildcard = $this->createRedirect('/from/wildcard/*', '/destination');
        $notWildcard = $this->createRedirect('/from/wildcard/exact', '/destination');

        $createdRedirects = $this->findAllRedirects()->take(-2);
        $this->assertSameRedirects($wildcard, $createdRedirects->first());
        $this->assertSameRedirects($notWildcard, $createdRedirects->last());

        $foundRedirect = $this->findRedirectByPath('from/wildcard/exact/path/');
        $this->assertInstanceOf(Redirect::class, $foundRedirect);
        $this->assertSameRedirects($wildcard, $foundRedirect);
    }

    public function testWildcardRedirectsKeepsQueryParams()
    {
        $redirect = $this->createRedirect('/from/*', '/destination', true);

        $foundRedirect = $this->findRedirectByPath('/from/this/slug?c=d&a=b');
        $this->assertInstanceOf(Redirect::class, $foundRedirect);
        $this->assertSame($redirect->getID(), $foundRedirect->getID());
        $this->assertSame('/from/*', $foundRedirect->getFrom());
        $this->assertSame('/destination?a=b&c=d', $foundRedirect->getTo());
    }

    public function testWildcardRedirectsIgnoresQueryParams()
    {
        $redirect = $this->createRedirect('/from/*', '/destination');

        $foundRedirect = $this->findRedirectByPath('/from/this/slug?c=d&a=b');
        $this->assertInstanceOf(Redirect::class, $foundRedirect);
        $this->assertSameRedirects($redirect, $foundRedirect);
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
