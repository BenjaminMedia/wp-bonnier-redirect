<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Observers\Loggers\CategoryObserver;
use Bonnier\WP\Redirect\Observers\Loggers\PostObserver;
use Bonnier\WP\Redirect\Observers\Loggers\TagObserver;
use Bonnier\WP\Redirect\Observers\Redirects\CategoryDeleteObserver;
use Bonnier\WP\Redirect\Observers\Redirects\CategorySlugChangeObserver;
use Bonnier\WP\Redirect\Observers\Redirects\PostSlugChangeObserver;
use Bonnier\WP\Redirect\Observers\Redirects\TagDeleteObserver;
use Bonnier\WP\Redirect\Observers\Redirects\TagSlugChangeObserver;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;

class Observers
{
    private static $logRepo;
    private static $redirectRepo;

    /**
     * @param LogRepository $logRepo
     * @param RedirectRepository $redirectRepo
     */
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
    }

    public static function bootstrapPostSubject()
    {
        $logObserver = new PostObserver(self::$logRepo);
        $slugChangeObserver = new PostSlugChangeObserver(self::$logRepo, self::$redirectRepo);
        $postSubject = new PostSubject();
        $postSubject->attach($logObserver);
        $postSubject->attach($slugChangeObserver);
    }

    public static function bootstrapTagSubject()
    {
        $logObserver = new TagObserver(self::$logRepo);
        $slugChangeObserver = new TagSlugChangeObserver(self::$logRepo, self::$redirectRepo);
        $deleteObserver = new TagDeleteObserver(self::$logRepo, self::$redirectRepo);
        $tagSubject = new TagSubject();
        $tagSubject->attach($logObserver);
        $tagSubject->attach($slugChangeObserver);
        $tagSubject->attach($deleteObserver);
    }
}
