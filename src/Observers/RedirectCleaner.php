<?php

namespace Bonnier\WP\Redirect\Observers;

trait RedirectCleaner
{
    public function shouldRedirectToDestination($to)
    {
        foreach ($this->ignorePatterns() as $pattern)
        {
            if (strpos($to, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    private function ignorePatterns(): array
    {
        return [
            '___',
        ];
    }
}