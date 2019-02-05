<?php

namespace Bonnier\WP\Redirect\Admin;

use Bonnier\WP\Redirect\WpBonnierRedirect;

/**
 * Class Overview - Renders redirect overview table
 * Credits: https://premium.wpmudev.org/blog/wordpress-admin-tables/
 *
 * @package Bonnier\WP\Redirect\Admin
 */
class Overview extends \WP_List_Table
{
    /** @var Overview */
    protected static $redirectsTable;

    public static function loadRedirectsTable()
    {
        self::$redirectsTable->prepare_items();

        include_once(WpBonnierRedirect::instance()->getViewPath('redirects-table-display.php'));
    }

    public static function loadRedirectsTableScreenOptions()
    {
        $arguments = [
            'label' => 'Redirects per page',
            'default' => 20,
            'option' => 'redirects_per_page',
        ];

        add_screen_option('per_page', $arguments);

        self::$redirectsTable = new self();
    }

    public static function displaySearch()
    {
        self::$redirectsTable->search_box('Find redirect', 'bonnier-redirect-find');
    }

    public static function displayTable()
    {
        self::$redirectsTable->display();
    }

    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'redirect_from' => 'Redirect From',
            'redirect_to' => 'Redirect To',
            'redirect_locale' => 'Locale',
            'redirect_type' => 'Type',
            'redirect_code' => 'Response Code',
            'id' => 'ID',
        ];
    }

    public function no_items()
    {
        echo 'No redirects created.';
    }

    public function prepare_items()
    {
        // Check if a search was performed
        $redirectSearchKey = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : null;

        // Column headers
        $this->_column_headers = $this->get_column_info();

        // Check and process any actions such as bulk actions.
        $this->handleTableActions();

        // Fetch table data
        $tableData = $this->fetchTableData();

        // Filter data in relation to a search
        if ($redirectSearchKey) {
            $tableData = $this->filterTableData($tableData, $redirectSearchKey);
        }

        // Pagination
        $redirectsPerPage = $this->get_items_per_page('redirects_per_page');
        $tablePage = $this->get_pagenum();

        // Slice results according to pagination
        $this->items = array_slice($tableData, (($tablePage - 1) * $redirectsPerPage), $redirectsPerPage);

        // Set pagination arguments
        $totalRedirects = count($tableData);
        $this->set_pagination_args([
            'total_items' => $totalRedirects,
            'per_page' => $redirectsPerPage,
            'total_pages' => ceil($totalRedirects / $redirectsPerPage),
        ]);
    }

    public function column_default($item, $column_name)
    {
        if (starts_with($column_name, 'redirect_')) {
            $column_name = str_after($column_name, 'redirect_');
        }
        return $item[$column_name];
    }

    public function column_redirect_from($item)
    {
        $pageUrl = admin_url('options-general.php');

        $deleteRedirectArgs = [
            'page' => wp_unslash($_REQUEST['page']),
            'action' => 'delete_redirect',
            'redirect_id' => absint($item['id']),
            '_wpnonce' => wp_create_nonce('delete_redirect_nonce'),
        ];

        $deleteRedirectLink = esc_url(add_query_arg($deleteRedirectArgs, $pageUrl));

        $actions = [
            'trash' => sprintf('<a href="%s" onclick="return confirm(\'Are you sure, you want to delete this redirect?\')">Delete redirect</a>', $deleteRedirectLink),
        ];


        return sprintf('<strong>%s</strong>', $item['from']) . $this->row_actions($actions);
    }

    public function get_bulk_actions()
    {
        return [
            'bulk-delete' => 'Delete redirects',
        ];
    }

    protected function column_cb($item)
    {
        return sprintf(
            '<label class="screen-reader-text" for="redirect_%s">Select %s</label>
            <input type="checkbox" name="redirects[]" id="redirect_%s" value="%s" />',
            $item['id'],
            $item['id'],
            $item['id'],
            $item['id']
        );
    }

    protected function get_sortable_columns()
    {
        return [
            'id' => ['id', true],
            'redirect_from' => 'from',
            'redirect_to' => 'to',
            'redirect_locale' => 'locale',
            'redirect_type' => 'type',
            'redirect_code' => 'code',
        ];
    }

    private function fetchTableData()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bonnier_redirects';
        $orderby = (isset($_GET['orderby'])) ? esc_sql($_GET['orderby']) : 'id';
        $order = (isset($_GET['order'])) ? esc_sql($_GET['order']) : 'ASC';

        $query = "SELECT
          `id`, `from`, `to`, `locale`, `type`, `code`
        FROM
          $table
        ORDER BY `$orderby` $order";

        return $wpdb->get_results($query, ARRAY_A);
    }

    private function filterTableData($tableData, $searchKey)
    {
        return collect($tableData)->reject(function ($row) use ($searchKey) {
            return !str_contains($row['from'], $searchKey) && !str_contains($row['to'], $searchKey);
        })->values()->toArray();
    }

    private function handleTableActions()
    {
        $tableAction = $this->current_action();

        if ('delete_redirect' === $tableAction) {
            $nonce = wp_unslash($_REQUEST['_wpnonce']);
            if (!wp_verify_nonce($nonce, 'delete_redirect_nonce')) {
                $this->invalidNonceRedirect();
            } else {
                // TODO: Delete single redirect
                add_action('admin_notices', function () {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <strong>Success:</strong>
                            The redirect was deleted!
                        </p>
                    </div>
                    <?php
                });
            }
        }

        if ((isset($_REQUEST['action']) && $_REQUEST['action'] === 'bulk-delete') ||
            (isset($_REQUEST['action2']) && $_REQUEST['action2'] === 'bulk-delete')
        ) {
            $nonce = wp_unslash($_REQUEST['_wpnonce']);
            if (!wp_verify_nonce($nonce, 'bulk-settings_page_bonnier-redirects')) {
                $this->invalidNonceRedirect();
            } elseif (isset($_REQUEST['redirects'])) {
                // TODO: delete all redirects
                add_action('admin_notices', function () {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <strong>Success:</strong>
                            <?php echo count($_REQUEST['redirects']); ?> redirect(s) was deleted!
                        </p>
                    </div>
                    <?php
                });
            }
        }
    }

    private function invalidNonceRedirect()
    {
        wp_die('Invalid Nonce', 'Error', [
            'response' => 403,
            'back_link' => esc_url(
                add_query_arg(['page' => wp_unslash($_REQUEST['page'])], admin_url('options-general.php'))
            ),
        ]);
    }
}
