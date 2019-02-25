<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class UpdateControllerTest extends ControllerTestCase
{
    public function testCanUpdateToRedirect()
    {
        $redirect = $this->createRedirect('/from/this/path', '/to/this/path');

        $createdRedirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $createdRedirects);
        $this->assertSameRedirects($redirect, $createdRedirects->first());

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
        $this->assertSame('Redirect updated!', $notices[0]['success']);

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

        $createdRedirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $createdRedirects);
        $this->assertRedirect(
            0,
            $createdRedirects->first(),
            '/from/this/path',
            '/to/this/path',
            'manual'
        );

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
        $this->assertSame('Redirect updated!', $notices[0]['success']);

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
        $updatingRedirect = $this->createRedirect('/redirect/to/be/updated', '/to/somewhere');

        $createdRedirects = $this->redirectRepository->findAll();
        $this->assertCount(2, $createdRedirects);
        $this->assertSameRedirects($existingRedirect, $createdRedirects->first());
        $this->assertSameRedirects($updatingRedirect, $createdRedirects->last());

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
}
