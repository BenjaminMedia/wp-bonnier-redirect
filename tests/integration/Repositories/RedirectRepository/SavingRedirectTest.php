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

        try {
            $this->repository->save($redirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed saving the redirect (%s)', $exception->getMessage()));
            return;
        }
        $this->assertGreaterThan(0,$redirect->getID());
        try {
            $redirects = $this->repository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting redirects (%s)', $exception->getMessage()));
            return;
        }
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
            try {
                $redirects = $this->repository->findAll();
            } catch (\Exception $exception) {
                $this->fail(sprintf('Failed getting redirects (%s)', $exception->getMessage()));
                return;
            }
            $this->assertEmpty($redirects);
            return;
        }
        $this->fail('Failed throwing IdenticalFromToException!');
    }

    public function testRemovingChainsDoesNotCreateLoops()
    {
        $oldRedirect = $this->createRedirect('/from/a', '/to/b');
        try {
            $createdRedirects = $this->repository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting redirects (%s)', $exception->getMessage()));
            return;
        }
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

        try {
            $redirects = $this->repository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting redirects (%s)', $exception->getMessage()));
            return;
        }

        $this->assertCount(1, $redirects);
        $this->assertSameRedirects($newRedirect, $redirects->first());
    }
}
