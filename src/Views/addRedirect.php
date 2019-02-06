<div class="wrap">
    <h2>Add New Redirect</h2>
    <form id="bonnier-redirect-add-form" method="post">
        <table>
            <tr>
                <td><strong>From this:</strong></td>
                <td>
                    <?php echo get_home_url(); ?>
                    <input type="text" name="from_url" placeholder="/old/page/slug" value="/old/from/slug" />
                </td>
            </tr>
            <tr>
                <td><strong>To this:</strong></td>
                <td>
                    <span id="to_home_url" style="display:inline"><?php echo get_home_url(); ?></span>
                    <input type="text" name="to_url" id="to_url" placeholder="/new/page/slug" value="/new/to/slug" />
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <label>
                        <input type="checkbox" name="external" id="external_checkbox" />
                        Make destination external
                    </label>
                </td>
            </tr>
            <tr>
                <td><label for="redirect_code"><strong>Redirect type:</strong></label></td>
                <td>
                    <select id="redirect_code" name="redirect_code">
                        <option value="<?php echo \Bonnier\WP\Redirect\Http\Request::HTTP_PERMANENT_REDIRECT; ?>">
                            Permanent Redirect (301)
                        </option>
                        <option value="<?php echo \Bonnier\WP\Redirect\Http\Request::HTTP_TEMPORARY_REDIRECT; ?>">
                            Temporary Redirect (302)
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="submit" class="button button-primary button-large" value="Save redirect" />
                </td>
            </tr>
        </table>
    </form>
</div>
