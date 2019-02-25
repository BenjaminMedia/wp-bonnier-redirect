<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Models\Redirect;
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
        $redirect = new Redirect();
        $redirect->setFrom('/this/example/path')
            ->setTo('/new/page/slug')
            ->setCode(301)
            ->setLocale('da')
            ->setWpID(101)
            ->setType('post-slug-change');
        try {
            $this->redirectRepository->save($redirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed saving redirect (%s)', $exception->getMessage()));
        }

        $initialRedirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $initialRedirects);
        $this->assertRedirect(
            101,
            $initialRedirects->first(),
            '/this/example/path',
            '/new/page/slug',
            'post-slug-change'
        );

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

    public function testCannotCreateWildcardRedirectMatchingEverything()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/*',
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
        $this->assertSame('You cannot create this destructive wildcard redirect!', $errors['redirect_from']);

        $this->assertNull($this->redirectRepository->findAll());
    }

    public function testCannotCreateRedirectFromFrontpage()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/',
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
        $this->assertSame('You cannot create a redirect from the frontpage!', $errors['redirect_from']);

        $this->assertNull($this->redirectRepository->findAll());
    }
}
