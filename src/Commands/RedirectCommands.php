<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Database\Migrations\Migrate;
use Bonnier\WP\Redirect\Exceptions\IdenticalFromToException;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use cli\progress\Bar;
use function WP_CLI\Utils\make_progress_bar;

class RedirectCommands extends \WP_CLI_Command
{
    /**
     * Normalizes all from and to urls in the redirect table
     *
     * ## EXAMPLES
     *     wp bonnier redirect redirects normalize
     *
     * @throws \WP_CLI\ExitException
     */
    public function normalize()
    {
        $database = new DB();
        $database->setTable(Migrate::REDIRECTS_TABLE);
        try {
            $query = $database->query()->select('*');
            $results = collect($database->getResults($query));
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed getting redirects from database (%s)', $exception->getMessage()));
            return;
        }
        if ($results->isNotEmpty()) {
            /** @var Bar $progress */
            $progress = make_progress_bar(
                sprintf('Normalizing %s redirects', number_format($results->count())),
                $results->count()
            );
            $results->each(function (array $redirect) use ($database, $progress) {
                $redirectID = $redirect['id'];
                unset($redirect['id']);
                $redirect = $this->normalizeRedirect($redirect);
                if ($this->isRedirectInvalid($redirect)) {
                    $database->delete($redirectID);
                } else {
                    try {
                        $database->update($redirectID, $redirect);
                    } catch (DuplicateEntryException $exception) {
                        $database->delete($redirectID);
                    } catch (\Exception $exception) {
                        \WP_CLI::warning(sprintf('Failed updating redirect \'%s\'', $redirectID));
                    }
                }
                $progress->tick();
            });
            $progress->finish();
        }
    }

    /**
     * Removes all chains in the redirect table
     *
     * ## EXAMPLES
     *     wp bonnier redirect redirects unchain
     *
     * @throws \WP_CLI\ExitException
     */
    public function unchain()
    {
        try {
            $repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
            return;
        }
        \WP_CLI::line('Retrieving redirects...');
        try {
            $redirects = $repository->findAll();
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }
        $redirectCount = $redirects->count();
        /** @var Bar $progress */
        $progress = make_progress_bar(
            sprintf('Unchaining %s redirects', number_format($redirectCount)),
            $redirectCount
        );
        $redirects->each(function (Redirect $redirect) use ($repository, $progress) {
            try {
                $repository->save($redirect);
            } catch (IdenticalFromToException $exception) {
                $repository->delete($redirect);
            } catch (\Exception $exception) {
                \WP_CLI::warning(sprintf('Failed updating %s (%s)', $redirect->getID(), $exception->getMessage()));
            }
            $progress->tick();
        });
        $progress->finish();
    }

    private function isRedirectInvalid(array $redirect)
    {
        return ($redirect['from'] === '' ||
            $redirect['from'] === '/' ||
            $redirect['to'] === '' ||
            !in_array($redirect['locale'], LocaleHelper::getLanguages())
        );
    }

    private function normalizeRedirect(array $redirect)
    {
        $redirect['from'] = UrlHelper::normalizePath($redirect['from']);
        $redirect['from_hash'] = hash('md5', $redirect['from']);
        $redirect['paramless_from_hash'] = hash('md5', parse_url($redirect['from'], PHP_URL_PATH));
        $redirect['to'] = UrlHelper::normalizeUrl($redirect['to']);
        $redirect['to_hash'] = hash('md5', $redirect['to']);
        return $redirect;
    }

    //private function getActiveCategory

    private function findNearestCategory($path, $language)
    {
        $categoriesFromPath = explode('/', $path);
        //var_dump($categoriesFromPath);

        $i = 0;
        while ($categoryFromPath = array_pop($categoriesFromPath)) {
            echo $categoryFromPath . PHP_EOL;

            echo 'Find category: ' . $categoryFromPath . PHP_EOL;
            $terms = get_terms([
                'taxonomy' => 'category',
                'name' => $categoryFromPath,
                'hide_empty' => false,
                'lang' => $language
            ]);

            if (isset($terms[0])) {
                echo 'Found: ' . get_category_link($terms[0]) . PHP_EOL;
                return get_category_link($terms[0]);
            }
        }

        exit;

        if ($url == '/vaerktoej/havemaskiner/motorsav/test-af-kaedesave-paa-batteri') {
            return '/el-save';
        }
        if ($url == '/vaerktoej/el-save') {
            return '/el-save';
        }
        return null;
    }

