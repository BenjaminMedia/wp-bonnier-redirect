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
     */
    public function update(SubjectInterface $subject)
    {
        $logs = $this->logRepository->findByWpIDAndType($subject->getPost()->ID, $subject->getPost()->post_type);
        /** @var Log $latest */
        $latest = $logs->last();
        $logs->each(function (Log $log) use ($latest, $subject) {
            if ($log->getSlug() !== $latest->getSlug()) {
                $redirect = new Redirect();
                $redirect->setFrom($log->getSlug())
                    ->setTo($latest->getSlug())
                    ->setWpID($subject->getPost()->ID)
                    ->setType('post-slug-change')
                    ->setCode(Response::HTTP_MOVED_PERMANENTLY)
                    ->setLocale(LocaleHelper::getPostLocale($subject->getPost()->ID));
                $this->redirectRepository->save($redirect, true);
            }
        });
    }
}
