<?php

namespace Bonnier\WP\Redirect\Observers\Redirects;

use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Observers\AbstractObserver;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;
use Bonnier\WP\Redirect\Observers\TagSubject;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Symfony\Component\HttpFoundation\Response;

class TagSlugChangeObserver extends AbstractObserver
{
    private $redirectRepository;

    public function __construct(LogRepository $logRepository, RedirectRepository $redirectRepository)
    {
        parent::__construct($logRepository);
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param SubjectInterface|TagSubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        if ($subject->getType() === TagSubject::UPDATE && $tag = $subject->getTag()) {
            $logs = $this->logRepository->findByWpIDAndType($tag->term_id, $tag->taxonomy);

            $latest = $logs->pop();

            $slug = $latest->getSlug();

            $logs->each(function (Log $log) use ($tag, $slug) {
                if ($log->getSlug() !== $slug) {
                    $redirect = new Redirect();
                    $redirect->setFrom($log->getSlug())
                        ->setTo($slug)
                        ->setLocale(LocaleHelper::getTermLocale($tag->term_id))
                        ->setCode(Response::HTTP_MOVED_PERMANENTLY)
                        ->setType('tag-slug-change')
                        ->setWpID($tag->term_id);

                    $this->redirectRepository->save($redirect, true);
                }
            });
        }
    }
}
