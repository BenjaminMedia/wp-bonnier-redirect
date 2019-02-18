<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;

class CategoryObserver extends AbstractObserver
{
    /**
     * @param SubjectInterface|CategorySubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        $category = $subject->getCategory();
        $log = new Log();
        $log->setSlug(UrlHelper::normalizePath(get_category_link($category->term_id)))
            ->setType($category->taxonomy)
            ->setWpID($category->term_id)
            ->setCreatedAt(new \DateTime());

        $this->logRepository->save($log);
    }
}
