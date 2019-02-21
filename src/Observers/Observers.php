<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Observers\Loggers\CategoryObserver;
use Bonnier\WP\Redirect\Observers\Loggers\PostObserver;
use Bonnier\WP\Redirect\Observers\Loggers\TagObserver;
use Bonnier\WP\Redirect\Observers\Redirects\CategoryDeleteObserver;
use Bonnier\WP\Redirect\Observers\Redirects\CategorySlugChangeObserver;
use Bonnier\WP\Redirect\Observers\Redirects\PostSlugChangeObserver;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;

class Observers
{
    private static $logRepo;
    private static $redirectRepo;

    public static function bootstrap(LogRepository $logRepo, RedirectRepository $redirectRepo)
    {
        self::$logRepo = $logRepo;
        self::$redirectRepo = $redirectRepo;

        self::bootstrapCategorySubject();

        self::bootstrapPostSubject();

        self::bootstrapTagSubject();
    }

    public static function bootstrapCategorySubject()
    {
        $logObserver = new CategoryObserver(self::$logRepo);
        $slugChangeObserver = new CategorySlugChangeObserver(self::$logRepo, self::$redirectRepo);
        $deleteObserver = new CategoryDeleteObserver(self::$logRepo, self::$redirectRepo);

        $categorySubject = new CategorySubject();
        $categorySubject->attach($logObserver);
        $categorySubject->attach($slugChangeObserver);
        $categorySubject->attach($deleteObserver);
        return $categorySubject;
    }

    public static function bootstrapPostSubject()
    {
        $logObserver = new PostObserver(self::$logRepo);
        $slugChangeObserver = new PostSlugChangeObserver(self::$logRepo, self::$redirectRepo);
        $postSubject = new PostSubject();
        $postSubject->attach($logObserver);
        $postSubject->attach($slugChangeObserver);
        return $postSubject;
    }

    public static function bootstrapTagSubject()
    {
        $logObserver = new TagObserver(self::$logRepo);
        $tagSubject = new TagSubject();
        $tagSubject->attach($logObserver);
    }
}
