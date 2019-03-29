<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController\Update;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class NormalizationTest extends ControllerTestCase
{
    /**
     * @dataProvider normalizeFromUrlProvider
     *
     * @param string $from
     * @param string $expectedResult
     */
    public function testCreatingRedirectsWillNormalizeFromInput(string $from, string $expectedResult)
    {
        $redirect = $this->createRedirect('/different/from', '/expected/destination');
        $this->assertRedirectCreated($redirect);

        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getID(),
            'redirect_from' => $from,
            'redirect_to' => '/expected/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirectsAfter = $this->findAllRedirects();
        $this->assertCount(1, $redirectsAfter);
        $this->assertRedirect(
            0,
            $redirectsAfter->first(),
            $expectedResult,
            '/expected/destination',
            'manual'
        );
    }

    /**
     * @dataProvider normalizeToUrlProvider
     *
     * @param string $toUrl
     * @param string $expectedResult
     */
    public function testCreatingRedirectsWillNormalizeToInput(string $toUrl, string $expectedResult)
    {
        $redirect = $this->createRedirect('/expected/from', '/another/to');
        $this->assertRedirectCreated($redirect);

        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getID(),
            'redirect_from' => '/expected/from',
            'redirect_to' => $toUrl,
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirectsAfter = $this->findAllRedirects();
        $this->assertCount(1, $redirectsAfter);
        $this->assertRedirect(
            0,
            $redirectsAfter->first(),
            '/expected/from',
            $expectedResult,
            'manual'
        );
    }

    public function normalizeFromUrlProvider()
    {
        return [
            'Base Url' => ['http://example.com/', '/'],
            'Url with slug' => ['https://example.com/path/to/article/', '/path/to/article'],
            'Url with different case' => ['https://Example.com', '/'],
            'Url with query params' => ['https://example.com/slug/?c=d&a=b', '/slug?a=b&c=d'],
            'Url with www' => ['https://www.example.com/', '/'],
            'Url without scheme' => ['www.example.com/slug', '/slug'],
            'Url with ÆØÅ' => [
                'https://www.example.com/hætte/østers/påske',
                '/hætte/østers/påske'
            ],
            'Url with encoded ÆØÅ' => [
                'https://www.example.com/h%C3%A6tte/%C3%B8sters/p%C3%A5ske',
                '/hætte/østers/påske'
            ],
            'Url with wildcard' => ['https://example.com/path/to/*', '/path/to/*'],
            'Urlencoded' => ['%2Fpath%2Fwith%2F%3Fquery%3Dparams', '/path/with?query=params'],
            'Internal url' => [home_url(), '/'],
            'Internal url with slug' => [home_url('/slug/path/'), '/slug/path'],
            'Internal url with different case' => [home_url('/Slug/Path'), '/slug/path'],
            'Interal url with query params' => [home_url('/slug/?c=d&a=b'), '/slug?a=b&c=d'],
            'Relative path' => ['/example/slug/', '/example/slug'],
            'Relative path without starting slash' => ['example/slug', '/example/slug'],
            'Relative path with query params' => ['/example/?c=d&a=b', '/example?a=b&c=d'],
            'Relative path with ÆØÅ' => [
                '/hætte/østers/påske',
                '/hætte/østers/påske'
            ],
            'Relative path with encoded ÆØÅ' => [
                '/h%C3%A6tte/%C3%B8sters/p%C3%A5ske',
                '/hætte/østers/påske'
            ],
            'Relative path with wildcard' => ['/path/to/*', '/path/to/*'],
        ];
    }

    public function normalizeToUrlProvider()
    {
        return [
            'Base Url' => ['http://example.com/', 'http://example.com'],
            'Url with slug' => ['https://example.com/path/to/article/', 'https://example.com/path/to/article'],
            'Url with different case' => ['https://Example.com', 'https://example.com'],
            'Url with query params' => ['https://example.com/slug/?c=d&a=b', 'https://example.com/slug?a=b&c=d'],
            'Url with www' => ['https://www.example.com/', 'https://www.example.com'],
            'Url without scheme' => ['www.example.com/slug', 'http://www.example.com/slug'],
            'Url with ÆØÅ' => [
                'https://www.example.com/hætte/østers/påske',
                'https://www.example.com/hætte/østers/påske'
            ],
            'Url with encoded ÆØÅ' => [
                'https://www.example.com/h%C3%A6tte/%C3%B8sters/p%C3%A5ske',
                'https://www.example.com/hætte/østers/påske'
            ],
            'Url with wildcard' => ['https://example.com/path/to/*', 'https://example.com/path/to'],
            'Urlencoded' => ['%2Fpath%2Fwith%2F%3Fquery%3Dparams', '/path/with?query=params'],
            'Internal url' => [home_url(), '/'],
            'Internal url with slug' => [home_url('/slug/path/'), '/slug/path'],
            'Internal url with different case' => [home_url('/Slug/Path'), '/slug/path'],
            'Interal url with query params' => [home_url('/slug/?c=d&a=b'), '/slug?a=b&c=d'],
            'Relative path' => ['/example/slug/', '/example/slug'],
            'Relative path without starting slash' => ['example/slug', '/example/slug'],
            'Relative path with query params' => ['/example/?c=d&a=b', '/example?a=b&c=d'],
            'Relative path with ÆØÅ' => [
                '/hætte/østers/påske',
                '/hætte/østers/påske'
            ],
            'Relative path with encoded ÆØÅ' => [
                '/h%C3%A6tte/%C3%B8sters/p%C3%A5ske',
                '/hætte/østers/påske'
            ],
            'Relative path with wildcard' => ['/path/to/*', '/path/to'],
        ];
    }
}
