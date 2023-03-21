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
use Bonnier\WP\Redirect\Commands\Fix;

class FixRedirectsCommands extends \WP_CLI_Command
{
    const CACHE_ENABLED = true;
    private $data;

    public function __construct()
    {
        $this->data = (object)array();
        $this->readDataFile();
    }

    private function readDataFile()
    {
        if (!self::CACHE_ENABLED) {
            return;
        }
        if (file_exists('data.json')) {
            $this->data = json_decode(file_get_contents('data.json'));
        }
    }

    private function readData($url, $field)
    {
        if (!self::CACHE_ENABLED) {
            return null;
        }
        $url = str_replace('https://', '', $url);
        /*
        if (isset($this->data->{$url}->{$field})) {
            echo ' **** USED CACHED:   ' . $this->data->{$url}->{$field} . ' **** ' . PHP_EOL;
        }
        */
        return $this->data->{$url}->{$field} ?? null;
    }

    private function writeData($url, $field, $value)
    {
        if (!self::CACHE_ENABLED) {
            return;
        }
        $url = str_replace('https://', '', $url);
        //$this->data->{$url}->{$field} = $value;
        //$this->data[$url][$field] = $value;
        if (!isset($this->data->{$url})) {
            $this->data->{$url} = (object)array();
        }
        $this->data->{$url}->{$field} = $value;
        file_put_contents('data.json', json_encode($this->data));
    }

    private function getHttpCode($url)
    {
        $cachedValue = $this->readData($url, 'code');
        if ($cachedValue) {
            return $cachedValue;
        }
        $headers = get_headers($url);
        foreach ($headers as $line) {
            if (preg_match('#^HTTP/.*(\d{3})#', $line, $res)) {
                $this->writeData($url, 'code', $res[1]);
                return $res[1];
            }
        }
        return null;
    }

    private function deleteRedirect($redirect)
    {
        //echo 'ACTION:      Delete redirect' . PHP_EOL;
        return 'delete';
    }

    private function updateTo($redirect, $newTo)
    {
        //echo 'ACTION:      Update to' . PHP_EOL;
        return 'update to';
    }

    private function updateToHash($redirect, $reason)
    {
        //echo 'ACTION:      Update to_hash' . PHP_EOL;
        return 'update to_hash';
    }

    private function redirectOk($redirect, $reason)
    {
        //echo 'ACTION:      Ok' . PHP_EOL;
        return 'ok ' . $reason;
    }

