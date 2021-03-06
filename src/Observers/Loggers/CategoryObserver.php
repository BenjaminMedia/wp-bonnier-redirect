<?php

namespace Bonnier\WP\Redirect\Observers\Loggers;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Observers\AbstractObserver;
use Bonnier\WP\Redirect\Observers\CategorySubject;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;

class CategoryObserver extends AbstractObserver
{
    /**
     * @param SubjectInterface|CategorySubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        if ($subject->getType() === CategorySubject::UPDATE && $category = $subject->getCategory()) {
            $log = new Log();
            $log->setSlug(get_category_link($category->term_id))
                ->setType($category->taxonomy)
                ->setWpID($category->term_id);

            $this->logRepository->save($log);
        }
    }
}
