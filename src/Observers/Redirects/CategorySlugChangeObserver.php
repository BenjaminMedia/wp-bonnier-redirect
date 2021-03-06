<?php

namespace Bonnier\WP\Redirect\Observers\Redirects;

use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Log;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Observers\AbstractObserver;
use Bonnier\WP\Redirect\Observers\CategorySubject;
use Bonnier\WP\Redirect\Observers\Interfaces\SubjectInterface;
use Bonnier\WP\Redirect\Observers\Loggers\CategoryObserver;
use Bonnier\WP\Redirect\Observers\Loggers\PostObserver;
use Bonnier\WP\Redirect\Observers\PostSubject;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Symfony\Component\HttpFoundation\Response;

class CategorySlugChangeObserver extends AbstractObserver
{
    /** @var RedirectRepository */
    private $redirectRepository;
    private $postObserver;
    private $postLogObserver;
    private $categoryObserver;

    public function __construct(LogRepository $logRepository, RedirectRepository $redirectRepository)
    {
        parent::__construct($logRepository);
        $this->redirectRepository = $redirectRepository;
        $this->postObserver = new PostSlugChangeObserver($logRepository, $redirectRepository);
        $this->postLogObserver = new PostObserver($logRepository);
        $this->categoryObserver = new CategoryObserver($logRepository);
    }

    /**
     * @param SubjectInterface|CategorySubject $subject
     * @throws \Exception
     */
    public function update(SubjectInterface $subject)
    {
        if ($subject->getType() !== CategorySubject::UPDATE) {
            return;
        }
        $category = $subject->getCategory();
        if (!$category) {
            return;
        }
        $logs = $this->logRepository->findByWpIDAndType($category->term_id, $category->taxonomy);

        $latest = $logs->pop();

        $slug = $latest->getSlug();

        if ($logs->isNotEmpty() && $slug === $logs->last()->getSlug()) {
            // No changes happened to Category Slug
            return;
        }

        // Check for slug changes
        $noSlugChanges = true;

        $logs->each(function (Log $log) use (&$noSlugChanges, $slug, $category) {
            if ($log->getSlug() !== $slug) {
                $noSlugChanges = false;
                $redirect = new Redirect();
                $redirect->setFrom($log->getSlug())
                    ->setTo($slug)
                    ->setWpID($category->term_id)
                    ->setType('category-slug-change')
                    ->setCode(Response::HTTP_MOVED_PERMANENTLY)
                    ->setLocale(LocaleHelper::getTermLocale($category->term_id));
                $this->redirectRepository->save($redirect, true);
            }
        });

        // There's no slug changes, so we do not need to waste
        // resources on trying to update all child categories
        // and posts, since we know, their slug won't be changed.
        if ($noSlugChanges) {
            return;
        }

        if ($categories = get_categories(['parent' => $category->term_id, 'hide_empty' => false])) {
            foreach ($categories as $cat) {
                $subject = new CategorySubject();
                $subject->setCategory($cat)->setType(CategorySubject::UPDATE);
                $this->categoryObserver->update($subject);
                $this->update($subject);
            }
        }

        $postTypes = collect(get_post_types(['public' => true]))->reject('attachment');
        $postTypes->each(function (string $postType) use ($category) {
            if ($posts = get_posts([
                'post_type' => $postType,
                'category' => $category->term_id,
                'posts_per_page' => -1
            ])) {
                foreach ($posts as $post) {
                    $subject = new PostSubject();
                    $subject->setPost($post);
                    $this->postLogObserver->update($subject);
                    $this->postObserver->update($subject);
                }
            }
        });
    }
}
