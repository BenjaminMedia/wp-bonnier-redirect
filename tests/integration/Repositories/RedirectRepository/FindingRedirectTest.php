<?php

namespace Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepository;

use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepositoryTestCase;

class FindingRedirectTest extends RedirectRepositoryTestCase
{
    public function testCanFindSimpleRedirect()
    {
        $redirect = $this->createRedirect('/path/to/old/article', '/path/to/new/article');

        try {
            $foundRedirect = $this->repository->findRedirectByPath('/path/to/old/article', 'da');
            $this->assertInstanceOf(Redirect::class, $foundRedirect);
            $this->assertSameRedirects($redirect, $foundRedirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirect (%s)', $exception->getMessage()));
        }
    }

    /**
     * @dataProvider malformedPathProvider
     *
     * @param string $path
     * @param string $from
     */
    public function testCanFindRedirectWithMalformedPath(string $path, string $from)
    {
        $redirect = $this->createRedirect($from, '/destination');

        try {
            $foundRedirect = $this->repository->findRedirectByPath($path, 'da');
            $this->assertInstanceOf(
                Redirect::class,
                $foundRedirect,
                sprintf('Could not find redirect where path was \'%s\'', $path)
            );
            $this->assertSameRedirects($redirect, $foundRedirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirect (%s)', $exception->getMessage()));
        }
    }

    public function malformedPathProvider()
    {
        return [
            'Url-encoded path' => ['/path/which%20is%20urlencoded/', '/path/which is urlencoded'],
            'Unordered query params' => ['/path/?params=out-of&order=they-are', '/path?order=they-are&params=out-of'],
            'Mixed case path' => ['/Path/With/Mixed/Case', '/path/with/mixed/case'],
            'Inproper slash use' => ['path/with/invalid/slashes///', '/path/with/invalid/slashes'],
            'URL with domain' => ['https://wp.test/path/to/article', '/path/to/article'],
        ];
    }
}
