<?php

/** @var \Bonnier\WP\Redirect\Controllers\ToolController $this */
$exampleCSV = \Bonnier\WP\Redirect\WpBonnierRedirect::instance()->assetURI('files/import-example.csv');
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Redirect tools</h1>
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
    <h2>Exporting redirects</h2>
    <form id="export-redirects" method="post">
        <p>Download a CSV of all redirects registered in this plugin</p>
        <p class="submit">
            <button
                type="submit"
                class="button button-primary"
                name="export"
                value="export"
            >Download Export File</button>
        </p>
    </form>
    <h2>Importing redirects</h2>
    <form id="import-redirects" method="post" enctype="multipart/form-data">
        <p>You can import redirects by uploading a CSV.</p>
        <p>
            <strong>NOTE:</strong> The CSV has to be in a specific format.
            <a href="<?php echo $exampleCSV; ?>" target="_blank">Download example here.</a>
        </p>
        <input type="file" name="import-file" id="import-file" />
        <p class="submit">
            <button
                type="submit"
                id="import-submit"
                class="button button-primary"
                name="import"
                value="import"
                disabled
            >Upload</button>
        </p>
    </form>
</div>
