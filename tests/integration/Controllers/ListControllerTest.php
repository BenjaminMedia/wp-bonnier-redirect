<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers;

use Bonnier\WP\Redirect\Controllers\ListController;
use Bonnier\WP\Redirect\Models\Redirect;

class ListControllerTest extends ControllerTestCase
{
    public function _setUp()
    {
        // Global variable used when constructing ListController in
        // /wp-admin/includes/class-wp-screen.php:209
        $GLOBALS['hook_suffix'] = null;
        parent::_setUp();
    }

    public function testCanDeleteSingleRedirect()
    {
        $redirect = $this->createRedirect(
            '/example/slug',
            '/new/slug'
        );

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            0,
            $redirects->first(),
            '/example/slug',
            '/new/slug',
            'manual'
        );


        $request = $this->createGetRequest([
            'page' => 'bonnier-redirects',
            'action' => 'delete_redirect',
            'redirect_id' => $redirect->getID(),
            '_wpnonce' => wp_create_nonce('delete_redirect_nonce'),
        ]);

        $listController = new ListController($this->redirectRepository, $request);
        try {
            $listController->prepare_items();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed preparing items for ListController (%s)', $exception->getMessage()));
            return;
        }

        $this->assertNull($this->findAllRedirects());
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

        $createdRedirects = $this->findAllRedirects();
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
        try {
            $listController->prepare_items();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed preparing items for ListController (%s)', $exception->getMessage()));
            return;
        }

        $this->assertNull($this->findAllRedirects());
    }
}
