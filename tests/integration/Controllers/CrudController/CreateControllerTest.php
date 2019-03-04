<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class CreateControllerTest extends ControllerTestCase
{
    public function testCanCreateNewRedirect()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/example/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);
        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);

        $this->assertManualRedirect($redirects->first(), '/example/from/slug', '/example/to/slug');
    }

    public function testCannotCreateMultipleRedirectsWithSameFrom()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/example/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $redirects = $this->redirectRepository->findAll();
        $this->assertManualRedirect($redirects->first(), '/example/from/slug', '/example/to/slug');

        $newRequest = $this->createPostRequest([
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/new/example/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $newRequest);
        $crudController->handlePost();
        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('error', $notices[0]);
        $this->assertSame($notices[0]['error'], 'A redirect with the same \'from\' and \'locale\' already exists!');

        $newRedirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $newRedirects);
        $this->assertManualRedirect($newRedirects->first(), '/example/from/slug', '/example/to/slug');
    }

    public function testCreatingManualRedirectUpdatesOlderToAvoidChains()
    {
        $redirect = $this->createRedirect(
            '/this/example/path',
            '/new/page/slug',
            'post-slug-change',
            101
        );
        $this->assertRedirectCreated($redirect);

        $request = $this->createPostRequest([
            'redirect_from' => '/new/page/slug',
            'redirect_to' => '/final/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $newRedirects = $this->redirectRepository->findAll();

        $this->assertCount(2, $newRedirects);

        $this->assertRedirect(
            101,
            $newRedirects->first(),
            '/this/example/path',
            '/final/destination',
            'post-slug-change'
        );
        $this->assertManualRedirect(
            $newRedirects->last(),
            '/new/page/slug',
            '/final/destination',
            'da'
        );
    }

    public function testCannotCreateRedirectWithEmptyFrom()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '',
            'redirect_to' => '/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('error', $notices[0]);
        $this->assertSame('Invalid data was submitted - fix fields marked with red.', $notices[0]['error']);

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_from', $errors);
        $this->assertSame('The \'from\'-value cannot be empty!', $errors['redirect_from']);

        $this->assertNull($this->redirectRepository->findAll());
    }

    public function testCannotCreateRedirectWithEmptyTo()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/from/slug',
            'redirect_to' => '',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('error', $notices[0]);
        $this->assertSame('Invalid data was submitted - fix fields marked with red.', $notices[0]['error']);

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_to', $errors);
        $this->assertSame('The \'to\'-value cannot be empty!', $errors['redirect_to']);

        $this->assertNull($this->redirectRepository->findAll());
    }

    /**
     * @dataProvider destructiveRedirectsProvider
     *
     * @param string $from
     * @param string $error
     */
    public function testCannotCreateDestructiveRedirects(string $from, string $error)
    {
        $request = $this->createPostRequest([
            'redirect_from' => $from,
            'redirect_to' => '/impossible/redirect',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('error', $notices[0]);
        $this->assertSame('Invalid data was submitted - fix fields marked with red.', $notices[0]['error']);

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_from', $errors);
        $this->assertSame($error, $errors['redirect_from']);

        $this->assertNull($this->redirectRepository->findAll());
    }

    /**
     * @dataProvider sameFromToProvider
     *
     * @param string $fromUrl
     * @param string $toUrl
     * @param bool $identical
     */
    public function testCannotCreateRedirectWithSameFromAndTo(string $fromUrl, string $toUrl, bool $identical = false)
    {
        if ($identical) {
            $this->assertSame($fromUrl, $toUrl);
        } else {
            $this->assertNotSame($fromUrl, $toUrl);
        }
        $request = $this->createPostRequest([
            'redirect_from' => $fromUrl,
            'redirect_to' => $toUrl,
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('error', $notices[0]);
        $this->assertSame('From and to urls seems identical!', $notices[0]['error']);

        $this->assertNull($this->redirectRepository->findAll());
    }

    public function testCreatingRedirectWithToWhichAlreadyExistsInFromAvoidsMakingChains()
    {
        $existingRedirect = $this->createRedirect('/original/from', '/final/destination');

        $this->assertRedirectCreated($existingRedirect);

        $request = $this->createPostRequest([
            'redirect_from' => '/another/from',
            'redirect_to' => '/original/from',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(2, $notices, 'Not all notices was registered!');
        $this->assertArrayHasKey('success', $notices[0], 'A success notice was missing!');
        $this->assertArrayHasKey('warning', $notices[1], 'A warning notice was missing!');
        $this->assertContains('The redirect was saved!', $notices[0]['success']);
        $this->assertContains(
            'The redirect was chaining, and its \'to\'-url has been updated!',
            $notices[1]['warning']
        );

        $redirectsAfter = $this->redirectRepository->findAll();
        $this->assertCount(2, $redirectsAfter);
        $this->assertSameRedirects($existingRedirect, $redirectsAfter->first());
        $this->assertRedirect(
            0,
            $redirectsAfter->last(),
            '/another/from',
            '/final/destination',
            'manual'
        );
    }

    /**
     * @dataProvider normalizeFromUrlProvider
     *
     * @param string $from
     * @param string $expectedResult
     */
    public function testCreatingRedirectsWillNormalizeFromInput(string $from, string $expectedResult)
    {
        $request = $this->createPostRequest([
            'redirect_from' => $from,
            'redirect_to' => '/expected/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved!', $notices[0]['success']);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->first(),
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
        $request = $this->createPostRequest([
            'redirect_from' => '/expected/from',
            'redirect_to' => $toUrl,
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved!', $notices[0]['success']);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->first(),
            '/expected/from',
            $expectedResult,
            'manual'
        );
    }

    public function testCanCreateRedirectWithToWhichAlreadyExists()
    {
        $existingRedirect = $this->createRedirect('/from/something', '/to/destination');

        $this->assertRedirectCreated($existingRedirect);

        $request = $this->createPostRequest([
            'redirect_from' => '/another/from/slug',
            'redirect_to' => '/to/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved!', $notices[0]['success']);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(2, $redirects);
        $this->assertSameRedirects($existingRedirect, $redirects->first());
        $this->assertRedirect(
            0,
            $redirects->last(),
            '/another/from/slug',
            '/to/destination',
            'manual'
        );
    }

    public function testCanCreateWildcardRedirect()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/polopoly.jsp*',
            'redirect_to' => '/',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved!', $notices[0]['success']);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->first(),
            '/polopoly.jsp*',
            '/',
            'manual'
        );
    }

    public function destructiveRedirectsProvider()
    {
        return [
            'Wildcard /*' => ['/*', 'You cannot create this destructive wildcard redirect!'],
            'Wildcard *' => ['*', 'You cannot create this destructive wildcard redirect!'],
            'Frontpage' => ['/', 'You cannot create a redirect from the frontpage!']
        ];
    }

    public function sameFromToProvider()
    {
        return [
            'Identical' => ['/from/slug', '/from/slug', true],
            'Same, but to has domain' => ['/from/slug', home_url('/from/slug')],
            'Same, but slashes are different' => ['from/slug/', '/from/slug'],
            'Same, but query are ordered different' => ['/from/slug?c=d&a=b', 'from/slug/?a=b&c=d'],
        ];
    }

    public function normalizeFromUrlProvider()
    {
        return [
            'Base Url' => ['http://example.com/', '/'],
            'Url with slug' => ['https://example.com/path/to/article/', '/path/to/article'],
            'Url with different case' => ['https://Example.com', '/'],
            'Url with query params' => ['https://example.com/slug/?c=d&a=b', '/slug?a=b&c=d'],
            'Url with www' => ['https://www.example.com/', '/'],
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
            'Frontpage with query params' => ['/?s=test', '/?s=test'],
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
            'Frontpage with query params' => ['/?s=test', '/?s=test'],
        ];
    }
}
