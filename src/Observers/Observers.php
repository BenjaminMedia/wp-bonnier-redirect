<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Observers\Loggers\CategoryObserver;
use Bonnier\WP\Redirect\Observers\Loggers\PostObserver;
use Bonnier\WP\Redirect\Observers\Loggers\TagObserver;
use Bonnier\WP\Redirect\Observers\Redirects\PostSlugChangeObserver;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;

class Observers
{
    public static function bootstrap(LogRepository $logRepository, RedirectRepository $redirectRepository)
    {
        $categoryObserver = new CategoryObserver($logRepository);
        $categorySubject = new CategorySubject();
        $categorySubject->attach($categoryObserver);

        $postObserver = new PostObserver($logRepository);
        $slugChangeObserver = new PostSlugChangeObserver($logRepository, $redirectRepository);
        $postSubject = new PostSubject();
        $postSubject->attach($postObserver);
        $postSubject->attach($slugChangeObserver);

        $tagObserver = new TagObserver($logRepository);
        $tagSubject = new TagSubject();
        $tagSubject->attach($tagObserver);
    }
}
