<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\WpBonnierRedirect;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Overview - Renders redirect overview table
 * Credits: https://premium.wpmudev.org/blog/wordpress-admin-tables/
 *
 * @package Bonnier\WP\Redirect\Admin
 */
class ListController extends \WP_List_Table
{
    /** @var ListController */
    protected static $redirectsTable;
    private $notices;

    /** @var RedirectRepository */
    private $redirects;
    /** @var Request */
    private $request;

    public function __construct(RedirectRepository $redirects, Request $request)
    {
        parent::__construct();
        $this->redirects = $redirects;
        $this->request = $request;
    }

    public function displayRedirectsTable()
    {
        $this->prepare_items();

        include_once(WpBonnierRedirect::instance()->getViewPath('redirectsTable.php'));
    }

    public static function loadRedirectsTable()
    {
        $arguments = [
            'label' => 'Redirects per page',
            'default' => 20,
            'option' => 'redirects_per_page',
        ];

        add_screen_option('per_page', $arguments);
    }

    public function displaySearch()
    {
        $this->search_box('Find redirect', 'bonnier-redirect-find');
    }

    public function displayTable()
    {
        $this->display();
    }

    public function displayNotices()
    {
        foreach ($this->getNotices() as $notice) {
            $notice();
        }
    }

    public function getNotices()
    {
        if ($this->notices) {
            return $this->notices;
        }

        return [];
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
        echo 'No redirects found.';
    }

    public function prepare_items()
    {
        // Check if a search was performed
        $redirectSearchKey = wp_unslash(trim($this->request->query->get('s'))) ?: null;

        // Column headers
        $this->_column_headers = $this->get_column_info();

        // Check and process any actions such as bulk actions.
        $this->handleTableActions();

        // Pagination
        $redirectsPerPage = $this->get_items_per_page('redirects_per_page');
        $tablePage = $this->get_pagenum();

        $offset = ($tablePage - 1) * $redirectsPerPage;

        // Fetch table data
        $this->items = $this->fetchTableData($redirectSearchKey, $offset, $redirectsPerPage);

        // Set pagination arguments
        $totalRedirects = $this->redirects->countRows($redirectSearchKey);
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
        $pageUrl = admin_url('admin.php');

        $deleteRedirectArgs = [
            'page' => $this->request->query->get('page'),
            'action' => 'delete_redirect',
            'redirect_id' => absint($item['id']),
            '_wpnonce' => wp_create_nonce('delete_redirect_nonce'),
        ];

        $deleteRedirectLink = esc_url(add_query_arg($deleteRedirectArgs, $pageUrl));

        $actions = [
            'trash' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
                $deleteRedirectLink,
                'Are you sure, you want to delete this redirect?',
                'Delete redirect'
            ),
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

    private function fetchTableData(?string $searchKey = null, int $offset = 0, int $perPage = 20)
    {
        $orderby = esc_sql($this->request->query->get('orderby', 'id'));
        $order = esc_sql($this->request->query->get('order', 'DESC'));

        return $this->redirects->find($searchKey, $orderby, $order, $perPage, $offset);
    }

    private function handleTableActions()
    {
        $tableAction = $this->current_action();

        if ('delete_redirect' === $tableAction) {
            $nonce = wp_unslash($this->request->query->get('_wpnonce'));
            if (!wp_verify_nonce($nonce, 'delete_redirect_nonce')) {
                $this->invalidNonceRedirect();
            } else {
                // TODO: Delete single redirect
                $this->addNotice(function () {
                    ?>
                    <div id="message" class="updated notice is-dismissible">
                        <p>
                            <strong>Success:</strong>
                            The redirect was deleted!
                        </p>
                    </div>
                    <?php
                });
            }
        }

        if ($this->request->query->get('action') === 'bulk-delete' ||
            $this->request->query->get('action2') === 'bulk-delete'
        ) {
            $nonce = wp_unslash($this->request->query->get('_wpnonce'));
            if (!wp_verify_nonce($nonce, 'bulk-' . $this->_args['plural'])) {
                $this->invalidNonceRedirect();
            } elseif ($redirects = $this->redirects->query->get('redirects')) {
                $this->redirects->deleteByID($redirects);
                $this->addNotice(function () use ($redirects) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <strong>Success:</strong>
                            <?php echo count($redirects); ?> redirect(s) was deleted!
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
                add_query_arg([
                        'page' => wp_unslash($this->redirects->query->get('page'))
                ], admin_url('admin.php'))
            ),
        ]);
    }

    private function addNotice(callable $notice)
    {
        if (!$this->notices) {
            $this->notices = [];
        }
        $this->notices[] = $notice;
    }
}
