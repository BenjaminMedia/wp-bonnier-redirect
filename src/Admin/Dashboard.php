<?php

namespace Bonnier\WP\Redirect\Admin;

class Dashboard
{
    public static function addPluginMenus()
    {
        $pageHook = add_options_page(
            'Bonnier Redirects',
            'Bonnier Redirects',
            'manage_options',
            'bonnier-redirects',
            [Overview::class, 'loadRedirectsTable']
        );

        add_action(sprintf('load-%s', $pageHook), [Overview::class, 'loadRedirectsTableScreenOptions']);
    }
}
