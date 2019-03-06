<?php

namespace Bonnier\WP\Redirect\Commands;

class Commands
{
    private static $commands = [
        'logs' => LogCommands::class
    ];

    public static function register()
    {
        if (defined('WP_CLI') && WP_CLI) {
            collect(self::$commands)->each(function (string $class, string $prefix) {
                \WP_CLI::add_command(sprintf('bonnier redirect %s', $prefix), $class);
            });
        }
    }
}
