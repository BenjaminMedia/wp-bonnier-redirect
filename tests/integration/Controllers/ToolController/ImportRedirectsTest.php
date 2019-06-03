<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\ToolController;

use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportRedirectsTest extends ControllerTestCase
{
    public function testCanCreateRedirectFromUploadedCSV()
    {
        $file = new UploadedFile(
            $this->getData('import-valid.csv'),
            'import-file.csv',
            'text/csv',
            null,
            true
        );
        $request = $this->createPostRequest([
            'import' => 'import',
        ], [
            'import-file' => $file
        ]);

        $this->actAsAdmin();

        $controller = $this->getToolController($request);
        $this->assertNoticeIs($controller->getNotices(), 'success', 'Redirects saved!');
        $redirects = $this->findAllRedirects();
        $this->assertCount(2, $redirects);
        $this->assertRedirect(0, $redirects[0], '/category/article', '/category', 'csv-import');
        $this->assertRedirect(0, $redirects[1], '/category/specific/article', 'https://example.com', 'csv-import', 302);
    }

    public function testMustBeAdminToImportRedirects()
    {
        $file = new UploadedFile(
            $this->getData('import-valid.csv'),
            'import-file.csv',
            'text/csv',
            null,
            true
        );
        $request = $this->createPostRequest([
            'import' => 'import'
        ], [
            'import-file' => $file
        ]);

        try {
            $this->getToolController($request);
        } catch (\Exception $exception) {
            $this->assertEquals('Unauthorized', $exception->getMessage());
            return;
        }

        $this->fail('Failed dismissing request as unauthorized');
    }

    public function testCSVMustContainAValidHeader()
    {
        $file = new UploadedFile(
            $this->getData('import-no-header.csv'),
            'import-file.csv',
            'text/csv',
            null,
            true
        );
        $request = $this->createPostRequest(['import' => 'import'], ['import-file' => $file]);

        $this->actAsAdmin();

        $controller = $this->getToolController($request);
        $this->assertNoticeIs($controller->getNotices(), 'error', 'CSV seems to be formatted incorrectly.');
    }

    public function testUploadedFileMustBeACSVFile()
    {
        $file = new UploadedFile(
            $this->getData('import-no-header.csv'),
            'original-name.csv',
            'text/plain',
            null,
            true
        );
        $request = $this->createPostRequest(['import' => 'import'], ['import-file' => $file]);

        $this->actAsAdmin();

        $controller = $this->getToolController($request);
        $this->assertNoticeIs($controller->getNotices(), 'error', 'Unable to process uploaded file.');
    }
}
