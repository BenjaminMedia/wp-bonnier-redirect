<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Database\Migrations\Migrate;
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
                if ($redirect['from'] === '' ||
                    $redirect['from'] === '/' ||
                    $redirect['to'] === '' ||
                    $redirect['locale'] === ''
                ) {
                    $database->delete($redirectID);
                } else {
                    $redirect['from'] = UrlHelper::normalizePath($redirect['from']);
                    $redirect['from_hash'] = hash('md5', $redirect['from']);
                    $redirect['paramless_from_hash'] = hash('md5', parse_url($redirect['from'], PHP_URL_PATH));
                    $redirect['to'] = UrlHelper::normalizeUrl($redirect['to']);
                    $redirect['to_hash'] = hash('md5', $redirect['to']);
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
            } catch (\Exception $exception) {
                \WP_CLI::warning(sprintf('Failed updating %s (%s)', $redirect->getID(), $exception->getMessage()));
            }
            $progress->tick();
        });
        $progress->finish();
    }
}
