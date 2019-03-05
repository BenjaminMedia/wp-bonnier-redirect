<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController\Create;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class CreateTest extends ControllerTestCase
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $redirects);

            $this->assertManualRedirect($redirects->first(), '/example/from/slug', '/example/to/slug');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertManualRedirect($redirects->first(), '/example/from/slug', '/example/to/slug');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }

        $newRequest = $this->createPostRequest([
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/new/example/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $newRequest);
        $crudController->handlePost();

        $this->assertNoticeIs(
            $crudController->getNotices(),
            'error',
            'A redirect with the same \'from\' and \'locale\' already exists!'
        );

        try {
            $newRedirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $newRedirects);
            $this->assertManualRedirect($newRedirects->first(), '/example/from/slug', '/example/to/slug');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        try {
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
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        try {
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
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $redirects);
            $this->assertRedirect(
                0,
                $redirects->first(),
                '/polopoly.jsp*',
                '/',
                'manual'
            );
            $this->assertTrue($redirects->first()->isWildcard());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
    }

    public function testCanCreateRedirectThatKeepsQueryParams()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/from/slug',
            'redirect_to' => '/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
            'redirect_keep_query' => '1'
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $redirects);
            $this->assertRedirect(
                0,
                $redirects->first(),
                '/from/slug',
                '/to/slug',
                'manual'
            );
            $this->assertTrue($redirects->first()->keepsQuery());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
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
        $expectedNotices = [
            ['type' => 'success', 'message' => 'The redirect was saved!'],
            ['type' => 'warning', 'message' => 'The redirect was chaining, and its \'to\'-url has been updated!'],
        ];
        $this->assertNotices($expectedNotices, $notices);

        try {
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
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
    }
}
