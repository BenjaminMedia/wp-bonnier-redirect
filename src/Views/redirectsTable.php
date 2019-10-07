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
        if (!empty($this->redirectLocales)) {
            ?>
            <div style="height: 50px;">
                <h2 class="screen-reader-text">Filter redirects list</h2>
                <ul class="subsubsub">
                    <li>
                        <a class="<?php echo $this->request->get('redirect_locale') ? '' : 'current'; ?>"
                           href="<?php echo esc_url(add_query_arg(['redirect_locale' => null])); ?>">All</a>
                        |
                    </li>
                    <?php
                    foreach($this->redirectLocales as $redirectLocale) {
                        $locale = $redirectLocale['locale'];
                        $amount = $redirectLocale['amount'];
                        ?>
                        <li>
                            <a class="<?php echo $this->request->get('redirect_locale') === $locale ? 'current' : ''; ?>"
                               href="<?php echo esc_url(add_query_arg(['redirect_locale' => $locale])); ?>"><?php echo strtoupper($locale) . ' (' . $amount . ')' ?></a>
                            |
                        </li>
                        <?php
                    }
                    ?>
                </ul>
            </div>
            <?php
        }
        if (!empty($this->redirectTypes)) {
            ?>
            <label for="redirect-filter">Filter redirects</label>
            <select id="redirect-filter" onchange="if (this.value) window.location.href = this.value">
                <option
                    value="<?php echo esc_url(add_query_arg(['redirect_type' => null])); ?>"
                    <?php echo $this->request->get('redirect_type') ? '' : 'selected'; ?>>
                    All
                </option>
                <?php
                foreach ($this->redirectTypes as $redirectType) {
                    $type = $redirectType['type'];
                    $amount = $redirectType['amount'];
                    ?>
                    <option
                        value="<?php echo esc_url(add_query_arg(['redirect_type' => $type])); ?>"
                        <?php echo $this->request->get('redirect_type') === $type ? 'selected' : ''; ?>>
                        <?php echo ucwords(preg_replace('/-/', ' ', $type)) . ' (' . $amount . ')' ?>
                    </option>
                    <?php
                }
                ?>
            </select>
            <?php
        }
        $this->displaySearch();
        $this->display();
        ?>
    </form>
</div>
