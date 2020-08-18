<?php

namespace Bonnier\WP\Redirect\Observers\Redirects;

use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Observers\AbstractObserver;
use Bonnier\WP\Redirect\Observers\CategorySubject;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Symfony\Component\HttpFoundation\Response;

class CategoryDeleteObserver extends AbstractObserver
{
    private $redirectRepository;

    public function __construct(LogRepository $logRepository, RedirectRepository $redirectRepository)
    {
        parent::__construct($logRepository);
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param SubjectInterface|CategorySubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        if ($subject->getType() !== CategorySubject::DELETE) {
            return;
        }
        $category = $subject->getCategory();
        if (!$category) {
            return;
        }

        $slug = '/';
        $parentID = $category->parent;
        if ($parentID) {
            $slug = rtrim(parse_url(get_category_link($parentID), PHP_URL_PATH), '/');
        }
        $logs = $this->logRepository->findByWpIDAndType($category->term_id, $category->taxonomy);
        if (!empty($logs)){
            $logs->each(function (Log $log) use ($slug, $category, $subject) {
                if ($log->getSlug() !== $slug) {
                    $redirect = new Redirect();
                    $redirect->setFrom($log->getSlug())
                             ->setTo($slug)
                             ->setWpID($category->term_id)
                             ->setType('category-deleted')
                             ->setCode(Response::HTTP_MOVED_PERMANENTLY)
                             ->setLocale($subject->getLocale() ?: LocaleHelper::getTermLocale($category->term_id));
                    $this->redirectRepository->save($redirect, true);
                }
            });
        }

        if ($posts = $subject->getAffectedPosts()) {
            $postCategory = $parentID ?: (int) get_option('default_category');
            foreach ($posts as $postID) {
                wp_set_post_categories($postID, $postCategory);
                if (get_post($postID)){
                    do_action('save_post', $postID, get_post($postID), true);
                }
            }
        }
    }
}
