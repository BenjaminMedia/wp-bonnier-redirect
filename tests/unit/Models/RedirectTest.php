<?php

namespace Bonnier\WP\Redirect\Tests\unit\Models;

use Bonnier\WP\Redirect\Models\Redirect;
use Codeception\Test\Unit;

class RedirectTest extends Unit
{
    public function testFailsWhenSettingInvalidRedirectCode()
    {
        try {
            $redirect = new Redirect();
            $redirect->setCode(200);
        } catch (\InvalidArgumentException $exception) {
            $expectedMessage = 'Code \'200\' is not a valid redirect response code!';
            $this->assertSame($expectedMessage, $exception->getMessage());
            return;
        }
        $this->fail('Failed throwing exception, when setting invalid redirect code');
    }
}
