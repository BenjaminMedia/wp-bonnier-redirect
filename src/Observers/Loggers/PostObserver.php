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
        $log = new Log();
        $log->setSlug(UrlHelper::normalizePath(get_permalink($post)))
            ->setType($post->post_type)
            ->setWpID($post->ID)
            ->setCreatedAt(new \DateTime());

        $this->logRepository->save($log);
    }
}
