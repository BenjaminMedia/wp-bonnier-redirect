<?php

namespace Bonnier\WP\Redirect\Tests\unit\Models;

use Bonnier\WP\Redirect\Models\Redirect;
use Codeception\Test\Unit;

class RedirectTest extends Unit
{
    public function testPrefersHashesFromDB()
    {
        $redirect = new Redirect();
        $redirect->fromArray([
            'id' => 1,
            'from' => '/from/a/slug',
            'from_hash' => 'dbmd5fromhash',
            'to' => '/to/a/slug',
            'to_hash' => 'dbmd5tohash',
            'locale' => 'da',
            'code' => 301,
            'paramless_from_hash' => 'dbmd5paramlessfromhash',
        ]);

        $this->assertSame('dbmd5fromhash', $redirect->getFromHash());
        $this->assertSame('dbmd5tohash', $redirect->getToHash());
        $this->assertSame('dbmd5paramlessfromhash', $redirect->getParamlessFromHash());
    }

    public function testGeneratesOwnHashesWhenMissing()
    {
        $redirect = new Redirect();
        $redirect->fromArray([
            'id' => 1,
            'from' => '/from/a/slug',
            'to' => '/to/a/slug',
            'locale' => 'da',
            'code' => 301,
        ]);

        $fromHash = hash('md5', '/from/a/slug');
        $toHash = hash('md5', '/to/a/slug');
        $paramlessHash = hash('md5', '/from/a/slug');

        $this->assertSame($fromHash, $redirect->getFromHash());
        $this->assertSame($toHash, $redirect->getToHash());
        $this->assertSame($paramlessHash, $redirect->getParamlessFromHash());
    }
}
