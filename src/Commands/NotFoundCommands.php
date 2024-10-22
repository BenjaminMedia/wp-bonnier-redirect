<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Query;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use function WP_CLI\Utils\make_progress_bar;

class NotFoundCommands extends \WP_CLI_Command
{
    /**
     * Inspect redirects for 404 results
     *
     * [--host=<host>]
     * : Set host name for proper loading of envs
     *
     * ## EXAMPLES
     *     wp bonnier redirect notfound inspect
     */
    public function inspect()
    {
        if (isset($assocArgs['host'])) {
            $_SERVER['HOST_NAME'] = $assocArgs['host'];
        }
        \WP_CLI::line('Start inspecting ...');
        $repo = new RedirectRepository(new DB());
        $client = new Client(['timeout'  => 3.0]);
        $domains = LocaleHelper::getLocalizedUrls();
        $lastMonth = new \DateTime('- 1 month');
        $query = $repo->query()
            ->select('*')
            ->where(['notfound', null], Query::FORMAT_NULL)
            ->orWhere(['updated_at', $lastMonth->format('Y-m-d H:i:s'), '<'])
            ->limit(5000);
        $redirects = $repo->getRedirects($query);
        if (!$redirects) {
            \WP_CLI::line('No redirects to inspect!');
        }
        $redirectCount = $redirects->count();
        $progress = make_progress_bar(sprintf('Inspecting %s redirects', number_format($redirectCount)), $redirectCount);
        $redirects->each(function (Redirect $redirect) use ($progress, $domains, $client, $repo) {
            $url = null;
            $domain = $domains[$redirect->getLocale()] ?? null;
            if (substr($redirect->getTo(), 0, 1) !== '/') {
                $url = $redirect->getTo();
            } else if ($domain) {
                $url = sprintf('%s/%s', rtrim($domain, '/'), ltrim($redirect->getTo(), '/'));
            }
            if ($url) {
                try {
                    $client->head($url);
                    $redirect->setNotFound(false);
                    $repo->save($redirect);
                } catch (RequestException $exception) {
                    if ($exception->getResponse() && $exception->getResponse()->getStatusCode() > 399) {
                        $redirect->setNotFound();
                        $repo->save($redirect);
                    }

                }
                usleep(500 * 1000); // 500ms
            }
            $progress->tick();
        });

        $progress->finish();
    }
}
