<?php

namespace Bonnier\WP\Redirect\Tests\Integration\Models;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;

class RedirectTest extends TestCase
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

    public function testPrefersHashesFromDB()
    {
        $redirect = new Redirect();
        $redirect->fromArray([
            'id' => 1,
            'from' => '/from/a/slug',
            'from_hash' => 'dbmd5fromhash',
            'to' => '/to/a/slug',
            'to_hash' => 'dbmd5tohash',
            'locale' => 'da',
            'code' => 301,
            'paramless_from_hash' => 'dbmd5paramlessfromhash',
        ]);

        $this->assertSame('dbmd5fromhash', $redirect->getFromHash());
        $this->assertSame('dbmd5tohash', $redirect->getToHash());
        $this->assertSame('dbmd5paramlessfromhash', $redirect->getParamlessFromHash());
    }

    public function testGeneratesOwnHashesWhenMissing()
    {
        $redirect = new Redirect();
        $redirect->fromArray([
            'id' => 1,
            'from' => '/from/a/slug',
            'to' => '/to/a/slug',
            'locale' => 'da',
            'code' => 301,
        ]);

        $fromHash = hash('md5', '/from/a/slug');
        $toHash = hash('md5', '/to/a/slug');
        $paramlessHash = hash('md5', '/from/a/slug');

        $this->assertSame($fromHash, $redirect->getFromHash());
        $this->assertSame($toHash, $redirect->getToHash());
        $this->assertSame($paramlessHash, $redirect->getParamlessFromHash());
    }

    public function testGeneratesParamlessHashCorrect()
    {
        $fromWithoutParams = '/path/from/slug';
        $fromWithParams = $fromWithoutParams . '?with=params';

        $redirect = new Redirect();
        $redirect->fromArray([
            'id' => 1,
            'from' => $fromWithParams,
            'to' => '/path/to/slug',
            'locale' => 'da',
            'code' => 301,
        ]);

        $fromHash = hash('md5', $fromWithParams);
        $paramlessFromHash = hash('md5', $fromWithoutParams);

        $this->assertSame($fromHash, $redirect->getFromHash());
        $this->assertSame($paramlessFromHash, $redirect->getParamlessFromHash());
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
