<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Database\DB;
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
     * ## EXAMPLES
     *     wp bonnier redirect notfound inspect
     */
    public function inspect()
    {
        $repo = new RedirectRepository(new DB());
        $client = new Client();
        $domains = LocaleHelper::getLocalizedUrls();
        $redirects = $repo->findAllBy('notfound', 0);
        $redirectCount = $redirects->count();
        $progress = make_progress_bar(sprintf('Inspecting %s redirects', number_format($redirectCount)), $redirectCount);
        $redirects->each(function (Redirect $redirect) use ($progress, $domains, $client, $repo) {
            $domain = $domains[$redirect->getLocale()] ?? null;
            if ($domain) {
                try {
                    $client->head(sprintf('%s/%s', rtrim($domain, '/'), ltrim($redirect->getTo(), '/')));
                } catch (RequestException $exception) {
                    if ($exception->getResponse()->getStatusCode() > 399) {
                        $redirect->setNotFound();
                        $repo->save($redirect);
                    }
                }
            }
            $progress->tick();
        });

        $progress->finish();
    }
}
