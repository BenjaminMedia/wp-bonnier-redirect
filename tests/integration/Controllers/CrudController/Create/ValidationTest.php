<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController\Create;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class ValidationTest extends ControllerTestCase
{
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

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_from', $errors);
        $this->assertSame('The \'from\'-value cannot be empty!', $errors['redirect_from']);

        try {
            $this->assertNull($this->redirectRepository->findAll());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_to', $errors);
        $this->assertSame('The \'to\'-value cannot be empty!', $errors['redirect_to']);

        try {
            $this->assertNull($this->redirectRepository->findAll());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_from', $errors);
        $this->assertSame($error, $errors['redirect_from']);

        try {
            $this->assertNull($this->redirectRepository->findAll());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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

        $this->assertNoticeIs($crudController->getNotices(), 'error', 'From and to urls seems identical!');

        try {
            $this->assertNull($this->redirectRepository->findAll());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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
}