    public function checkRedirects0()
    {
        try {
            $repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
            return;
        }
        \WP_CLI::line('Retrieving redirects...');
        try {
            //$redirects = $repository->findAll();
            //$redirects = $repository::latest()->take(10)->get();
            $redirects = $repository->findAllBy('wp_id', 84728);    //75912
            //$redirects = $repository->findRedirectByPath('/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor-0', 'da');
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }
        //var_dump($redirects);

        $redirectCount = $redirects->count();
        echo 'Total redirects: ' . $redirectCount . PHP_EOL;

        foreach ($redirects as $redirect) {
            $action = null;

            echo PHP_EOL;
            echo 'Redirect id: ' . $redirect->getId() . PHP_EOL;
            echo 'To: ' . $redirect->getTo() . PHP_EOL;

            $wpId = $redirect->getWpID();
            echo 'WpId: ' . $wpId . PHP_EOL;
            echo 'language: ' . $redirect->getLocale() . PHP_EOL;

            if ($wpId) {
                echo '** wp_id found' . PHP_EOL;
                $path_from_wp = preg_replace('#^https?://[^/]+#', '', get_permalink($wpId));
                if ($path_from_wp) {
                    echo '** get_permalink returns data' . PHP_EOL;
                    if ($redirect->getTo() != $path_from_wp) {
                        echo 'Update redirect: to = ' . $path_from_wp . PHP_EOL;
                        $action = 'Update to';
                    }
                }
                else {
                    echo '** get_permalink no data' . PHP_EOL;
                    $nearestCategory = $this->findNearestCategory($redirect->getTo(), $redirect->getLocale());
                    if ($nearestCategory) {
                        echo '** nearest category returns data: ' . $nearestCategory . PHP_EOL;
                        echo 'Update redirect: to = ' . $nearestCategory . PHP_EOL;
                        $action = 'Update to';
                    }
                    else {
                        echo '** nearest category no data' . PHP_EOL;
                        echo 'Update redirect: to = /' . PHP_EOL;
                        $action = 'Update to';
                    }
                }
            }

            if (!$action && $redirect->getToHash() != md5($redirect->getTo())) {
                echo 'Update redirect: to_hash = ' . md5($redirect->getTo()) . PHP_EOL;
            }

        }
        exit;

        $i = 0;
        $count_no_wp_id = 0;
        $count_no_wp_id_and_wrong_to_hash = 0;
        $count_wrong_to = 0;
        $count_correct_to_but_wrong_to_hash = 0;
        $count_both_correct = 0;
        foreach ($redirects as $redirect) {
            $i++;

            /*
            if ($i>10) {
                exit;
            }
            */

            $to_ok = true;
            $to_hash_ok = true;

            $wpId = $redirect->getWpID();
            echo 'WpId: ' . $wpId . PHP_EOL;

            if (!$wpId) {
                echo 'WpId empty' . PHP_EOL;
            }

            if ($wpId) {
                $path_from_wp = preg_replace('#^https?://[^/]+#', '', get_permalink($wpId));
                echo 'WP: ' . $path_from_wp . PHP_EOL;
                echo 'DB: ' . $redirect->getTo() . PHP_EOL;

                if ($redirect->getTo() != $path_from_wp) {
                    $to_ok = false;
                    echo 'Wrong to' . PHP_EOL;
                }
                else {
                    echo 'Correct to' . PHP_EOL;
                }
            }

            if ($redirect->getToHash() != md5($redirect->getTo())) {
                $to_hash_ok = false;
                echo 'Wrong to_hash' . PHP_EOL;
            }
            else {
                echo 'Correct to_hash' . PHP_EOL;
            }

            if (!$wpId && !$to_hash_ok) {
                $count_no_wp_id_and_wrong_to_hash++;
                echo 'Action: Updating to_hash' . PHP_EOL;
            }
            else if (!$wpId) {
                $count_no_wp_id++;
                echo 'Action: No action' . PHP_EOL;
            }
            else if (!$to_ok) {
                $count_wrong_to++;
                echo 'Action: Updating to' . PHP_EOL;
            }
            else if ($to_ok && !$to_hash_ok) {
                $count_correct_to_but_wrong_to_hash++;
                echo 'Action: Updating to_hash' . PHP_EOL;
            }
            else if ($to_ok && $to_hash_ok) {
                $count_both_correct++;
                echo 'Action: No action' . PHP_EOL;
            }
            else {
                echo 'ERROR' . PHP_EOL;
                exit;
            }

            echo PHP_EOL;
        }

        echo PHP_EOL;
        echo 'Count no_wp_id and wrong_to_hash   (Update to_hash): ' . $count_no_wp_id_and_wrong_to_hash . PHP_EOL;
        echo 'Count no wp id                     (No action):      ' . $count_no_wp_id . PHP_EOL;
        echo 'Count wrong to                     (Update to):      ' . $count_wrong_to . PHP_EOL;
        echo 'Count both correct:                (No action):      ' . $count_both_correct . PHP_EOL;
        echo 'Count correct to but wrong to_hash (Update to_hash): ' . $count_correct_to_but_wrong_to_hash . PHP_EOL;


        /** @var Bar $progress */
        /*
        $progress = make_progress_bar(
            sprintf('Checking %s redirects', number_format($redirectCount)),
            $redirectCount
        );
        */
    }

