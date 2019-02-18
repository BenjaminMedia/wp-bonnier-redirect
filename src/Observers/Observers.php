<?php

namespace Bonnier\WP\Redirect\Observers;

use Bonnier\WP\Redirect\Repositories\LogRepository;

class Observers
{
    public static function bootstrap(LogRepository $logRepository)
    {
        $categoryObserver = new CategoryObserver($logRepository);
        $categorySubject = new CategorySubject();
        $categorySubject->attach($categoryObserver);

        $postObserver = new PostObserver($logRepository);
        $postSubject = new PostSubject();
        $postSubject->attach($postObserver);

        $tagObserver = new TagObserver($logRepository);
        $tagSubject = new TagSubject();
        $tagSubject->attach($tagObserver);
    }
}
