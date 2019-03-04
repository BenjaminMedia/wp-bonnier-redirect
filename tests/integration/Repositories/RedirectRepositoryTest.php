<?php

namespace Bonnier\WP\Redirect\Tests\integration\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\Tests\integration\TestCase;

class RedirectRepositoryTest extends TestCase
{
    /** @var RedirectRepository */
    private $repository;

    public function setUp()
    {
        parent::setUp();
        try {
            $this->repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
        }
        $this->bootstrapRedirects();
    }

    public function testCanFindSimpleRedirect()
    {
        $redirect = $this->createRedirect('/path/to/old/article', '/path/to/new/article');

        $foundRedirect = $this->repository->findRedirectByPath('/path/to/old/article', 'da');

        $this->assertInstanceOf(Redirect::class, $foundRedirect);
        $this->assertSameRedirects($redirect, $foundRedirect);
    }

    /**
     * @dataProvider malformedPathProvider
     *
     * @param string $path
     * @param string $from
     */
    public function testCanFindRedirectWithMalformedPath(string $path, string $from)
    {
        $redirect = $this->createRedirect($from, '/destination');

        $foundRedirect = $this->repository->findRedirectByPath($path, 'da');

        $this->assertInstanceOf(
            Redirect::class,
            $foundRedirect,
            sprintf('Could not find redirect where path was \'%s\'', $path)
        );
        $this->assertSameRedirects($redirect, $foundRedirect);
    }

    /**
     * @dataProvider wildcardRedirectProvider
     *
     * @param string $path
     * @param string $from
     */
    public function testCanFindWildcardRedirect(string $path, string $from)
    {
        $redirect = $this->createRedirect($from, '/destination');

        $this->assertTrue($redirect->isWildcard(), 'The created redirect wasn\'t a wildcard redirect!');

        $foundRedirect = $this->repository->findRedirectByPath($path, 'da');

        $this->assertInstanceOf(
            Redirect::class,
            $foundRedirect,
            sprintf('Could not find redirect where path was \'%s\'', $path)
        );

        $this->assertSameRedirects($redirect, $foundRedirect);
    }

    public function testPrefersExactRedirectMatchInsteadOfWildcardRedirect()
    {
        $wildcard = $this->createRedirect('/from/wildcard/*', '/destination');
        $notWildcard = $this->createRedirect('/from/wildcard/exact', '/destination');

        $createdRedirects = $this->repository->findAll()->take(-2);

        $this->assertSameRedirects($wildcard, $createdRedirects->first());
        $this->assertSameRedirects($notWildcard, $createdRedirects->last());

        $foundRedirect = $this->repository->findRedirectByPath('from/wildcard/exact/', 'da');

        $this->assertInstanceOf(Redirect::class, $foundRedirect);

        $this->assertSameRedirects($notWildcard, $foundRedirect);
    }

    public function testPrefersWildcardIfNoExactMatchExists()
    {
        $wildcard = $this->createRedirect('/from/wildcard/*', '/destination');
        $notWildcard = $this->createRedirect('/from/wildcard/exact', '/destination');

        $createdRedirects = $this->repository->findAll()->take(-2);

        $this->assertSameRedirects($wildcard, $createdRedirects->first());
        $this->assertSameRedirects($notWildcard, $createdRedirects->last());

        $foundRedirect = $this->repository->findRedirectByPath('from/wildcard/exact/path/', 'da');

        $this->assertInstanceOf(Redirect::class, $foundRedirect);

        $this->assertSameRedirects($wildcard, $foundRedirect);
    }

    private function bootstrapRedirects()
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
                'from' => '/opskrifter/desserter/opskrift-ristet-mandel-is-–-jordbaersauce-hertil',
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

        $createdRedirects = $this->repository->findAll();
        $this->assertCount(count($redirects), $createdRedirects);
        $createdRedirects->each(function (Redirect $redirect, int $index) use ($redirects) {
            $this->assertSame($redirects[$index]['from'], $redirect->getFrom());
            $this->assertSame($redirects[$index]['to'], $redirect->getTo());
        });
    }

    private function createRedirect(
        string $source,
        string $destination,
        string $locale = 'da',
        string $type = 'manual',
        int $code = 301
    ): ?Redirect {
        $redirect = new Redirect();
        $redirect->setFrom($source)
            ->setTo($destination)
            ->setType($type)
            ->setCode($code)
            ->setLocale($locale);

        try {
            return $this->repository->save($redirect);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating redirect (%s)', $exception->getMessage()));
        }
        return null;
    }

    public function malformedPathProvider()
    {
        return [
            'Url-encoded path' => ['/path/which%20is%20urlencoded/', '/path/which is urlencoded'],
            'Unordered query params' => ['/path/?params=out-of&order=they-are', '/path?order=they-are&params=out-of'],
            'Mixed case path' => ['/Path/With/Mixed/Case', '/path/with/mixed/case'],
            'Inproper slash use' => ['path/with/invalid/slashes///', '/path/with/invalid/slashes'],
            'URL with domain' => ['https://wp.test/path/to/article', '/path/to/article'],
        ];
    }

    public function wildcardRedirectProvider()
    {
        return [
            'Polopoly redirects' => ['/polopoly.jsp?id=1234&gcid=abc123', '/polopoly.jsp*'],
            'Archive redirects' => ['/archive/my-article', '/archive*'],
            'Archive redirects with slash' => ['/archive/my-article', '/archive/*'],
        ];
    }
}
