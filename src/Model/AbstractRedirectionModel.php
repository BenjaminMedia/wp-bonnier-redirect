<?php

namespace Bonnier\WP\Redirect\Model;


use Bonnier\WP\Redirect\Http\BonnierRedirect;

Abstract class AbstractRedirectionModel
{
    abstract static function type();

    public static function preventSuccessMessageWhenError($id) {
        if (get_transient(BonnierRedirect::getErrorString(static::type(), $id) ) ) {
            return [];
        }
    }

    public static function displayErrorMessage($id) {
        if ( $error = get_transient(BonnierRedirect::getErrorString(static::type(), $id) ) ) { ?>
            <div class="error">
            <p><?php echo $error; ?></p>
            </div><?php

            delete_transient(BonnierRedirect::getErrorString(static::type(), $id));
        }
    }

    public static function trimAddSlash($url) {
        return '/' . trim($url, '/');
    }

}