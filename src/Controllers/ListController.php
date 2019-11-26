<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\WpBonnierRedirect;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Overview - Renders redirect overview table
 * Credits: https://premium.wpmudev.org/blog/wordpress-admin-tables/
 *
 * @package Bonnier\WP\Redirect\Admin
 */
class ListController extends \WP_List_Table
{
    const DELETE_NONCE_KEY = 'delete_redirect_nonce';
    /** @var ListController */
    protected static $redirectsTable;

    /** @var array */
    private $notices = [];

    /** @var RedirectRepository */
    private $redirects;
    /** @var Request */
    private $request;

    /** @var array */
    private $redirectTypes = [];
    /** @var array */
    private $redirectLocales = [];

    public function __construct(RedirectRepository $redirects, Request $request)
    {
        parent::__construct([
            'singular' => 'redirect',
            'plural' => 'redirects',
        ]);
        $this->redirects = $redirects;
        $this->request = $request;
    }

    /**
     * @throws \Exception
     */
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

    /**
     * @return array
     */
    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * @return array
     */
    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'redirect_from' => 'Redirect From',
            'redirect_to' => 'Redirect To',
            'redirect_locale' => 'Locale',
            'redirect_type' => 'Type',
            'redirect_code' => 'Response Code',
            'redirect_user' => 'User',
            'id' => 'ID',
        ];
    }

    public function no_items()
    {
        echo 'No redirects found.';
    }

    /**
     * @throws \Exception
     */
    public function prepare_items()
    {
        // Check if a search was performed
        $redirectSearchKey = wp_unslash(trim($this->request->get('s'))) ?: null;

        // Column headers
        $this->_column_headers = $this->get_column_info();

        // Check and process any actions such as bulk actions.
        $this->handleTableActions();

        // Pagination
        $redirectsPerPage = $this->get_items_per_page('redirects_per_page');
        $tablePage = $this->get_pagenum();

        $offset = ($tablePage - 1) * $redirectsPerPage;

        $filters = [];
        $redirectType = wp_unslash(trim($this->request->get('redirect_type')));
        $redirectLocale = wp_unslash(trim($this->request->get('redirect_locale')));
        if ($redirectType) {
            $filters['type'] = $redirectType;
        }
        if ($redirectLocale) {
            $filters['locale'] = $redirectLocale;
        }

        // Fetch table data
        $this->items = $this->fetchTableData($redirectSearchKey, $offset, $redirectsPerPage, $filters);
        $this->redirectTypes = $this->fetchRedirectTypes($filters);
        $this->redirectLocales = $this->fetchRedirectLocales($filters);

        try {
            // Set pagination arguments
            $totalRedirects = $this->redirects->countRows($redirectSearchKey);
        } catch (\Exception $exception) {
            $totalRedirects = 0;
        }
        $this->set_pagination_args([
            'total_items' => $totalRedirects,
            'per_page' => $redirectsPerPage,
            'total_pages' => ceil($totalRedirects / $redirectsPerPage),
        ]);
    }

    /**
     * @param object $item
     * @param string $column_name
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        if (Str::startsWith($column_name, 'redirect_')) {
            $column_name = Str::after($column_name, 'redirect_');
        }
        if (isset($item[$column_name])) {
            return $item[$column_name];
        }
        return null;
    }

    /**
     * @param $item
     * @return string
     */
    public function column_redirect_from($item)
    {
        $pageUrl = admin_url('admin.php');

        $deleteRedirectArgs = [
            'page' => $this->request->get('page'),
            'action' => 'delete_redirect',
            'redirect_id' => absint($item['id']),
            '_wpnonce' => wp_create_nonce(self::DELETE_NONCE_KEY),
        ];

        $editRedirectArgs = [
            'page' => CrudController::PAGE,
            'action' => 'edit',
            'redirect_id' => absint($item['id']),
        ];

        $deleteRedirectLink = esc_url(add_query_arg($deleteRedirectArgs, $pageUrl));
        $editRedirectLink = esc_url(add_query_arg($editRedirectArgs, $pageUrl));

        $actions = [
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                $editRedirectLink,
                'Edit redirect'
            ),
            'trash' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
                $deleteRedirectLink,
                'Are you sure, you want to delete this redirect?',
                'Delete redirect'
            ),
        ];

        return sprintf(
            '<strong><a href="%s">%s</a></strong>%s',
            $editRedirectLink,
            $item['from'],
            $this->row_actions($actions)
        );
    }

    public function column_redirect_user($item)
    {
        if ($userID = $item['user']) {
            $user = get_user_by('id', $userID);
            return $user->display_name;
        }

        return 'N/A';
    }

    /**
     * @return array
     */
    public function get_bulk_actions()
    {
        return [
            'bulk-delete' => 'Delete redirects',
        ];
    }

    /**
     * @return bool|false|mixed|string
     */
    public function current_action()
    {
        $params = $this->request->query;
        if ($params->get('filter_action')) {
            return false;
        }

        if (($action = $params->get('action')) && $action != -1) {
            return $action;
        }

        if (($action = $params->get('action2')) && $action != -1) {
            return $action;
        }

        return false;
    }

    /**
     * @param object $item
     * @return string
     */
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

    /**
     * @return array
     */
    protected function get_sortable_columns()
    {
        return [
            'id' => ['id', true],
            'redirect_from' => 'from',
            'redirect_to' => 'to',
            'redirect_locale' => 'locale',
            'redirect_type' => 'type',
            'redirect_code' => 'code',
            'redirect_user' => 'user'
        ];
    }

    /**
     * @param string|null $searchKey
     * @param int $offset
     * @param int $perPage
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    private function fetchTableData(?string $searchKey = null, int $offset = 0, int $perPage = 20, array $filters = [])
    {
        $orderby = esc_sql($this->request->get('orderby', 'id'));
        $order = esc_sql($this->request->get('order', 'DESC'));

        return $this->redirects->find($searchKey, $orderby, $order, $perPage, $offset, $filters);
    }

    private function fetchRedirectTypes(array $filters = []): array
    {
        $query = $this->redirects->query()->select('type, COUNT(type) AS amount');
        if (isset($filters['locale'])) {
            $query->where(['locale', $filters['locale']]);
        }
        $query->groupBy('type');
        return $this->redirects->results($query) ?: [];
    }

    private function fetchRedirectLocales(array $filters = []): array
    {
        $query = $this->redirects->query()->select('locale, COUNT(locale) AS amount');
        if (isset($filters['type'])) {
            $query->where(['type', $filters['type']]);
        }
        $query->groupBy('locale');
        return $this->redirects->results($query) ?: [];
    }

    /**
     * @throws \Exception
     */
    private function handleTableActions()
    {
        $tableAction = $this->current_action();

        if ('delete_redirect' === $tableAction) {
            $this->deleteRedirect();
        }

        if ($tableAction === 'bulk-delete') {
            $this->bulkDeleteRedirects();
        }
    }

    private function deleteRedirect()
    {
        $nonce = wp_unslash($this->request->get('_wpnonce'));
        if (!wp_verify_nonce($nonce, self::DELETE_NONCE_KEY)) {
            $this->invalidNonceRedirect();
        } else {
            if (($redirectID = $this->request->get('redirect_id')) &&
                $redirect = $this->redirects->getRedirectById($redirectID)
            ) {
                try {
                    $this->redirects->delete($redirect);
                    $this->addNotice('The redirect was deleted!', 'success');
                } catch (\Exception $exception) {
                    $this->addNotice(
                        sprintf('An error occured while deleting redirect (%s)', $exception->getMessage())
                    );
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function bulkDeleteRedirects()
    {
        $nonce = wp_unslash($this->request->get('_wpnonce'));
        if (!wp_verify_nonce($nonce, 'bulk-' . $this->_args['plural'])) {
            $this->invalidNonceRedirect();
        } elseif ($redirects = $this->request->get('redirects')) {
            $this->redirects->deleteMultipleByIDs($redirects);
            $this->addNotice(sprintf('%s redirect(s) was deleted!', count($redirects)), 'success');
        }
    }

    private function invalidNonceRedirect()
    {
        wp_die('Invalid Nonce', 'Error', [
            'response' => 403,
            'back_link' => esc_url(
                add_query_arg([
                        'page' => wp_unslash($this->request->get('page'))
                ], admin_url('admin.php'))
            ),
        ]);
    }

    /**
     * @param string $notice
     * @param string $type
     */
    private function addNotice(string $notice, string $type = 'error')
    {
        $this->notices[] = [$type => $notice];
    }
}
