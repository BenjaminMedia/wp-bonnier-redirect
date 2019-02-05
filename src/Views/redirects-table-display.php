<div class="wrap">
    <h2>Bonnier Redirects</h2>
    <form id="bonnier-redirect-overview-form" method="get">
        <?php do_action('admin_notices'); ?>
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php
        \Bonnier\WP\Redirect\Admin\Overview::displaySearch();
        \Bonnier\WP\Redirect\Admin\Overview::displayTable();
        ?>
    </form>
</div>
