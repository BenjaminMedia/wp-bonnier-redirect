<?php

namespace Bonnier\WP\Redirect\Http;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Request
{
    const HTTP_PERMANENT_REDIRECT = 301;
    const HTTP_TEMPORARY_REDIRECT = 302;

    /** @var HttpRequest */
    private static $request;

    /**
     * @return HttpRequest
     */
    public static function instance()
    {
        if (!self::$request) {
            self::$request = HttpRequest::createFromGlobals();
        }

        return self::$request;
    }
}
