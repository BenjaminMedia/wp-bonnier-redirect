<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;

class TagObserver extends AbstractObserver
{
    /**
     * @param SubjectInterface|TagSubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        $tag = $subject->getTag();
        $log = new Log();
        $log->setSlug(UrlHelper::normalizePath(get_tag_link($tag)))
            ->setType($tag->taxonomy)
            ->setWpID($tag->term_id)
            ->setCreatedAt(new \DateTime());

        $this->logRepository->save($log);
    }
}
