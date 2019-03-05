<?php

namespace Bonnier\WP\Redirect\Tests\unit\Helpers;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Codeception\Test\Unit;

class UrlHelperTest extends Unit
{
    /**
     * @dataProvider urlSanitizeProvider
     * @param string $expected
     * @param string $url
     */
    public function testCanProperlySanitizeUrl(string $expected, string $url)
    {
        $path = UrlHelper::sanitizePath($url);

        $this->assertSame($expected, $path);
    }

    public function testSanitizingRemovesTrailingSlashes()
    {
        $url = 'https://willow.test/path/to/article/';

        $path = UrlHelper::sanitizePath($url);

        $this->assertSame('/path/to/article', $path);
    }

    public function testSanitizingEnsuresBeginningWithSlash()
    {
        $url = 'path/to/article';

        $path = UrlHelper::sanitizePath($url);

        $this->assertSame('/path/to/article', $path);
    }

    public function testSanitizingStripsQueryParams()
    {
        $url = 'https://willow.test/path/to/article/?query=params&tobe=removed';

        $path = UrlHelper::sanitizePath($url);

        $this->assertSame('/path/to/article', $path);
    }

    /**
     * @dataProvider normalizePathProvider
     * @param string $url
     * @param string $expected
     */
    public function testNormalizePath(string $url, string $expected)
    {
        $path = UrlHelper::normalizePath($url);
        $this->assertSame($expected, $path);
    }

    public function testParseQueryParamsConvertsToKeySortedAssociatedArray()
    {
        $url = 'https://willow.test/slug?a_param=first_parameter&c_param=third_parameter&b_param=second_parameter';

        $queryParams = UrlHelper::parseQueryParams($url);

        $this->assertSame([
            'a_param' => 'first_parameter',
            'b_param' => 'second_parameter',
            'c_param' => 'third_parameter',
        ], $queryParams);
    }

    public function testParseQueryParamsSortsKeysAndValues()
    {
        $url = 'https://willow.test/slug?a=1&c=3&b[]=2&b[]=1';

        $queryParams = UrlHelper::parseQueryParams($url);

        $this->assertSame([
            'a' => '1',
            'b' => [
                '1',
                '2',
            ],
            'c' => '3'
        ], $queryParams);
    }

    public function urlSanitizeProvider()
    {
        return [
            'URL With ø' => [
                '/have-og-terrasse/fleksible-højbede-til-terrassen',
                '/have-og-terrasse/fleksible-h%C3%B8jbede-til-terrassen'
            ],
            'URL with å and æ' => [
                '/varme-ventilation-isolering-og-skorsten/sådan-tilslutter-du-selv-brændeovnen',
                '/varme-ventilation-isolering-og-skorsten/s%C3%A5dan-tilslutter-du-selv-br%C3%A6ndeovnen'
            ],
            'URL with ö' => [
                '/byggeri/ut-med-de-immiga-fönsten',
                '/byggeri/ut-med-de-immiga-f%C3%B6nsten'
            ],
            'URL with spaces' => [
                '/2 nr.   gör det själv-bok',
                '/2+nr.+++g%C3%B6r+det+sj%C3%A4lv-bok'
            ],
            'URL double-encoded' => [
                '/2 nr.   gör det själv-bok',
                '%2F2%2Bnr.%2B%2B%2Bg%25C3%25B6r%2Bdet%2Bsj%25C3%25A4lv-bok'
            ]
        ];
    }

    public function normalizePathProvider()
    {
        return [
            'Removes host' => ['https://willow.test/path/test', '/path/test'],
            'Fixes improper slashes' => ['path/test/', '/path/test'],
            'Decodes url' => ['%2Fpath%2Fwith%2F%3Fquery%3Dparams', '/path/with?query=params'],
            'Double decodes url' => [
                '%2Fpath%2Fwith%2F%3Fparam%3Dhttps%253A%252F%252Fexample.com',
                '/path/with?param=https://example.com'
            ],
            'Sorting params' => [
                'https://willow.test/path/to/article/?second=second_param&first=first_param',
                '/path/to/article?first=first_param&second=second_param'
            ],
        ];
    }
}
