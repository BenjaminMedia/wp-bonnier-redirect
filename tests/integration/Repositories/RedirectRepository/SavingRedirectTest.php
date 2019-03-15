<?php

namespace Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepository;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Migrations\Migrate;
use Bonnier\WP\Redirect\Exceptions\IdenticalFromToException;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Tests\integration\Repositories\RedirectRepositoryTestCase;

class SavingRedirectTest extends RedirectRepositoryTestCase
{
    public function setUp(bool $bootstrapRedirects = false)
    {
        parent::setUp($bootstrapRedirects);
    }

    public function testCanSaveARedirect()
    {
        $redirect = new Redirect();
        $redirect->setFrom('/from/slug')
            ->setTo('/to/slug')
            ->setKeepQuery(false)
            ->setType('test')
            ->setCode(301)
            ->setLocale('da');

        $this->saveRedirect($redirect);

        $this->assertGreaterThan(0, $redirect->getID());
        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertSameRedirects($redirect, $redirects->first());
    }

    public function testCannotSaveRedirectWhereFromAndToAreIdentical()
    {
        $redirect = new Redirect();
        $redirect->setFrom('/from/slug')
            ->setTo('/from/slug')
            ->setKeepQuery(false)
            ->setType('test')
            ->setCode(301)
            ->setLocale('da');

        try {
            $this->repository->save($redirect);
        } catch (IdenticalFromToException $exception) {
            $this->assertSame('A redirect with the same from and to, cannot be created!', $exception->getMessage());
            $this->assertEmpty($this->findAllRedirects());
            return;
        } catch (\Exception $exception) {
            $this->fail(sprintf('Saving redirect threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->fail('Failed throwing IdenticalFromToException!');
    }

    public function testRemovingChainsDoesNotCreateLoops()
    {
        $oldRedirect = $this->createRedirect('/from/a', '/to/b');
        $createdRedirects = $this->findAllRedirects();
        $this->assertCount(1, $createdRedirects);
        $this->assertSameRedirects($oldRedirect, $createdRedirects->first());

        $newRedirect = new Redirect();
        $newRedirect->setFrom('/to/b')
            ->setTo('/from/a')
            ->setType('test')
            ->setCode(301)
            ->setLocale('da');

        $database = new DB();
        $database->setTable(Migrate::REDIRECTS_TABLE);
        try {
            if ($redirectID = $database->insert($newRedirect->toArray())) {
                $newRedirect->setID($redirectID);
            }
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed saving redirect (%s)', $exception->getMessage()));
            return;
        }


        try {
            $this->repository->removeChainsByRedirect($newRedirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed removing redirect chains (%s)', $exception->getMessage()));
            return;
        }

        $redirects = $this->findAllRedirects();

        $this->assertCount(1, $redirects);
        $this->assertSameRedirects($newRedirect, $redirects->first());
    }
}
