<?php
/**
 * @var \Bonnier\WP\Redirect\Controllers\ListController $this
 */
?>
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
        $this->displaySearch();
        $this->display();
        ?>
    </form>
</div>
