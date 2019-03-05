<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers;

use Bonnier\WP\Redirect\Controllers\ListController;
use Bonnier\WP\Redirect\Models\Redirect;

class ListControllerTest extends ControllerTestCase
{
    public function setUp()
    {
        // Global variable used when constructing ListController in
        // /wp-admin/includes/class-wp-screen.php:209
        $GLOBALS['hook_suffix'] = null;
        parent::setUp();
    }

    public function testCanDeleteSingleRedirect()
    {
        $redirect = $this->createRedirect(
            '/example/slug',
            '/new/slug'
        );

        try {
            $redirects = $this->redirectRepository->findAll();
            $this->assertCount(1, $redirects);

            $this->assertRedirect(
                0,
                $redirects->first(),
                '/example/slug',
                '/new/slug',
                'manual'
            );
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }


        $request = $this->createGetRequest([
            'page' => 'bonnier-redirects',
            'action' => 'delete_redirect',
            'redirect_id' => $redirect->getID(),
            '_wpnonce' => wp_create_nonce('delete_redirect_nonce'),
        ]);

        $listController = new ListController($this->redirectRepository, $request);
        $listController->prepare_items();

        try {
            $this->assertNull($this->redirectRepository->findAll());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
    }

    public function testCanBulkDeleteRedirects()
    {
        $redirects = collect([
            $this->createRedirect(
                '/first/redirect',
                '/first/destination'
            ),
            $this->createRedirect(
                '/second/redirect',
                '/second/destination'
            ),
            $this->createRedirect(
                '/third/redirect',
                '/third/destination'
            ),
        ]);

        try {
            $createdRedirects = $this->redirectRepository->findAll();
            $this->assertCount(3, $createdRedirects);
            $createdRedirects->each(function (Redirect $redirect, int $index) use ($redirects) {
                /** @var Redirect $expectedRedirect */
                $expectedRedirect = $redirects->get($index);
                $this->assertRedirect(
                    $expectedRedirect->getWpID(),
                    $redirect,
                    $expectedRedirect->getFrom(),
                    $expectedRedirect->getTo(),
                    $expectedRedirect->getType()
                );
            });
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }

        $request = $this->createGetRequest([
            'page' => 'bonnier-redirects',
            'action' => 'bulk-delete',
            // The nonce is created by the keyword 'bulk' and the plural constructor argument for \WP_List_Table
            '_wpnonce' => wp_create_nonce('bulk-redirects'),
            'redirects' => $redirects->map(function (Redirect $redirect) {
                return $redirect->getID();
            })->toArray(),
        ]);

        $listController = new ListController($this->redirectRepository, $request);
        $listController->prepare_items();

        try {
            $this->assertNull($this->redirectRepository->findAll());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
        }
    }
}