    private function getHttpCode($url)
    {
        $headers = get_headers($url);
        foreach ($headers as $line) {
            if (preg_match('#^HTTP/.*(\d{3})#', $line, $res)) {
                return $res[1];
            }
        }
    }

    private function finalCode($url, $count = 0)
    {
        if ($count > 10) {
            return 404;
        }
        echo 'Final code check: ' . $url . PHP_EOL;
        $parseUrl = parse_url($url);
        $domain = $parseUrl['scheme'] . '://' . $parseUrl['host'];
        $headers = get_headers($url);
        $code = null;
        $location = null;
        foreach ($headers as $line) {
            if (preg_match('#^HTTP/.*(\d{3})#', $line, $res)) {
                $code = $res[1];
            }
            if (preg_match('#^Location: (.+)#', $line, $res)) {
                $location = $res[1];
            }
            if (in_array($code, [301, 302]) && $location) {
                return $this->finalCode($domain . $location, $count++);
            }
        }
        return $code;
    }

    private function deleteRedirect($redirect, $reason)
    {
        echo 'ACTION:      Delete redirect' . PHP_EOL;
        if ($reason) {
            echo 'REASON:  ' . $reason . PHP_EOL;
        }
        return 'delete';
    }

    private function updateTo($redirect, $reason)
    {
        echo 'ACTION:      Update to' . PHP_EOL;
        if ($reason) {
            echo 'REASON:  ' . $reason . PHP_EOL;
        }
        return 'update to';
    }

    private function updateToHash($redirect, $reason)
    {
        echo 'ACTION:      Update to_hash' . PHP_EOL;
        if ($reason) {
            echo 'REASON:  ' . $reason . PHP_EOL;
        }
        return 'update to_hash';
    }

