<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class UpdateControllerTest extends ControllerTestCase
{
    public function testCanUpdateToRedirect()
    {
        $redirect = $this->createRedirect('/from/this/path', '/to/this/path');

        $this->assertRedirectCreated($redirect);

        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getID(),
            'redirect_from' => '/from/this/path',
            'redirect_to' => '/to/new/path',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();
        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved! <a href="', $notices[0]['success']);

        $updatedRedirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $updatedRedirects);
        $this->assertRedirect(
            0,
            $updatedRedirects->first(),
            '/from/this/path',
            '/to/new/path',
            'manual'
        );
    }

    public function testCanUpdateFromRedirect()
    {
        $redirect = $this->createRedirect('/from/this/path', '/to/this/path');
        $this->assertRedirectCreated($redirect);

        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getID(),
            'redirect_from' => '/from/new/path',
            'redirect_to' => '/to/this/path',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();
        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved! <a href="', $notices[0]['success']);

        $updatedRedirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $updatedRedirects);
        $this->assertRedirect(
            0,
            $updatedRedirects->first(),
            '/from/new/path',
            '/to/this/path',
            'manual'
        );
    }

    public function testCannotUpdateFromIfAlreadyExists()
    {
        $existingRedirect = $this->createRedirect('/existing/redirect', '/to/somewhere');
        $this->assertRedirectCreated($existingRedirect);
        $updatingRedirect = $this->createRedirect('/redirect/to/be/updated', '/to/somewhere');
        $this->assertRedirectCreated($updatingRedirect, 2);

        $request = $this->createPostRequest([
            'redirect_id' => $updatingRedirect->getID(),
            'redirect_from' => '/existing/redirect',
            'redirect_to' => '/to/somewhere',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('error', $notices[0]);
        $this->assertSame('A redirect with the same \'from\' and \'locale\' already exists!', $notices[0]['error']);

        $redirectsAfter = $this->redirectRepository->findAll();
        $this->assertCount(2, $redirectsAfter);
        $this->assertSameRedirects($existingRedirect, $redirectsAfter->first());
        $this->assertRedirect(
            0,
            $redirectsAfter->last(),
            '/redirect/to/be/updated',
            '/to/somewhere',
            'manual'
        );
    }

    /**
     * @dataProvider sameFromToProvider
     *
     * @param string $fromUrl
     * @param string $toUrl
     * @param bool $identical
     */
    public function testCannotUpdateRedirectWithSameFromAndTo(string $fromUrl, string $toUrl, bool $identical = false)
    {
        $redirect = $this->createRedirect($fromUrl, '/totally/different/slug');
        $this->assertRedirectCreated($redirect);

        if ($identical) {
            $this->assertSame($fromUrl, $toUrl);
        } else {
            $this->assertNotSame($fromUrl, $toUrl);
        }
        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getID(),
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

        $redirectsAfter = $this->redirectRepository->findAll();

        $this->assertCount(1, $redirectsAfter);
        $this->assertSameRedirects($redirect, $redirectsAfter->first());
    }

    public function testUpdatingRedirectWithToWhichAlreadyExistsInFromAvoidsMakingChains()
    {
        $existingRedirect = $this->createRedirect('/original/from', '/final/destination');
        $this->assertRedirectCreated($existingRedirect);
        $updatingRedirect = $this->createRedirect('/another/from', '/different/to');
        $this->assertRedirectCreated($updatingRedirect, 2);

        $request = $this->createPostRequest([
            'redirect_id' => $updatingRedirect->getID(),
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
        $updatingRedirect->setTo('/final/destination');
        $this->assertSameRedirects($updatingRedirect, $redirectsAfter->last());
    }

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

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved!', $notices[0]['success']);

        $redirectsAfter = $this->redirectRepository->findAll();
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

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $notices = $crudController->getNotices();
        $this->assertCount(1, $notices);
        $this->assertArrayHasKey('success', $notices[0]);
        $this->assertContains('The redirect was saved!', $notices[0]['success']);

        $redirectsAfter = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirectsAfter);
        $this->assertRedirect(
            0,
            $redirectsAfter->first(),
            '/expected/from',
            $expectedResult,
            'manual'
        );
    }

    public function testCanCreateRedirectWithToWhichAlreadyExists()
    {
        $existingRedirect = $this->createRedirect('/from/something', '/to/destination');
        $this->assertRedirectCreated($existingRedirect);
        $updatingRedirect = $this->createRedirect('/from/another/slug', '/to/another/destination');
        $this->assertRedirectCreated($updatingRedirect, 2);

        $request = $this->createPostRequest([
            'redirect_id' => $updatingRedirect->getID(),
            'redirect_from' => '/from/another/slug',
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
            '/from/another/slug',
            '/to/destination',
            'manual'
        );
    }

    public function testUpdatingManualRedirectUpdatesOlderToAvoidChains()
    {
        $redirect = $this->createRedirect(
            '/this/example/path',
            '/new/page/slug',
            'post-slug-change',
            101
        );
        $this->assertRedirectCreated($redirect);
        $updatingRedirect = $this->createRedirect('/another/from', '/final/destination');
        $this->assertRedirectCreated($updatingRedirect, 2);

        $request = $this->createPostRequest([
            'redirect_id' => $updatingRedirect->getID(),
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

    public function testCanCreateWildcardRedirect()
    {
        $redirect = $this->createRedirect('/polopoly.jsp?id=412', '/');
        $this->assertRedirectCreated($redirect);

        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getID(),
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

    public function normalizeToUrlProvider()
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
