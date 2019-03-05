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
     */
    public function testIgnoringQueryParamsActuallyIgnoresAllQueryParams(string $path, string $from)
    {
        $redirect = $this->createRedirect($from, '/destination');

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
        $this->assertSame($redirect->getID(), $foundRedirect->getID());
        $this->assertSame($from, $foundRedirect->getFrom());
        $this->assertSame($expectedTo, $foundRedirect->getTo());
    }

    public function ignoreQueryParamProvider()
    {
        return [
            'With pagination' => ['/category?page=1', '/category'],
            'With multiple params' => ['/page/?a=b&c=d&e=f', '/page'],
        ];
    }

    public function keepQueryParamProvider()
    {
        return [
            'Pagination' => ['/category?page=1', '/category', '/destination', '/destination?page=1'],
            'Merges params' => ['/category?c=d&a=b', '/category', '/destination?a=q', '/destination?a=q&c=d'],
        ];
    }
}