    private function checkRedirect($redirect)
    {
        echo PHP_EOL;
        echo 'Redirect id: ' . $redirect->getId() . PHP_EOL;
        echo 'From:        ' . $redirect->getFrom() . PHP_EOL;
        echo 'To:          ' . $redirect->getTo() . PHP_EOL;
        echo 'language:    ' . $redirect->getLocale() . PHP_EOL;
        echo 'Type:        ' . $redirect->getType() . PHP_EOL;
        echo 'Wp_id:       ' . $redirect->getWpId() . PHP_EOL;

        // Delete redirect if in language not used on site
        if (!in_array($redirect->getLocale(), pll_languages_list())) {
            echo 'WRONG language' . PHP_EOL;
            return $this->deleteRedirect($redirect, 'Invalid language');
        }

        $homeUrl = pll_home_url($redirect->getLocale());
        $homeUrl = rtrim($homeUrl, '/');
        $homeUrl = preg_replace('#^http:#', 'https:', $homeUrl);
        $fromUrl = $homeUrl . $redirect->getFrom();
        $toUrl = $homeUrl . $redirect->getTo();

        $postLink = null;
        $postStatus = null;
        if (in_array($redirect->getType(), ['post-slug-change','Google Index Fix'])) {
            $postLink = get_permalink($redirect->getWpId()); // TODO kan der komme noget forkert her når det ikke er en post
            $postLink = preg_replace('#^http:#', 'https:', $postLink);
            $postStatus = get_post_status($redirect->getWpId());
        }

        echo 'From url:    ' . $fromUrl . PHP_EOL;
        echo 'To url:      ' . $toUrl . PHP_EOL;
        echo 'PostLink:    ' . $postLink . PHP_EOL;
        echo 'PostStatus:  ' . $postStatus . PHP_EOL;

        $toCategoryLink = null;
        $toCategory = get_category_by_path($redirect->getTo());
        if ($toCategory) {
            $toCategoryLink = get_category_link($toCategory);
            $toCategoryLink = preg_replace('#^http:#', 'https:', $toCategoryLink);
            echo 'To category: ' . $toCategoryLink . PHP_EOL;
        }


        // ****** Checks and return


        // Check for external redirect, e.g.
        // https://abonnement.goerdetselv.dk/campaign/fa-3-nr-af-gor-det-selv?media=Egenannoncer
        if (substr($redirect->getTo(), 0,4) == 'http') {
            echo 'OK           (external redirect)' . PHP_EOL;
            return 'ok external redirect';
        }

        // Permalink is not null: If permalink and fromUrl are equal, then no redirect is necessary
        // Maybe also check for 200
        if ($postLink && $postLink == $fromUrl) {
            return $this->deleteRedirect($redirect, 'From url gives 200');
        }

        if ($postLink && $postLink == $toUrl) {
            echo 'OK           (redirect to wp_ids to-url ok)' . PHP_EOL;
            return 'ok redirect wp_id post';
        }

        // Permalink is not null: If permalink and toUrl are different, then to should be updated
        // 119121 - Should be: OK
        /*
        if ($postLink && $postLink != $toUrl) {
            $this->updateTo($redirect);
            return;
        }
        */

        // CategoryLink is not null: If categoryLink and toUrl are equal, then the redirect is OK
        if ($toCategoryLink && $toCategoryLink == $toUrl) {
            echo 'OK           (redirect to category ok)' . PHP_EOL;
            return 'ok redirect to category';
        }

        // Try to get page by last path of path
        // If path is: /vaerktoej/havemaskiner/motorsav/test-af-kaedesave-paa-batter
        // then get page by: test-af-kaedesave-paa-batter
        $toPageByPathLink = null;
        $toPageByPath = get_page_by_path(basename( untrailingslashit($redirect->getTo())), OBJECT, 'contenthub_composite');
        // TODO kan der komme forkert domæne her, når samme slug på flere sprog
        if ($toPageByPath && $toPageByPath->post_type != 'contenthub_composite') {
            $toPageByPath = null;
        }
        // Set $toPageByPath to null if it is in incorrect language
        if ($toPageByPath && !pll_get_post($toPageByPath->ID, $redirect->getLocale())) {
            $toPageByPath = null;
        }
        if ($toPageByPath) {
            $toPageByPathLink = get_permalink($toPageByPath);
            $toPageByPathLink = preg_replace('#^http:#', 'https:', $toPageByPathLink);
        }
        echo 'toPageByPathLink: ' . $toPageByPathLink . PHP_EOL;

        if (/*$redirect->getType() == 'manual' && */$toPageByPathLink && $toPageByPathLink == $toUrl) {
            echo 'OK           (to page has correct url)' . PHP_EOL;
            return 'ok to page correct';
        }

        if (/*$redirect->getType() == 'manual' && */$toPageByPathLink && $toPageByPathLink != $toUrl) {
            return $this->updateTo($redirect, 'Post has different url now');
        }

        // Check to http code
        $toCode = $this->getHttpCode($toUrl);
        //$finalToCode = $this->finalCode($toUrl);
        echo 'To code:     ' . $toCode . PHP_EOL;
        //echo 'Final code: ' . $finalToCode . PHP_EOL;

        if ($toCode == 200) {
            echo 'OK           (200)' . PHP_EOL;
            return 'ok 200';
        }

        if ($toCode == 301 || $toCode == 302) {
            echo 'OK           (301 302 redirect chain)' . PHP_EOL;
            return 'ok redirect 301 302'; // Redirect chains are fixed elsewhere
        }

        if ($toCode == 404) {
            echo '*** 404 ***' . PHP_EOL;
            return $this->deleteRedirect($redirect, 'To url gives 404');
        }

        echo '*** ' . $toCode . ' ***' . PHP_EOL;
        exit;



        echo 'ACTION: delete (probably)' . PHP_EOL;
        return $this->deleteRedirect($redirect);

        //https://api.goerdetselv.dk/wp-json/app/resolve?path=/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor

        //da local
        //http://api.gds.test/wp-json/app/resolve?path=/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor
    }

