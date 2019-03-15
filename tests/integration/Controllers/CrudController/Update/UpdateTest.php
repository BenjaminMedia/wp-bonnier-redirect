<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController\Update;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class UpdateTest extends ControllerTestCase
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $updatedRedirects = $this->findAllRedirects();
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $updatedRedirects = $this->findAllRedirects();
        $this->assertCount(1, $updatedRedirects);
        $this->assertRedirect(
            0,
            $updatedRedirects->first(),
            '/from/new/path',
            '/to/this/path',
            'manual'
        );
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

        $expectedNotices = [
            ['type' => 'success', 'message' => 'The redirect was saved!'],
            ['type' => 'warning', 'message' => 'The redirect was chaining, and its \'to\'-url has been updated!'],
        ];

        $this->assertNotices($expectedNotices, $crudController->getNotices());

        $redirectsAfter = $this->findAllRedirects();
        $this->assertCount(2, $redirectsAfter);
        $this->assertSameRedirects($existingRedirect, $redirectsAfter->first());
        $updatingRedirect->setTo('/final/destination');
        $this->assertSameRedirects($updatingRedirect, $redirectsAfter->last());
    }

    public function testUpdatingReverseRedirectOfExistingRedirectDeletesOldRedirect()
    {
        $oldRedirect = $this->createRedirect('/slug/alfa', '/slug/bravo');
        $this->assertRedirectCreated($oldRedirect);
        $updatingRedirect = $this->createRedirect('/some/other', '/redirect');
        $this->assertRedirectCreated($updatingRedirect, 2);

        $request = $this->createPostRequest([
            'redirect_id' => $updatingRedirect->getID(),
            'redirect_from' => '/slug/bravo',
            'redirect_to' => '/slug/alfa',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();

        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            0,
            $redirects->first(),
            '/slug/bravo',
            '/slug/alfa',
            'manual'
        );
    }

    public function testCanUpdateRedirectWithToWhichAlreadyExists()
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $newRedirects = $this->findAllRedirects();
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

    public function testCanUpdateWildcardRedirect()
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

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->first(),
            '/polopoly.jsp*',
            '/',
            'manual'
        );
        $this->assertTrue($redirects->first()->isWildcard());
    }

    public function testCanUpdateRedirectThatKeepsQueryParams()
    {
        $redirect = $this->createRedirect('/from/slug', '/to/slug');
        $this->assertRedirectCreated($redirect);
        $this->assertFalse($redirect->keepsQuery());

        $request = $this->createPostRequest([
            'redirect_id' => $redirect->getID(),
            'redirect_from' => '/from/slug',
            'redirect_to' => '/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
            'redirect_keep_query' => '1'
        ]);

        $crudController = new CrudController($this->redirectRepository, $request);
        $crudController->handlePost();

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->first(),
            '/from/slug',
            '/to/slug',
            'manual'
        );
        $this->assertTrue($redirects->first()->keepsQuery());
    }
}
