<?php

namespace Bonnier\WP\Redirect\Observers\Loggers;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Observers\AbstractObserver;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;
use Bonnier\WP\Redirect\Observers\PostSubject;

class PostObserver extends AbstractObserver
{
    /**
     * @param SubjectInterface|PostSubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        $post = $subject->getPost();

        if ($post->post_status !== 'publish') {
            return;
        }

        $post = get_post($post->ID);
        $slug = UrlHelper::normalizePath(get_permalink($post));

        $logs = $this->logRepository->findByWpIDAndType($post->ID, $post->post_type);
        if ($logs) {
            $latest = $logs->pop();
            $latestSlug = $latest->getSlug();

            if ($latestSlug === $slug) {
                return ;
            }
        }

        $log = new Log();
        $log->setSlug($slug)
            ->setType($post->post_type)
            ->setWpID($post->ID)
            ->setCreatedAt(new \DateTime());

        $this->logRepository->save($log);
    }
}
