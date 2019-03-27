<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Exceptions\IdenticalFromToException;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\WpBonnierRedirect;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ToolController extends BaseController
{
    public function displayToolPage()
    {
        include_once(WpBonnierRedirect::instance()->getViewPath('tools.php'));
    }

    public function registerScripts()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_register_script(
                'bonnier_redirect_tool_page_script',
                WpBonnierRedirect::instance()->assetURI('scripts/tools.js'),
                false,
                WpBonnierRedirect::instance()->assetVersion('scripts/tools.js'),
                true
            );
            wp_enqueue_script('bonnier_redirect_tool_page_script');
        });
    }

    public function handlePost()
    {
        if ($this->request->isMethod(Request::METHOD_POST)) {
            if (current_user_can('manage_options')) {
                if ($this->request->request->get('export')) {
                    $this->exportRedirects();
                } elseif ($this->request->request->get('import')) {
                    $this->importRedirects();
                }
            } else {
                wp_die('Unauthorized', 'Error', [
                    'response' => 403,
                    'back_link' => admin_url('admin.php'),
                ]);
            }
        }
    }

    private function exportRedirects()
    {
        $redirects = $this->redirectRepository->findAll();
        $csv = Writer::createFromString();
        $csv->insertOne(['ID', 'From', 'To','Locale', 'Type', 'WP ID', 'Code', 'Is Wildcard', 'Keep Query Params']);
        $csv->insertAll($redirects->map(function (Redirect $redirect) {
            return [
                $redirect->getID(),
                $redirect->getFrom(),
                $redirect->getTo(),
                $redirect->getLocale(),
                $redirect->getType(),
                $redirect->getWpID(),
                $redirect->getCode(),
                $redirect->isWildcard() ? 'Yes' : 'No',
                $redirect->keepsQuery() ? 'Yes' : 'No',
            ];
        })->toArray());

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Description: File Transfer');
        header(sprintf('Content-Disposition: attachment; filename=exported-redirects-%s.csv', strtotime('now')));

        $csv->output();
        die();
    }

    private function importRedirects()
    {
        if ($file = $this->getUploadedCSV('import-file')) {
            if ($input = $this->loadCSV($file)) {
                $records = $input->getRecords();
                $filename = sprintf('files/imported-redirects-%s.csv', strtotime('now'));
                $output = Writer::createFromPath(WpBonnierRedirect::instance()->assetPath($filename, true), 'w+');
                try {
                    $output->insertOne(['ID', 'From', 'To', 'Locale', 'Code', 'Status']);
                } catch (CannotInsertRecord $exception) {
                    $this->addNotice('Could not create output log file!');
                    return;
                }
                foreach ($records as $record) {
                    $source = Arr::get($record, 'from');
                    $destination = Arr::get($record, 'to');
                    $locale = Arr::get($record, 'locale');
                    $code = intval(Arr::get($record, 'code'));
                    $status = 'Inserted';
                    $redirect = new Redirect();
                    try {
                        $redirect->setFrom($source)
                            ->setTo($destination)
                            ->setLocale($locale)
                            ->setCode($code)
                            ->setType('csv-import');
                        $this->redirectRepository->save($redirect);
                    } catch (\InvalidArgumentException $exception) {
                        if (Str::startsWith($exception->getMessage(), 'The locale')) {
                            $status = 'Ignored - Invalid Locale';
                        } elseif (Str::startsWith($exception->getMessage(), 'Code ')) {
                            $status = 'Ignored - Invalid Code';
                        }
                    } catch (IdenticalFromToException $exception) {
                        $status = 'Ignored - Identical from and to';
                    } catch (DuplicateEntryException $exception) {
                        $status = 'Ignored - Already exists';
                    } catch (\Exception $exception) {
                        $status = 'Ignored - ' . $exception->getMessage();
                    }
                    try {
                        $output->insertOne([
                            $redirect->getID(),
                            $source,
                            $destination,
                            $locale,
                            $code,
                            $status
                        ]);
                    } catch (CannotInsertRecord $exception) {
                    }
                }
                $this->addNotice(
                    sprintf(
                        'Redirects saved! <a href="%s" target="_blank">Download results here.</a>',
                        WpBonnierRedirect::instance()->assetURI($filename)
                    ),
                    'success'
                );
            } else {
                $this->addNotice('CSV seems to be formatted incorrectly.');
            }
        } else {
            $this->addNotice('Unable to process uploaded file.');
        }
    }

    private function getUploadedCSV(string $key): ?UploadedFile
    {
        /** @var UploadedFile|null $file */
        if (($file = $this->request->files->get($key)) && $file->isValid()) {
            if ($file->getMimeType() === 'text/plain' && $file->getClientMimeType() === 'text/csv') {
                return $file;
            }
        }
        return null;
    }

    private function loadCSV(UploadedFile $file): ?Reader
    {
        $csv = Reader::createFromPath($file->getPathname());
        $csv->setHeaderOffset(0);
        if ($csv->getHeader() === 'from;to;locale;code') {
            try {
                $csv->setDelimiter(';');
            } catch (Exception $exception) {
                return null;
            }
        }
        if ($csv->getHeader() === ['from', 'to', 'locale', 'code']) {
            return $csv;
        }

        return null;
    }
}
