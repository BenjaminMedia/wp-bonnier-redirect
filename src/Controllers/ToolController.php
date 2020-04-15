<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Exceptions\IdenticalFromToException;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
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
                if ($this->request->get('export')) {
                    $this->exportRedirects();
                } elseif ($this->request->get('import')) {
                    $this->importRedirects();
                } elseif ($this->request->get('404')) {
                    $this->redirect404();
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
        $file = $this->getUploadedCSV('import-file');
        $shouldOverwrite = $this->request->request->getBoolean('import-overwrite');
        if (!$file) {
            $this->addNotice('Unable to process uploaded file.');
            return;
        }
        $input = $this->loadCSV($file);
        if (!$input) {
            $this->addNotice('CSV seems to be formatted incorrectly.');
            return;
        }
        $output = $this->getOutputFile('imported-redirects');
        if (!$output) {
            return;
        }
        $records = $input->getRecords();
        foreach ($records as $record) {
            $source = Arr::get($record, 'from');
            $destination = Arr::get($record, 'to');
            $locale = Arr::get($record, 'locale');
            $code = intval(Arr::get($record, 'code'));
            $this->saveRedirect(
                $output,
                $source,
                $destination,
                $locale,
                'csv-import',
                $code,
                $shouldOverwrite
            );
        }
        $this->addNotice(
            sprintf(
                'Redirects saved! <a href="%s" target="_blank">Download results here.</a>',
                $this->getWriterURL($output)
            ),
            'success'
        );
    }

    private function redirect404()
    {
        $file = $this->getUploadedCSV('404-file');
        if (!$file) {
            $this->addNotice('Unable to process uploaded file.');
            return;
        }
        $input = $this->loadCSV($file, false);
        if (!$input) {
            $this->addNotice('CSV seems to be formatted incorrectly.');
            return;
        }
        $output = $this->getOutputFile('404-redirects');
        if (!$output) {
            return;
        }
        $records = $input->getRecords();
        foreach ($records as $record) {
            $url = Arr::get($record, 0);
            $destination = $this->getDestination($url);
            $locale = LocaleHelper::getUrlLocale($url) ?? '';
            $this->saveRedirect($output, $url, $destination, $locale, 'csv-404-redirect');
        }
        $this->addNotice(
            sprintf(
                'Redirects saved! <a href="%s" target="_blank">Download results here.</a>',
                $this->getWriterURL($output)
            ),
            'success'
        );
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

    private function loadCSV(UploadedFile $file, bool $header = true): ?Reader
    {
        $csv = Reader::createFromPath($file->getPathname());
        if ($header) {
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
        return $csv;
    }

    private function getDestination(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return '/';
        }
        $reversed = array_reverse($parts);
        foreach ($reversed as $slug) {
            if ($permalink = $this->getPermalink($slug)) {
                return rtrim(parse_url($permalink, PHP_URL_PATH), '/');
            }
        }
        return '/';
    }

    private function getPermalink(string $slug): ?string
    {
        $postTypes = $postTypes = collect(get_post_types(['public' => true]))->reject('attachment');
        $posts = get_posts([
            'name' => $slug,
            'post_status' => 'publish',
            'post_type' => $postTypes->toArray()
        ]);
        if (!empty($posts)) {
            return get_permalink($posts[0]);
        }

        if ($category = get_category_by_slug($slug)) {
            return get_category_link($category);
        }

        if ($tag = get_term_by('slug', $slug, 'post_tag')) {
            return get_tag_link($tag);
        }

        return null;
    }

    private function getOutputFile(string $name): ?Writer
    {
        $uploadDir = wp_get_upload_dir();
        if (empty($uploadDir['path'])) {
            $this->addNotice('Could not store result CSV.');
            return null;
        }
        $filename = sprintf(
            '%s-%s.csv',
            $name,
            strtotime('now')
        );
        $filepath = sprintf('%s/%s', rtrim($uploadDir['path'], '/'), $filename);
        $output = Writer::createFromPath($filepath, 'w+');
        try {
            $output->insertOne(['ID', 'From', 'To', 'Locale', 'Code', 'Status']);
        } catch (CannotInsertRecord $exception) {
            $this->addNotice('Could not create output log file!');
            return null;
        }
        return $output;
    }

    private function saveRedirect(
        Writer $output,
        string $source,
        string $destination,
        string $locale,
        string $type,
        int $code = 301,
        bool $update = false
    ) {
        $status = 'Inserted';
        $redirect = new Redirect();
        try {
            $redirect->setFrom($source)
                ->setTo($destination)
                ->setLocale($locale)
                ->setCode($code)
                ->setType($type);
            $this->redirectRepository->save($redirect, $update);
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
                301,
                $status
            ]);
        } catch (CannotInsertRecord $exception) {
        }
    }

    private function getWriterURL(Writer $writer): ?string
    {
        $uploadDir = wp_get_upload_dir();
        if (!isset($uploadDir['path'], $uploadDir['url'])) {
            return null;
        }
        return str_replace($uploadDir['path'], $uploadDir['url'], $writer->getPathname());
    }
}