    private function checkRedirect($redirect, $fix)
    {
        $fix->setId($redirect->getId());
        $fix->setFrom($redirect->getFrom());
        $fix->setTo($redirect->getTo());
        $fix->setLanguage($redirect->getLocale());
        $fix->setType($redirect->getType());
        $fix->setWpId($redirect->getWpId());

        // Delete redirect if in language not used on site
        if (!in_array($redirect->getLocale(), pll_languages_list())) {
            //echo 'WRONG language' . PHP_EOL;
            //return $this->deleteRedirect($redirect, $fix, 'Invalid language');
            $this->deleteRedirect($redirect);
            $fix->setAction('delete');
            $fix->setReason('Invalid language');
            return $fix;
        }

        // Get fromUrl and toUrl with domain
        $homeUrl = pll_home_url($redirect->getLocale());
        $homeUrl = rtrim($homeUrl, '/');
        $homeUrl = preg_replace('#^http:#', 'https:', $homeUrl);
        $fromUrl = $homeUrl . $redirect->getFrom();
        $toUrl = $homeUrl . $redirect->getTo();
        $fix->setFromUrl($fromUrl);
        $fix->setToUrl($toUrl);

        // Try to get post from wp_id
        $postLink = null;
        $postStatus = null;
        if (in_array($redirect->getType(), ['post-slug-change','Google Index Fix'])) {
            $postLink = get_permalink($redirect->getWpId()); // TODO kan der komme noget forkert her når det ikke er en post
            $postLink = preg_replace('#^http:#', 'https:', $postLink);
            $postStatus = get_post_status($redirect->getWpId());
            $fix->setWpIdPostLink($postLink);
            $fix->setWpIdPostStatus($postStatus);
        }

        // Try to get category by path
        $toCategoryLink = null;
        $toCategory = get_category_by_path($redirect->getTo());
        if ($toCategory) {
            $toCategoryLink = get_category_link($toCategory);
            $toCategoryLink = preg_replace('#^http:#', 'https:', $toCategoryLink);
            $fix->setToCategoryLink($toCategoryLink);
        }


        // ****** Checks and return

        // Check for external redirect, e.g.
        // https://abonnement.goerdetselv.dk/campaign/fa-3-nr-af-gor-det-selv?media=Egenannoncer
        if (substr($redirect->getTo(), 0,4) == 'http') {
            $fix->setAction('ok');
            $fix->setReason('External redirect');
            return $fix;
        }

        // Check from http code
        $fromCode = $this->getHttpCode($fromUrl);
        $fix->setFromCode($fromCode);

        // Check to http code
        $toCode = $this->getHttpCode($toUrl);
        $fix->setToCode($toCode);
        //$finalToCode = $this->finalCode($toUrl);
        //echo 'To code:     ' . $toCode . PHP_EOL;
        //echo 'Final code: ' . $finalToCode . PHP_EOL;

        if ($fromCode == 200) {
            $this->deleteRedirect($redirect);
            $fix->setAction('delete');
            $fix->setReason('From gives 200');
            return $fix;
        }

        // Permalink is not null: If permalink and fromUrl are equal, then no redirect is necessary
        // Maybe also check for 200
        // Udkommenteret - overtaget af fromCode == 200
        if ($postLink && $postLink == $fromUrl && $postStatus == 'Publish') {
            //echo '*** ' . $redirect->getId() . ' - ' . $fromCode . ' - ' . $toCode . PHP_EOL;
            /*
            $this->deleteRedirect($redirect);
            $fix->setAction('delete');
            $fix->setReason('wpIdPostLink = fromUrl');
            return $fix;
            */
        }

        // Udkommenteret - overtaget af toCode == 200
        /*
        if ($postLink && $postLink == $toUrl) {
            $fix->setAction('ok');
            $fix->setReason('Redirect wp_id post');
            return $fix;
        }
        */

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
            //return $this->redirectOk($redirect, 'redirect to category');
            $fix->setAction('ok');
            $fix->setReason('Redirect to category');
            return $fix;
        }

        // Try to get page by last part of path
        // If path is: /vaerktoej/havemaskiner/motorsav/test-af-kaedesave-paa-batter
        // then get page by: test-af-kaedesave-paa-batter
        $toPageByPathLink = null;
        $toPageByPath = get_page_by_path(basename( untrailingslashit($redirect->getTo())), OBJECT, 'contenthub_composite');
        // TODO kan der komme forkert domæne her, når samme slug på flere sprog
        // Set $toPageByPath to null if post is draft
        if ($toPageByPath && $toPageByPath->post_status != 'publish') {
            $toPageByPath = null;
        }
        if ($toPageByPath && $toPageByPath->post_type != 'contenthub_composite') {
            $toPageByPath = null;
        }
        // Set $toPageByPath to null if it is in incorrect language
        /*
        echo PHP_EOL;

        echo '* 1' . PHP_EOL;
        var_dump($redirect->getLocale());
        echo PHP_EOL;

        echo '* 2' . PHP_EOL;
        var_dump(pll_get_post($toPageByPath->ID, $redirect->getLocale()));
        echo PHP_EOL;

        echo '* 3' . PHP_EOL;
        var_dump(get_permalink($toPageByPath->ID, $redirect->getLocale()));
        echo PHP_EOL;

        echo '* 4' . PHP_EOL;
        var_dump($toPageByPath);
        echo PHP_EOL;

        echo '* 5' . PHP_EOL;
        var_dump($toPageByPath->ID);
        echo PHP_EOL;

        echo '* 6' . PHP_EOL;
        $toPageByPathLink = get_permalink($toPageByPath);
        var_dump($toPageByPathLink);
        echo PHP_EOL;
        exit;
        */

