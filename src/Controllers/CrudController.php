<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Http\Request;
use Bonnier\WP\Redirect\WpBonnierRedirect;

class CrudController
{
    public static function displayAddRedirectPage()
    {
        include_once(WpBonnierRedirect::instance()->getViewPath('addRedirect.php'));
    }

    public static function handlePost()
    {
        if (Request::instance()->isMethod('post')) {
            /*
            [
                'from' => Request::instance()->request->get('from_url'),
                'to' => Request::instance()->request->get('to_url'),
                'external' => Request::instance()->request->getBoolean('external'),
                'code' => Request::instance()->request->get('redirect_code'),
            ];
            */
        }
    }

    public static function registerScripts()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_register_script(
                'bonnier_redirect_manage_page_script',
                WpBonnierRedirect::instance()->assetURI('scripts/crud.js'),
                false,
                WpBonnierRedirect::instance()->assetVersion('scripts/crud.js'),
                true
            );
            wp_register_style(
                'bonnier_redirect_manage_page_style',
                WpBonnierRedirect::instance()->assetURI('styles/crud.css'),
                false,
                WpBonnierRedirect::instance()->assetVersion('styles/crud.css')
            );
            wp_enqueue_style('bonnier_redirect_manage_page_style');
            wp_enqueue_script('bonnier_redirect_manage_page_script');
        });
    }
}
