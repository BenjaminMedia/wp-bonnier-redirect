<?php

namespace Bonnier\WP\Redirect\Tests\Integration\Models;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;

class RedirectTest extends \Codeception\TestCase\WPTestCase
{
    /** @var RedirectRepository */
    private $redirectRepository;

    public function setUp()
    {
        parent::setUp();

        global $wpdb;
        $database = new DB($wpdb);
        $this->redirectRepository = new RedirectRepository($database);
    }

    public function testCanSaveRedirect()
    {
        $redirect = new Redirect();
        $redirect->setFrom('/my/old/slug')
            ->setTo('/my/new/slug')
            ->setLocale('da')
            ->setCode(301);
        try {
            $redirect = $this->redirectRepository->save($redirect);
        } catch (DuplicateEntryException $e) {
            $this->fail(sprintf('Failed saving the redirect (%s)', $e->getMessage()));
        } catch (\Exception $e) {
            $this->fail(sprintf('Failed saving the redirect (%s)', $e->getMessage()));
        }
        $this->assertNotEquals(0, $redirect->getID());

        $savedRedirect = $this->redirectRepository->getRedirectById($redirect->getID());

        $this->assertNotNull($savedRedirect);
        $this->assertSame($redirect->toArray(), $savedRedirect->toArray());
    }

    public function testCannotSaveRedirectsWithSameFrom()
    {
        $firstRedirect = new Redirect();
        $firstRedirect->setFrom('/my/old/slug')
            ->setTo('/my/first/destination')
            ->setLocale('da')
            ->setCode(301);

        $this->redirectRepository->save($firstRedirect);

        $secondRedirect = new Redirect();
        $secondRedirect->setFrom('/my/old/slug')
            ->setTo('/my/second/destination')
            ->setLocale('da')
            ->setCode(301);

        try {
            $this->redirectRepository->save($secondRedirect);
        } catch (DuplicateEntryException $exception) {
            $this->assertEquals(
                'Cannot create entry, due to key constraint \'from_hash_locale\'',
                $exception->getMessage()
            );
            return;
        }

        $this->fail('Failed catching DupicateEntryException');
    }

    public function testCanCreateMultipleRedirectsWithSameDestination()
    {
        foreach (range(1, 10) as $index) {
            $redirect = new Redirect();
            $redirect->setFrom('/from/old/slug/' . $index)
                ->setTo('/same/destination/slug')
                ->setLocale('da')
                ->setCode(301);
            $savedRedirect = $this->redirectRepository->save($redirect);
            $this->assertInstanceOf(Redirect::class, $savedRedirect);
            $this->assertGreaterThan(0, $savedRedirect->getID());
        }

        $this->assertEquals(10, $this->redirectRepository->countRows());
    }
}
