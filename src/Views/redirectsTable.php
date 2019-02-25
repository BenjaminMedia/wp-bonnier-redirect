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
            if ($message = $notice['error'] ?? null) {
                ?>
                <div id="message" class="notice notice-error is-dismissible">
                    <p>
                        <strong>Error:</strong>
                        <?php echo $message; ?>
                    </p>
                </div>
                <?php
            } elseif ($message = $notice['success'] ?? null) {
                ?>
                <div id="message" class="notice notice-success is-dismissible">
                    <p>
                        <strong>Success:</strong>
                        <?php echo $message; ?>
                    </p>
                </div>
                <?php
            }
        }
        $this->displaySearch();
        $this->display();
        ?>
    </form>
</div>
