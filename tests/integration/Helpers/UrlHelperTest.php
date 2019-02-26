<?php

namespace Bonnier\WP\Redirect\Tests\integration\Helpers;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Tests\integration\TestCase;

class UrlHelperTest extends TestCase
{
    /**
     * @dataProvider normalizeUrlProvider
     * @param string $url
     * @param string $expectedResult
     */
    public function testCanNormalizeUrl(string $url, string $expectedResult)
    {
        $normalized = UrlHelper::normalizeUrl($url);
        $this->assertSame($expectedResult, $normalized);
    }

    public function normalizeUrlProvider()
    {
        return [
            'Base Url' => ['http://example.com/', 'http://example.com'],
            'Url with slug' => ['https://example.com/path/to/article/', 'https://example.com/path/to/article'],
            'Url with different case' => ['https://Example.com', 'https://example.com'],
            'Url with query params' => ['https://example.com/slug/?c=d&a=b', 'https://example.com/slug?a=b&c=d'],
            'Url with www' => ['https://www.example.com/', 'https://www.example.com'],
            'Urlencoded' => ['%2Fpath%2Fwith%2F%3Fquery%3Dparams', '/path/with?query=params'],
            'Internal url' => [home_url(), '/'],
            'Internal url with slug' => [home_url('/slug/path/'), '/slug/path'],
            'Internal url with different case' => [home_url('/Slug/Path'), '/slug/path'],
            'Interal url with query params' => [home_url('/slug/?c=d&a=b'), '/slug?a=b&c=d'],
            'Relative path' => ['/example/slug/', '/example/slug'],
            'Relative path without starting slash' => ['example/slug', '/example/slug'],
            'Relative path with query params' => ['/example/?c=d&a=b', '/example?a=b&c=d'],
        ];
    }
}
