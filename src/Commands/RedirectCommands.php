<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Database\Migrations\Migrate;
use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use cli\progress\Bar;
use Illuminate\Support\Str;
use League\Csv\Writer;
use function WP_CLI\Utils\make_progress_bar;

class RedirectCommands extends \WP_CLI_Command
{
    private $csvHandle;

    /**
     * Normalizes all from and to urls in the redirect table
     *
     * ## OPTIONS
     * [--output=<file>]
     * : Optional: Specify a csv filename where updates are recorded.
     *
     * ## EXAMPLES
     *     wp bonnier redirect redirects normalize
     *
     * @param $args
     * @param $assocArgs
     *
     * @throws \WP_CLI\ExitException
     */
    public function normalize($args, $assocArgs)
    {
        if ($output = $assocArgs['output'] ?? null) {
            $this->initCSV($output);
        }
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
            $progress = make_progress_bar(sprintf('Normalizing %s redirects', number_format($results->count())), $results->count());
            $results->each(function (array $redirect) use ($database, $progress) {
                $redirectID = $redirect['id'];
                unset($redirect['id']);
                if ($redirect['from'] === '' ||
                    $redirect['from'] === '/' ||
                    $redirect['to'] === '' ||
                    $redirect['locale'] === ''
                ) {
                    $database->delete($redirectID);
                    $this->csvWrite([$redirectID, $redirect['from'], $redirect['to'], $redirect['locale'], 'N/A', 'N/A', 'Deleted']);
                } else {
                    $newFrom = UrlHelper::normalizePath($redirect['from']);
                    $newTo = UrlHelper::normalizeUrl($redirect['to']);
                    if ($newFrom !== $redirect['from'] || $newTo !== $redirect['to']) {
                        $this->csvWrite([$redirectID, $redirect['from'], $redirect['to'], $redirect['locale'], $newFrom, $newTo, 'Updated']);
                    }
                    $redirect['from'] = $newFrom;
                    $redirect['from_hash'] = hash('md5', $redirect['from']);
                    $redirect['paramless_from_hash'] = hash('md5', parse_url($redirect['from'], PHP_URL_PATH));
                    $redirect['to'] = $newTo;
                    $redirect['to_hash'] = hash('md5', $redirect['to']);
                    try {
                        $database->update($redirectID, $redirect);
                    } catch (DuplicateEntryException $exception) {
                        $database->delete($redirectID);
                        $this->csvWrite([$redirectID, $redirect['from'], $redirect['to'], $redirect['locale'], 'N/A', 'N/A', 'Deleted']);
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
     * ## OPTIONS
     * [--output=<file>]
     * : Optional: Specify a csv filename where updates are recorded.
     *
     * ## EXAMPLES
     *     wp bonnier redirect redirects unchain
     *
     * @throws \WP_CLI\ExitException
     */
    public function unchain($args, $assocArgs)
    {
        if ($output = $assocArgs['output'] ?? null) {
            $this->initCSV($output);
        }
        try {
            $repository = new RedirectRepository(new DB());
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed instantiating RedirectRepository (%s)', $exception->getMessage()));
            return;
        }
        \WP_CLI::line('Retrieving redirects...');
        try {
            $redirects = $repository->findAll()->reverse();
        } catch (\Exception $exception) {
            \WP_CLI::error(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }
        $redirectCount = $redirects->count();
        /** @var Bar $progress */
        $progress = make_progress_bar(sprintf('Unchaining %s redirects', number_format($redirectCount)), $redirectCount);
        $redirects->each(function (Redirect $redirect) use ($repository, $progress) {
            try {
                $repository->save($redirect);
            } catch (\Exception $exception) {
                \WP_CLI::warning(sprintf('Failed updating %s (%s)', $redirect->getID(), $exception->getMessage()));
            }
            $progress->tick();
        });
        $progress->finish();
        if ($this->csvHandle) {
            $unchainedRedirects = $repository->findAll();
            $progress = make_progress_bar(sprintf('Logging changes for %s redirects', number_format($redirectCount)), $redirectCount);
            $redirects->each(function (Redirect $oldRedirect) use ($unchainedRedirects, $progress) {
                /** @var Redirect|null $unchainedRedirect */
                $unchainedRedirect = $unchainedRedirects->first(function (Redirect $redirect) use ($oldRedirect) {
                    return $redirect->getID() === $oldRedirect->getID();
                });
                if ($unchainedRedirect) {
                    if ($unchainedRedirect->getFrom() !== $oldRedirect->getFrom() ||
                        $unchainedRedirect->getTo() !== $oldRedirect->getTo()
                    ) {
                        $this->csvWrite([
                            $oldRedirect->getID(),
                            $oldRedirect->getFrom(),
                            $oldRedirect->getTo(),
                            $oldRedirect->getLocale(),
                            $unchainedRedirect->getFrom(),
                            $unchainedRedirect->getTo(),
                            'Updated'
                        ]);
                    }
                } else {
                    $this->csvWrite([
                        $oldRedirect->getID(),
                        $oldRedirect->getFrom(),
                        $oldRedirect->getTo(),
                        $oldRedirect->getLocale(),
                        'N/A',
                        'N/A',
                        'Deleted'
                    ]);
                }
                $progress->tick();
            });
            $progress->finish();
        }
    }

    private function initCSV(string $file)
    {
        $filepath = Str::finish(getcwd(), '/');
        if (Str::startsWith($file, './')) {
            $filepath .= Str::after($file, './');
        } elseif (Str::startsWith($file, ['/', '~'])) {
            \WP_CLI::error('Please define output file as a relative path (eg. /path/to/file.csv)');
        } else  {
            $filepath .= $file;
        }
        $this->csvHandle = Writer::createFromPath($filepath, 'w+');
        $this->csvHandle->insertOne(['ID', 'Old From', 'Old To', 'Locale', 'New From', 'New To', 'Notes']);
    }

    private function csvWrite(array $row)
    {
        if (!$this->csvHandle) {
            return;
        }
        $this->csvHandle->insertOne($row);
    }
}
