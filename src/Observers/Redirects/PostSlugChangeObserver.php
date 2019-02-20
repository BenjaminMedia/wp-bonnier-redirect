<?php

namespace Bonnier\WP\Redirect\Observers\Redirects;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Observers\AbstractObserver;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;
use Bonnier\WP\Redirect\Observers\PostSubject;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Symfony\Component\HttpFoundation\Response;

class PostSlugChangeObserver extends AbstractObserver
{
    /** @var RedirectRepository */
    private $redirectRepository;

    public function __construct(LogRepository $logRepository, RedirectRepository $redirectRepository)
    {
        parent::__construct($logRepository);
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param SubjectInterface|PostSubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        $post = $subject->getPost();
        $logs = $this->logRepository->findByWpIDAndType($post->ID, $post->post_type);
        if (in_array($post->post_status, ['trash', 'draft'])) {
            if (!empty($categories = $subject->getPost()->post_category)) {
                $category = $categories[0];
                $slug = rtrim(parse_url(get_category_link($category), PHP_URL_PATH), '/');
            } else {
                $slug = '/';
            }
            $type = 'post-' . $post->post_status;
        } else {
            /** @var Log $latest */
            $latest = $logs->pop();
            $slug = $latest->getSlug();
            $type = 'post-slug-change';
        }

        if ($redirects = $this->redirectRepository->findBy('from', $slug)) {
            $this->redirectRepository->deleteMultiple($redirects);
        }

        $logs->each(function (Log $log) use ($slug, $subject, $type) {
            if ($log->getSlug() !== $slug) {
                $redirect = new Redirect();
                $redirect->setFrom($log->getSlug())
                    ->setTo($slug)
                    ->setWpID($subject->getPost()->ID)
                    ->setType($type)
                    ->setCode(Response::HTTP_MOVED_PERMANENTLY)
                    ->setLocale(LocaleHelper::getPostLocale($subject->getPost()->ID));
                $this->redirectRepository->save($redirect, true);
            }
        });
    }
}
