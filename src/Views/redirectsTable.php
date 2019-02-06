<div class="wrap">
    <h1 class="wp-heading-inline">Bonnier Redirects</h1>
    <a
        class="page-title-action"
        href="<?php echo esc_url(add_query_arg(['page' => 'add-redirect'], admin_url('admin.php'))); ?>"
    >Add New</a>
    <hr class="wp-header-end">

    <form id="bonnier-redirect-overview-form" method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php
        \Bonnier\WP\Redirect\Controllers\ListController::displayNotices();
        \Bonnier\WP\Redirect\Controllers\ListController::displaySearch();
        \Bonnier\WP\Redirect\Controllers\ListController::displayTable();
        ?>
    </form>
</div>
