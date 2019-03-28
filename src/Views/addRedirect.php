<?php

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Symfony\Component\HttpFoundation\Response;

/** @var \Bonnier\WP\Redirect\Controllers\CrudController $this */
/** @var \Bonnier\WP\Redirect\Models\Redirect $redirect */
$redirect = $this->getRedirect();
?>
<div class="wrap">
    <?php
    if ($redirect->getID()) {
        ?>
        <h1 class="wp-heading-inline">Edit Redirect</h1>
        <a
            class="page-title-action"
            href="<?php echo esc_url(add_query_arg(['page' => CrudController::PAGE], admin_url('admin.php'))); ?>"
        >Add New</a>
        <?php
    } else {
        ?>
        <h1>Add New Redirect</h1>
        <?php
    }
    ?>
    <hr class="wp-header-end">
    <?php
    foreach ($this->getNotices() as $notice) {
        $type = array_keys($notice)[0];
        $message = $notice[$type];
        ?>
        <div id="message" class="notice notice-<?php echo $type; ?> is-dismissible">
            <p>
                <strong><?php echo ucfirst($type); ?>:</strong>
                <?php echo $message; ?>
            </p>
        </div>
        <?php
    }
    ?>
    <form id="bonnier-redirect-add-form" method="post">
        <input type="hidden" name="redirect_id" value="<?php echo $redirect->getID(); ?>" />
        <table class="form-table">
            <tr <?php echo $this->getError('redirect_from') ? "class='error'": null; ?>>
                <th scope="row">
                    <label for="from_url">From this</label>
                </th>
                <td>
                    <input
                        id="from_url"
                        class="regular-text code"
                        name="redirect_from"
                        type="text"
                        placeholder="/old/page/slug"
                        autocomplete="off"
                        value="<?php echo $redirect->getFrom(); ?>" />
                    <p class="description">
                        <?php
                        if ($redirectFromError = $this->getError('redirect_from')) {
                            echo '<strong>' . $redirectFromError . '</strong>';
                        } else {
                            echo 'The origin URL must be relative. We can only redirect URLs on the site.
                                This means the domain part should be left out (' . home_url()  . ')';
                        }
                        ?>
                    </p>
                </td>
            </tr>
            <tr <?php echo $this->getError('redirect_to') ? "class='error'": null; ?>>
                <th scope="row">
                    <label for="to_url">To this</label>
                </th>
                <td>
                    <input
                        id="to_url"
                        class="regular-text code"
                        name="redirect_to"
                        type="text"
                        placeholder="/new/page/slug"
                        autocomplete="off"
                        value="<?php echo $redirect->getTo(); ?>" />
                    <p class="description">
                        <?php
                        if ($redirectToError = $this->getError('redirect_to')) {
                            echo '<strong>' . $redirectToError . '</strong>';
                        } else {
                            echo 'The destination URL can be a relative path (on the same site)
                            or an absolute path, if it needs to redirect externally
                            (e.g. https://bonniershop.com/product)';
                        }
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="redirect_code">Redirect type</label>
                </th>
                <td>
                    <select id="redirect_code" name="redirect_code">
                        <option
                            value="<?php echo Response::HTTP_MOVED_PERMANENTLY; ?>"
                            <?php echo $redirect->getCode() === Response::HTTP_MOVED_PERMANENTLY ? 'selected' : null; ?>
                        >Permanent Redirect (301) - Recommended</option>
                        <option
                            <?php echo $redirect->getCode() === Response::HTTP_FOUND ? 'selected' : null; ?>
                            value="<?php echo Response::HTTP_FOUND; ?>">
                            Temporary Redirect (302)
                        </option>
                    </select>
                </td>
            </tr>
            <tr <?php echo $this->getError('redirect_locale') ? "class='error'": null; ?>>
                <th scope="row">
                    <label for="redirect_locale">Locale</label>
                </th>
                <td>
                    <select id="redirect_locale" name="redirect_locale">
                        <?php
                        $languages = LocaleHelper::getLanguages();
                        if (count($languages) > 1) {
                            ?>
                            <option value="">-- Choose Language --</option>
                            <?php
                            foreach ($languages as $language) {
                                $selected = null;
                                if ($language === $redirect->getLocale()) {
                                    $selected = 'selected';
                                }
                                ?>
                                <option value="<?php echo $language; ?>" <?php echo $selected; ?>>
                                    <?php echo $language; ?>
                                </option>
                                <?php
                            }
                        } else {
                            ?>
                            <option value="<?php echo $redirect->getLocale(); ?>">
                                <?php echo $redirect->getLocale(); ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <p class="description">
                        <?php
                        if ($redirectToError = $this->getError('redirect_locale')) {
                            echo '<strong>' . $redirectToError . '</strong>';
                        } else {
                            echo 'Specify the language for which the redirect shall work.';
                        }
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">Query params</th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">Query params</legend>
                    </fieldset>
                    <label for="redirect_keep_query">
                        <input
                            id="redirect_keep_query"
                            name="redirect_keep_query"
                            type="checkbox"
                            value="1"
                            <?php echo $redirect->keepsQuery() ? 'checked' : null; ?>
                        />
                        Keep query params when redirecting ('off' is recommended)
                    </label>
                    <p class="description">
                        If query params needs to be maintained when redirecting, check this box.
                        (e.g. '/from/slug?page=4' should maintain pagination on destination: '/to/slug?page=4)
                    </p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button button-primary button-large" value="Save redirect" />
        </p>
    </form>
</div>
