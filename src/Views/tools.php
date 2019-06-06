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
    <hr />
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
    <hr />
    <h2>Redirect 404 URLs</h2>
    <form id="redirect-404" method="post" enctype="multipart/form-data">
        <p>You can upload a CSV of URLs ending in 404 and the plugin will try to create redirects.</p>
        <p>
            <strong>NOTE:</strong> The CSV has to only contain one column of FULL URLs.
            <br />
            This means, that the URL should contain the domain as well (ie. https://example.com/page/gives/404)
        </p>
        <h4>Redirect creation rules:</h4>
        <ol>
            <li>If an article exists on the last part of the slug, a redirect will be created for that.</li>
            <li>If a category page exists as part of the URL, a redirect will be created for that.</li>
            <li>If a tag page exists as part of the URL, a redirect will be created for that.</li>
            <li>Fallback: If none of the above rules apply, a redirect to the frontpage will be created.</li>
        </ol>
        <p><strong>NOTE:</strong> All redirects will be created as permanent redirects (301)</p>
        <input type="file" name="404-file" id="404-file" />
        <p class="submit">
            <button
                    type="submit"
                    id="404-submit"
                    class="button button-primary"
                    name="404"
                    value="404"
                    disabled
            >Upload</button>
        </p>
    </form>
</div>