    public function checkRedirects()
    {
        /*
        $tmp = get_category_by_path('/vaerktoej/maalevaerktoej/vaterpas/');
        var_dump($tmp);exit;

        $tmp = get_page_by_path(basename( untrailingslashit('/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor')), OBJECT, 'contenthub_composite');
        var_dump($tmp);exit;
        */

        try {
            $repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
            return;
        }
        \WP_CLI::line('Retrieving redirects...');
        try {
            $redirects = $repository->findAll();
            //$redirects = $repository::latest()->take(10)->get();
            //$redirects = $repository->findAllBy('wp_id', 84728);    // 75912
            //$redirects = $repository->getRedirectById(119121); //577018
            //$redirects = $repository->findRedirectByPath('/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor-0', 'da');
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }



        /*
        $this->checkRedirect($redirects);
        exit;
        */




        /*
        $tmp = get_category_by_path('');
        var_dump($tmp);exit;
        var_dump($redirects);exit;
        */
        $redirectCount = $redirects->count();
        echo 'Total redirects: ' . $redirectCount . PHP_EOL;

        foreach ($redirects as $redirect) {
            // TODO Skip certains types, remove later
            if (in_array($redirect->getType(), ['drupal', 'drupal-nodes'])) {
                continue;
            }
            $this->checkRedirect($redirect);
        }
    }

    public function redirectTests()
    {
        /*
        $toPageByPath = get_page_by_path('test-av-ryobi-rtms-1800-kombisag', OBJECT, 'contenthub_composite');
        var_dump($toPageByPath);
        exit;
        */

        //var_dump(get_permalink(84094));
        //exit;

        //var_dump($this->pll_get_page_url('terrasse-2', 'nb'));exit;

        /*
        var_dump(get_page_by_path('solseil-til-lavpris', OBJECT, 'contenthub_composite'));
        echo PHP_EOL;
        var_dump(get_permalink(get_page_by_path('terrasse-2', OBJECT, 'contenthub_composite')->ID));
        exit;
        */

        try {
            $repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
            return;
        }
        \WP_CLI::line('Testing redirects');

        $tests = [
            119121 => 'ok redirect to category',
            490825 => 'ok to page correct',      /* da, manual, wp_id ?, post-link (tom), to-page-by-path korrekt */
            488058 => 'delete',                  /* da, post-slug-change, wp_id: nav-menu-item, to-page-by-path (tom) */
            124310 => 'ok redirect wp_id post',  /* sv, google index fix, wp_id: post */
            124306 => 'ok redirect wp_id post',  /* sv, google index fix, wp_id: post */
            165268 => 'delete',
            210 => 'ok redirect 301 302',
            101544 => 'ok external redirect',
            271919 => 'delete',
            176889 => 'delete'
        ];

        foreach ($tests as $id => $expected) {
            echo PHP_EOL;
            echo 'Redirect id: ' . $id . ' - ';
            $redirect = $repository->getRedirectById($id);
            $result = $this->checkRedirect($redirect);
            if ($result == $expected) {
                echo 'OK' . PHP_EOL;
                continue;
            }
            echo 'ERROR' . PHP_EOL;
            echo 'Expected: ' . $expected . PHP_EOL;
            echo 'Result:   ' . $result . PHP_EOL;
        }
    }
}
