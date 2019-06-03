<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\ToolController;

use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Redirect404Test extends ControllerTestCase
{
    public function testCanCreateRedirectsFrom404CSV()
    {
        $file = new UploadedFile(
            $this->getData('404-valid.csv'),
            'fix-404.csv',
            'text/csv',
            null,
            true
        );
        $request = $this->createPostRequest(['404' => '404'], ['404-file' => $file]);

        $this->actAsAdmin();

        $controller = $this->getToolController($request);
        $this->assertNoticeIs($controller->getNotices(), 'success', 'Redirects saved!');

        $redirects = $this->findAllRedirects();
        $this->assertCount(3, $redirects);
        $this->assertRedirect(0, $redirects[0], '/some/fake/path', '/', 'csv-404-redirect');
        $this->assertRedirect(0, $redirects[1], '/some/other/path', '/', 'csv-404-redirect');
        $this->assertRedirect(0, $redirects[2], '/and/final/path', '/', 'csv-404-redirect');
    }

    public function testMustBeAdminToUpload404CSV()
    {
        $file = new UploadedFile(
            $this->getData('404-valid.csv'),
            'fix-404.csv',
            'text/csv',
            null,
            true
        );
        $request = $this->createPostRequest(['404' => '404'], ['404-file' => $file]);

        try {
            $this->getToolController($request);
        } catch (\Exception $exception) {
            $this->assertEquals('Unauthorized', $exception->getMessage());
            return;
        }

        $this->fail('Failed dismissing request as unauthorized');
    }

    public function testCreatesRedirectsBasedOnRules()
    {
        $category = $this->factory()->category->create_and_get(['slug' => 'my-test-category']);
        $post = $this->factory()->post->create_and_get([
            'post_category' => [$category->term_id],
            'post_name' => 'my-test-post'
        ]);

        $this->assertEquals('http://wp.test/my-test-category/my-test-post/', get_permalink($post));

        $file = new UploadedFile(
            $this->getData('404-rules.csv'),
            'fix-404.csv',
            'text/csv',
            null,
            true
        );
        $request = $this->createPostRequest(['404' => '404'], ['404-file' => $file]);

        $this->actAsAdmin();

        $controller = $this->getToolController($request);
        $this->assertNoticeIs($controller->getNotices(), 'success', 'Redirects saved!');

        $redirects = $this->findAllRedirects();
        $this->assertCount(5, $redirects);
        $this->assertRedirect(
            0,
            $redirects[0],
            '/my-test-category/deleted-category/my-test-post',
            '/my-test-category/my-test-post',
            'csv-404-redirect'
        );
        $this->assertRedirect(
            0,
            $redirects[1],
            '/my-test-category/deleted-post',
            '/my-test-category',
            'csv-404-redirect'
        );
        $this->assertRedirect(
            0,
            $redirects[2],
            '/deleted-category/deleted-post',
            '/',
            'csv-404-redirect'
        );
        $this->assertRedirect(
            0,
            $redirects[3],
            '/deleted-category',
            '/',
            'csv-404-redirect'
        );
        $this->assertRedirect(
            0,
            $redirects[4],
            '/my-test-post',
            '/my-test-category/my-test-post',
            'csv-404-redirect'
        );
    }

    public function testCannotCreateRedirectOnValidURL()
    {
        $category = $this->factory()->category->create_and_get(['slug' => 'my-test-category']);
        $post = $this->factory()->post->create_and_get([
            'post_category' => [$category->term_id],
            'post_name' => 'my-test-post'
        ]);

        $this->assertEquals('http://wp.test/my-test-category/my-test-post/', get_permalink($post));

        $file = new UploadedFile(
            $this->getData('404-existing.csv'),
            'fix-404.csv',
            'text/csv',
            null,
            true
        );
        $request = $this->createPostRequest(['404' => '404'], ['404-file' => $file]);

        $this->actAsAdmin();

        $controller = $this->getToolController($request);
        $this->assertNoticeIs($controller->getNotices(), 'success', 'Redirects saved!');

        $redirects = $this->findAllRedirects();
        $this->assertNull($redirects);
    }
}
