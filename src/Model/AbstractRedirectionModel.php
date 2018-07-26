<?php

namespace Bonnier\WP\Redirect\Model;

use Bonnier\WP\Redirect\Http\BonnierRedirect;

abstract class AbstractRedirectionModel
{
    abstract public static function type();

    public static function preventSuccessMessageWhenError($typeId)
    {
        if (get_transient(BonnierRedirect::getErrorString(static::type(), $typeId))) {
            return [];
        }
    }

    public static function displayErrorMessage($typeId)
    {
        if ($error = get_transient(BonnierRedirect::getErrorString(static::type(), $typeId))) { ?>
            <div class="error">
            <p><?php echo $error; ?></p>
            </div><?php

            delete_transient(BonnierRedirect::getErrorString(static::type(), $typeId));
        }
    }

    public static function trimAddSlash($url)
    {
        return '/' . trim($url, '/');
    }

    public static function invalidSlug($slug)
    {
        if (filter_var($slug, FILTER_VALIDATE_URL)) {
            return true;
        }
        return false;
    }

}
