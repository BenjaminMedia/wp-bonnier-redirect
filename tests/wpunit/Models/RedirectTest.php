<?php

namespace Bonnier\WP\Redirect\Tests\Wpunit\Models;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Http\Request;
use Bonnier\WP\Redirect\Models\Redirect;

class RedirectTest extends \Codeception\TestCase\WPTestCase
{
    public function testCanSaveRedirect()
    {
        $redirect = new Redirect();
        $redirect->setFrom('/my/old/slug')
            ->setTo('/my/new/slug')
            ->setLocale('da')
            ->setCode(Request::HTTP_PERMANENT_REDIRECT)
            ->save();

        $this->assertNotEquals(0, $redirect->getID());

        $savedRedirect = new Redirect($redirect->getID());

        $this->assertSame($redirect->toArray(), $savedRedirect->toArray());
    }

    public function testCannotSaveRedirectsWithSameFrom()
    {
        $firstRedirect = new Redirect();
        $firstRedirect->setFrom('/my/old/slug')
            ->setTo('/my/first/destination')
            ->setLocale('da')
            ->setCode(Request::HTTP_PERMANENT_REDIRECT)
            ->save();

        $secondRedirect = new Redirect();
        $secondRedirect->setFrom('/my/old/slug')
            ->setTo('/my/second/destination')
            ->setLocale('da')
            ->setCode(Request::HTTP_PERMANENT_REDIRECT);

        try {
            $secondRedirect->save();
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
                ->setCode(Request::HTTP_PERMANENT_REDIRECT)
                ->save();
        }

        $this->assertEquals(10, DB::countRedirects());
    }
}
