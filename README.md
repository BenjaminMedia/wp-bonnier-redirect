# wp-bonnier-redirect

A requirement to use this plugin is custom-redirects


### Importing

To import aliases and redirect you must run the following commands

`wp bonnier redirect import redirect GDS-path_redirect-20170814.csv`

and

`wp bonnier redirect import alias GDS-url_alias-20170814.csv`

### Filters
This plugin exposes the following filters:

`redirect/slug-is-live | WPBonnierRedirect::FILTER_SLUG_IS_LIVE`:

Perform your own validation on whether a url is live or not.

```php
/**
 * @param bool $isLive The plugins evaluation of whether the url is live or not
 * @param string $url The 'from'-url being saved
 * @param string $locale The locale of the redirect being saved
 * @param WP_Post|WP_Term|null $object The object found by the redirect plugin - null if $isLive == false
 *
 * @return bool
 */
add_filter('redirect/slug-is-live', function (bool $isLive, string $url, string $locale, $object) {
    return $isLive;
}, 10, 4);
```
