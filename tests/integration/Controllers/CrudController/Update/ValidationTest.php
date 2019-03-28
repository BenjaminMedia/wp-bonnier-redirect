<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController\Update;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class ValidationTest extends ControllerTestCase
{
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

        $this->assertNoticeIs(
            $crudController->getNotices(),
            'error',
            'A redirect with the same \'from\' and \'locale\' already exists!'
        );

        $redirectsAfter = $this->findAllRedirects();
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

        $this->assertNoticeIs($crudController->getNotices(), 'error', 'From and to urls seems identical!');

        $redirectsAfter = $this->findAllRedirects();
        $this->assertCount(1, $redirectsAfter);
        $this->assertSameRedirects($redirect, $redirectsAfter->first());
    }

    public function testCannotCreateRedirectWithoutSpecifyingLocale()
    {
        $redirect = $this->createRedirect(
            '/from/this/slug',
            '/to/this/slug'
        );

        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getId(),
            'redirect_from' => '/from/this/slug',
            'redirect_to' => '/to/this/slug',
            'redirect_locale' => '',
            'redirect_code' => 301,
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());
        $this->assertArrayHasKey('redirect_locale', $crudController->getValidationErrors());
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
}
