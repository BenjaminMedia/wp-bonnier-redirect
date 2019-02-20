<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Observers\Loggers\CategoryObserver;
use Bonnier\WP\Redirect\Observers\Loggers\PostObserver;
use Bonnier\WP\Redirect\Observers\Loggers\TagObserver;
use Bonnier\WP\Redirect\Observers\Redirects\CategorySlugChangeObserver;
use Bonnier\WP\Redirect\Observers\Redirects\PostSlugChangeObserver;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;

class Observers
{
    public static function bootstrap(LogRepository $logRepo, RedirectRepository $redirectRepo)
    {
        self::bootstrapCategorySubject($logRepo, $redirectRepo);

        self::bootstrapPostSubject($logRepo, $redirectRepo);

        self::bootstrapTagSubject($logRepo);
    }

    public static function bootstrapCategorySubject(LogRepository $logRepo, RedirectRepository $redirectRepo)
    {
        $categoryObserver = new CategoryObserver($logRepo);
        $categorySlugObserver = new CategorySlugChangeObserver($logRepo, $redirectRepo);
        $categorySubject = new CategorySubject();
        $categorySubject->attach($categoryObserver);
        $categorySubject->attach($categorySlugObserver);
        return $categorySubject;
    }

    public static function bootstrapPostSubject(LogRepository $logRepo, RedirectRepository $redirectRepo)
    {
        $postObserver = new PostObserver($logRepo);
        $postSlugObserver = new PostSlugChangeObserver($logRepo, $redirectRepo);
        $postSubject = new PostSubject();
        $postSubject->attach($postObserver);
        $postSubject->attach($postSlugObserver);
        return $postSubject;
    }

    public static function bootstrapTagSubject(LogRepository $logRepo)
    {
        $tagObserver = new TagObserver($logRepo);
        $tagSubject = new TagSubject();
        $tagSubject->attach($tagObserver);
    }
}
