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

        try {
            $this->redirectRepository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
        }
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
        $redirect = $this->save($redirect);
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

        $this->save($firstRedirect);

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
        } catch (\Exception $exception) {
            $this->fail(sprintf('Saving redirect threw unexpected exception (%s)', $exception->getMessage()));
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
            $savedRedirect = $this->save($redirect);
            $this->assertInstanceOf(Redirect::class, $savedRedirect);
            $this->assertGreaterThan(0, $savedRedirect->getID());
        }

        try {
            $rowCount = $this->redirectRepository->countRows();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed counting rows (%s)', $exception->getMessage()));
            return;
        }
        $this->assertEquals(10, $rowCount);
    }

    /**
     * @dataProvider addingQueryParamsProvider
     *
     * @param string $destination
     * @param string $query
     * @param string $expected
     */
    public function testCanAddQueryParamsCorrectly(string $destination, string $query, string $expected)
    {
        $redirect = new Redirect();
        $redirect->setTo($destination)
            ->setKeepQuery(true)
            ->addQuery($query);

        $this->assertSame($expected, $redirect->getTo());
    }

    public function testCannotAddQueryParamsWhenKeepQueryIsFalse()
    {
        $redirect = new Redirect();
        $redirect->setTo('/path/to/destination')
            ->setKeepQuery(false)
            ->addQuery('a=b&c=d&e=f');

        $this->assertSame('/path/to/destination', $redirect->getTo());
    }

    public function addingQueryParamsProvider()
    {
        return [
            'Simple query' => ['/destination', 'a=b', '/destination?a=b'],
            'Sorts query' => ['/destination', 'c=d&a=b', '/destination?a=b&c=d'],
            'Merges query' => ['/destination?a=b', 'c=d', '/destination?a=b&c=d'],
            'Prefers query from original' => ['/destination?a=b', 'a=c', '/destination?a=b'],
            'Works with full url' => ['/destination', 'http://example.com/slug/?a=b', '/destination?a=b'],
            'Works with path url' => ['/destination', '/slug/?a=b', '/destination?a=b'],
            'Works with query containing ?' => ['/destination', '?a=b', '/destination?a=b'],
            'Works with destination being a full url' => [
                'https://example.com/path/to/slug',
                'a=b&c=d',
                'https://example.com/path/to/slug?a=b&c=d'
            ]
        ];
    }

    private function save(Redirect &$redirect)
    {
        try {
            return $this->redirectRepository->save($redirect);
        } catch (\Exception $e) {
            $this->fail(sprintf('Failed saving the redirect (%s)', $e->getMessage()));
            return null;
        }
    }
}