        if ($toPageByPath && !pll_get_post($toPageByPath->ID, $redirect->getLocale())) {
            $toPageByPath = null;
        }
        if ($toPageByPath) {
            var_dump($toPageByPath);
            $toPageByPathLink = get_permalink($toPageByPath);
            var_dump($toPageByPathLink);
            $toPageByPathLink = preg_replace('#^http:#', 'https:', $toPageByPathLink);
            $fix->setToPageByPathLink($toPageByPathLink);
        }
        //echo 'toPageByPathLink: ' . $toPageByPathLink . PHP_EOL;

        if (/*$redirect->getType() == 'manual' && */$toPageByPathLink && $toPageByPathLink == $toUrl) {
            //return $this->redirectOk($redirect, 'to page correct');
            $fix->setAction('ok');
            $fix->setReason('To page correct');
            return $fix;
        }

        if (/*$redirect->getType() == 'manual' && */$toPageByPathLink && $toPageByPathLink != $toUrl) {
            //return $this->updateTo($redirect, 'Post has different url now');
            $this->updateTo($redirect, parse_url($toPageByPathLink)['path']);
            $fix->setAction('update to');
            $fix->setReason('Post has different url now');
            $fix->setField('to');
            $fix->setNewValue(parse_url($toPageByPathLink)['path']);
            return $fix;
        }

        if ($toCode == 200) {
            //return $this->redirectOk($redirect, 'to 200');
            $fix->setAction('ok');
            $fix->setReason('To 200');
            return $fix;
        }

        if ($toCode == 301 || $toCode == 302) {
            //return $this->redirectOk($redirect, 'redirect 301 302');
            $fix->setAction('ok');
            $fix->setReason('Redirect 301 302');
            return $fix;
        }

        if ($toCode == 404) {
            //echo '*** 404 ***' . PHP_EOL;
            //return $this->deleteRedirect($redirect, 'To gives 404');
            $this->deleteRedirect($redirect);
            $fix->setAction('delete');
            $fix->setReason('To gives 404');
            return $fix;
        }

        echo 'Unhandled Http code *** ' . $toCode . ' ***' . PHP_EOL;
        exit;

        //https://api.goerdetselv.dk/wp-json/app/resolve?path=/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor

