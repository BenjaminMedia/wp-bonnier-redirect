<?php


namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Http\BonnierRedirect;
use WP_CLI;

class ParamLessHasher
{
    const CMD_NAMESPACE = 'bonnier redirect fix';

    public static function register() {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }

    /**
     * wp bonnier redirect paramless_hash_add
     */
    public function paramless_hash_add()
    {
        global $wpdb;
        $redirects = collect($wpdb->get_results(
            "SELECT `id`, `from` FROM wp_bonnier_redirects"
        ));
        $redirects->each(function ($redirect) use ($wpdb) {
            $updatedRedirect = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE wp_bonnier_redirects SET `paramless_from_hash` = MD5(%s), `from` = %s, `from_hash` = MD5(%s) WHERE `id` = %d",
                    [
                        BonnierRedirect::trimAddSlash($redirect->from, false), // paramless
                        $trimmedFrom = BonnierRedirect::trimAddSlash($redirect->from),
                        $trimmedFrom,
                        $redirect->id
                    ]
                )
            );

            if ($updatedRedirect) {
                WP_CLI::success(sprintf("Fixed redirect %d from: %s",
                        $redirect->id, $redirect->from)
                );
            }
        });

        WP_CLI::success("Done fixing redirects");
    }
}
