<?php

namespace Bonnier\WP\Redirect\Tests\integration\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;

class RedirectRepositoryTestCase extends TestCase
{
    /** @var RedirectRepository */
    protected $repository;

    public function setUp(bool $bootstrapRedirects = true)
    {
        parent::setUp();
        try {
            $this->repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
        }

        if ($bootstrapRedirects) {
            $this->bootstrapRedirects();
        }
    }

    protected function bootstrapRedirects()
    {
        $redirects = collect([
            [
                'from' => '/indretning/inspiration/null?auto=compress&ch=Width,DPR',
                'to' => '/indretning/inspiration'
            ],
            [
                'from' => '/image/898574/0/0/620?noredir=1',
                'to' => '/'
            ],
            [
                'from' => '/app/uploads/2018/06/untitled-1-riyxevc8fxnydvxmdsflxq.psd?auto=compress&fit=crop&min-w=600',
                'to' => '/'
            ],
            [
                'from' => '/article/186520-fa-bonbonniere-fra-lyngby-porcelaen-og-arets-julenummer-til-saerpris',
                'to' => '/indretning/jul'
            ],
            [
                'from' => '/gallery-filter/designme?popup=gallery',
                'to' => '/gallerier'
            ],
            [
                'from' => '/app/uploads/2018/05/rismark-i-vietnam_atlantis-rejser-ozzenttvdhupa0h_whhm3g.jpg',
                'to' => '/'
            ],
            [
                'from' => '/gallery-filter/852?popup=gallery',
                'to' => '/gallerier'
            ],
            [
                'from' => '/indretning/lamper/null?auto=compress&ch=Width,DPR',
                'to' => '/indretning/lamper'
            ],
            [
                'from' => '/tags/designnyhed?page=2',
                'to' => '/tags/design'
            ],
            [
                'from' => '/jul/null?auto=compress&ch=Width,DPR',
                'to' => '/jul'
            ],
            [
                'from' => '/gallery-filter/lænestol?popup=gallery',
                'to' => '/gallerier'
            ],
            [
                'from' => '/app/uploads/2018/06/untitled-1-4kuoatdmhbrdgkozkd3_jq.psd?auto=compress&fit=crop&min-w=600',
                'to' => '/',
            ],
            [
                'from' => '/magasinet/null?auto=compress&ch=Width,DPR',
                'to' => '/magasinet'
            ],
            [
                'from' => '/search?q=Morten Heiberg',
                'to' => '/?s=Morten Heiberg'
            ],
            [
                'from' => '/search?page=648&q=Morten Heiberg',
                'to' => '/?p=648&s=Morten Heiberg'
            ],
            [
                'from' => '/boliger/lejligheder/null?auto=compress&ch=Width,DPR',
                'to' => '/boliger/lejligheder'
            ],
            [
                'from' => '/tags/vitra?page=2',
                'to' => '/tags'
            ],
            [
                'from' => '/gallery-filter/royal copenhagen?popup=gallery',
                'to' => '/gallerier'
            ],
            [
                'from' => '/article/174492-fa-en-stelton-termokande-sammen-med-dit-abonnement',
                'to' => '/indretning/stelton'
            ],
            [
                'from' => '/design/null?auto=compress&ch=Width,DPR',
                'to' => '/design'
            ],
            [
                'from' => '/indretningstips?page=31&tag_id=39051',
                'to' => '/indretning'
            ],
            [
                'from' => '/contests/212?expire_cache=1&ignore_hidden=true',
                'to' => '/konkurrencer'
            ],
            [
                'from' => '/image/849809/0/0/620?noredir=1',
                'to' => '/'
            ],
            [
                'from' => '/gallery/overview/producenter?popup=gallery',
                'to' => '/gallerier'
            ],
            [
                'from' => '/opskrifter/desserter/opskrift-ristet-mandel-is---jordbaersauce-hertil',
                'to' => '/opskrifter/desserter'
            ],
            [
                'from' => '/gallery-filter/pia lund hansen?popup=gallery/',
                'to' => '/gallerier'
            ],
            [
                'from' => '/gallery-filter/troll% chair?popup=gallery',
                'to' => '/gallerier'
            ],
            [
                'from' => '/indretning/badevaerelse/null?auto=compress&ch=Width,DPR',
                'to' => '/indretning/badevaerelse'
            ],
            [
                'from' => '/app/uploads/2018/06/leader-285orfkvjytszzblxxjr-q.psd?auto=compress&fit=crop&min-w=600',
                'to' => '/'
            ],
            [
                'from' => '/app/uploads/2020*',
                'to' => '/'
            ],
            [
                'from' => '/old-sitemap/*',
                'to' => '/sitemap'
            ],
            [
                'from' => '/archive/2020/*',
                'to' => '/'
            ],
        ]);

        $redirects->each(function (array $url) {
            $this->createRedirect($url['from'], $url['to']);
        });

        $createdRedirects = $this->findAllRedirects();
        $this->assertCount(count($redirects), $createdRedirects);
        $createdRedirects->each(function (Redirect $redirect, int $index) use ($redirects) {
            $this->assertSame($redirects[$index]['from'], $redirect->getFrom());
            $this->assertSame($redirects[$index]['to'], $redirect->getTo());
        });
    }

    protected function createRedirect(
        string $source,
        string $destination,
        bool $keepQuery = false,
        string $locale = 'da',
        string $type = 'manual',
        int $code = 301
    ): ?Redirect {
        $redirect = new Redirect();
        $redirect->setFrom($source)
            ->setTo($destination)
            ->setKeepQuery($keepQuery)
            ->setType($type)
            ->setCode($code)
            ->setLocale($locale);


        return $this->saveRedirect($redirect);
    }

    protected function saveRedirect(Redirect &$redirect)
    {
        try {
            return $this->repository->save($redirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating redirect (%s)', $exception->getMessage()));
            return null;
        }
    }

    protected function findAllRedirects()
    {
        try {
            return $this->repository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting redirects (%s)', $exception->getMessage()));
            return null;
        }
    }

    protected function findRedirectByPath(string $path, string $locale = 'da')
    {
        try {
            return $this->repository->findRedirectByPath($path, $locale);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirect (%s)', $exception->getMessage()));
            return null;
        }
    }
}