        //da local
        //http://api.gds.test/wp-json/app/resolve?path=/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor
    }

    public function checkToHash($redirect, $fix)
    {
        if ($fix->getAction() != 'ok') {
            return;
        }
        if (md5($redirect->getTo()) != $redirect->getToHash()) {
            $fix->setAction('update to_hash');
            $fix->setReason('to_hash wrong value');
            $fix->setField('to_hash');
            $fix->setNewValue(md5($redirect->getTo()));
        }
    }

    public function checkRedirects()
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
            //$redirects = $repository::latest()->take(10)->get();
            //$redirects = $repository->findAllBy('wp_id', 84728);    // 75912
            //$redirects = $repository->getRedirectById(119121); //577018
            //$redirects = $repository->findRedirectByPath('/vaerktoej/maalevaerktoej/vaterpas/toemrer-tip-saet-vaterpasset-paa-snor-0', 'da');
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }

        /*
        $fix = new Fix();
        $this->checkRedirect($redirects, $fix);
        echo $fix->from() . PHP_EOL;exit;
        exit;
        */

        $redirectCount = $redirects->count();
        echo 'Total redirects: ' . $redirectCount . PHP_EOL;

        $i = 0;
        foreach ($redirects as $redirect) {
            $i++;
            // TODO Skip certains types, remove later
            /*
            if (in_array($redirect->getType(), ['drupal', 'drupal-nodes'])) {
                continue;
            }
            */
            $progress = round($i / $redirectCount * 100) . '%';
            $fix = new Fix();
            $this->checkRedirect($redirect, $fix);
            $this->checkToHash($redirect, $fix);
            //$fix->outputCsv($progress);
        }
    }

    private function buildPostsArray()
    {
        $data = [];

        //var_dump( pll_languages_list(['fields' => []]) );exit;

        //var_dump( pll_home_url('nb') );exit;

        $status = 'publish';
        $perPage = 10;
        $currentPage = 1;
        $query_args = [
            'post_type' => 'contenthub_composite',
            'post_status' => $status,
            'posts_per_page' => $perPage,
            'paged' => $currentPage,
            'orderby' => 'modified',
            'order' => 'desc',
        ];
        $query_args['post__in'] = [84091,84094];

        $query = new \WP_Query($query_args);

        foreach ($query->posts as $post) {
            //var_dump($post);exit;
            $path = parse_url(get_permalink($post), PHP_URL_PATH);
            $slug = basename(untrailingslashit($path));
            $categories = preg_replace('#/[^/]+$#', '', $path);
            $language = pll_get_post_language($post->ID);
            $permalink = get_permalink($post->ID);
            $data[] = [
                'id' => $post->ID,
                'language' => $language,
                'slug' => $slug,
                'path' => $path,
                'categories' => $categories,
                'status' => $post->post_status,
                'url' => parse_url(get_permalink($post), PHP_URL_PATH),
                'permalink'=> $permalink
            ];
        }

        var_dump($data);
        exit;
    }

    public function test()
    {
        $this->buildPostsArray();
        exit;

        var_dump( get_permalink(84091) );
        echo PHP_EOL . PHP_EOL;

        var_dump( get_permalink(84094) );
        echo PHP_EOL . PHP_EOL;

        var_dump( untrailingslashit('/verktyg/elsag/test-av-ryobi-rtms-1800-kombisag') );
        var_dump( basename(untrailingslashit('/verktyg/elsag/test-av-ryobi-rtms-1800-kombisag')) );
        var_dump( get_page_by_path(basename(untrailingslashit('/verktyg/elsag/test-av-ryobi-rtms-1800-kombisag')), OBJECT, 'contenthub_composite') );
        exit;
        get_page_by_path(basename( untrailingslashit($redirect->getTo())), OBJECT, 'contenthub_composite');

        echo PHP_EOL . PHP_EOL;

        exit;

        try {
            $repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
            return;
        }
        \WP_CLI::line('Testing redirects');

        $tests = [
            119121 => 'ok Redirect to category',
            490825 => 'ok To page correct',      /* da, manual, wp_id ?, post-link (tom), to-page-by-path korrekt */
            488058 => 'delete To gives 404', /* da, post-slug-change, wp_id: nav-menu-item, to-page-by-path (tom) */
            124310 => 'ok Redirect wp_id post',  /* sv, google index fix, wp_id: post */
            124306 => 'ok Redirect wp_id post',  /* sv, google index fix, wp_id: post */
            165268 => 'delete To gives 404',
            210 => 'ok Redirect 301 302',
            101544 => 'ok External redirect',
            271919 => 'delete Invalid language',
            176889 => 'delete To gives 404',
            73105 => 'ok To 200',
            158567 => 'ok Redirect to category',
            498611 => 'delete From gives 200',
        ];

        $tests = [
            124310 => 'ok Redirect wp_id post',  /* sv, google index fix, wp_id: post */
            124306 => 'ok Redirect wp_id post',  /* sv, google index fix, wp_id: post */
        ];

        /*
        $tests = [
            9550 => 'xxx'
        ];
        */

        foreach ($tests as $id => $expected) {
            echo PHP_EOL;
            echo 'Redirect id: ' . $id . ' - ';
            $redirect = $repository->getRedirectById($id);
            $fix = new Fix();
            $this->checkRedirect($redirect, $fix);
            if ($fix->getAction() . ' ' . $fix->getReason() == $expected) {
                echo 'OK' . PHP_EOL;
                $fix->outputBlock();
                continue;
            }
            echo 'ERROR' . PHP_EOL;
            echo 'Expected: ' . $expected . PHP_EOL;
            echo 'Result:   ' . $fix->getAction() . ' ' . $fix->getReason() . PHP_EOL;
            $fix->outputBlock();
        }
    }
}