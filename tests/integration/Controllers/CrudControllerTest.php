<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CrudControllerTest extends TestCase
{
    public function testCanCreateNewRedirect()
    {
        $database = new DB();
        $redirectRepository = new RedirectRepository($database);
        $request = Request::create('/wp-admin/admin.php?page=add-redirect', 'POST', [
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/example/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);
        $crudController = new CrudController($redirectRepository, $request);
        $crudController->handlePost();

        $redirects = $redirectRepository->findAll();
        $this->assertCount(1, $redirects);

        /** @var Redirect $redirect */
        $redirect = $redirects->first();

        $this->assertSame('/example/from/slug', $redirect->getFrom());
        $this->assertSame('/example/to/slug', $redirect->getTo());
        $this->assertSame('manual', $redirect->getType());
        $this->assertSame('da', $redirect->getLocale());
        $this->assertSame(301, $redirect->getCode());
    }
}
