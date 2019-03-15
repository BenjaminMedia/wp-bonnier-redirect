<?php

namespace Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepository;

use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepositoryTestCase;

class QueryParamsRedirectTest extends RedirectRepositoryTestCase
{
    /**
     * @dataProvider ignoreQueryParamProvider
     *
     * @param string $path
     * @param string $from
     * @param string $destination
     */
    public function testIgnoringQueryParamsIgnoresAllQueryParams(string $path, string $from, string $destination)
    {
        $redirect = $this->createRedirect($from, $destination);

        $foundRedirect = $this->findRedirectByPath($path);
        $this->assertInstanceOf(
            Redirect::class,
            $foundRedirect,
            sprintf('Could not find redirect where path was \'%s\'', $path)
        );
        $this->assertSameRedirects($redirect, $foundRedirect);
        $this->assertSame($destination, $foundRedirect->getTo());
    }

    /**
     * @dataProvider keepQueryParamProvider
     *
     * @param string $path
     * @param string $from
     * @param string $destination
     * @param string $expectedTo
     */
    public function testKeepingQueryParamsActuallyKeepsAllQueryParams(
        string $path,
        string $from,
        string $destination,
        string $expectedTo
    ) {
        $redirect = $this->createRedirect($from, $destination, true);

        $foundRedirect = $this->findRedirectByPath($path);
        $this->assertInstanceOf(
            Redirect::class,
            $foundRedirect,
            sprintf('Could not find redirect where path was \'%s\'', $path)
        );
        $this->assertSame($redirect->getID(), $foundRedirect->getID());
        $this->assertSame($from, $foundRedirect->getFrom());
        $this->assertSame($expectedTo, $foundRedirect->getTo());
    }

    public function ignoreQueryParamProvider()
    {
        return [
            'With pagination' => ['/category?page=1', '/category', '/destination/page'],
            'With multiple params' => ['/page/?a=b&c=d&e=f', '/page', '/destination/page'],
            'Exact match' => ['/page/slug?a=b&c=d', '/page/slug?a=b&c=d', '/destination/page'],
        ];
    }

    public function keepQueryParamProvider()
    {
        return [
            'Pagination' => ['/category?page=1', '/category', '/destination', '/destination?page=1'],
            'Merges params' => ['/category?c=d&a=b', '/category', '/destination?a=q', '/destination?a=q&c=d'],
            'Exact match' => [
                '/category/?c=d&a=b&e=f',
                '/category?a=b&c=d&e=f',
                '/destination',
                '/destination?a=b&c=d&e=f'
            ]
        ];
    }
}
