<?php
/**
 * Plugin Name: WP Bonnier Redirect
 * Version: 1.0.0
 * Plugin URI: https://github.com/BenjaminMedia/wp-bonnier-redirect
 * Description: This plugin creates redirects with support for Polylang
 * Author: Bonnier - Nicklas Kevin Frank
 * License: GPL v3
 */

namespace Bonnier\WP\Redirect;

// Do not access this file directly
use Bonnier\WP\Redirect\Commands\CsvImport;
use Bonnier\WP\Redirect\Db\Bootstrap;

use Bonnier\WP\Redirect\Helpers\Pagination\Pagination;
use Bonnier\WP\Redirect\Http\BonnierRedirect;
use Bonnier\WP\Redirect\Model\Post;
use Bonnier\WP\Redirect\Model\Tag;

if (!defined('ABSPATH')) {
    exit;
}

// Handle autoload so we can use namespaces
spl_autoload_register(function ($className) {
    if (strpos($className, __NAMESPACE__) !== false) {
        $classPath = str_replace( // Replace namespace with directory separator
            "\\",
            DIRECTORY_SEPARATOR,
            str_replace( // Replace namespace with path to class dir
                __NAMESPACE__,
                __DIR__ . DIRECTORY_SEPARATOR . Plugin::CLASS_DIR,
                $className
            )
        );
        require_once($classPath . '.php');
    }
});

// Load dependencies from includes
require_once( __DIR__ . '/includes/vendor/autoload.php' );

class Plugin
{
    /**
     * Text domain for translators
     */
    const TEXT_DOMAIN = 'wp-bonnier-redirect';

    const CLASS_DIR = 'src';

    /**
     * @var object Instance of this class.
     */
    private static $instance;

    public $settings;

    /**
     * @var string Filename of this class.
     */
    public $file;

    /**
     * @var string Basename of this class.
     */
    public $basename;

    /**
     * @var string Plugins directory for this plugin.
     */
    public $plugin_dir;

    /**
     * @var string Plugins url for this plugin.
     */
    public $plugin_url;

    /**
     * Do not load this more than once.
     */
    private function __construct()
    {
        // Set plugin file variables
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = plugin_dir_url($this->file);

        // Load textdomain
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename) . '/languages');

        if ( defined('WP_CLI') && WP_CLI ) {
            CsvImport::register();
        }

        BonnierRedirect::register();

        Post::register();
        Tag::register();
    }

    /**
     * Returns the instance of this class.
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
            global $wp_bonnier_redirect;
            $wp_bonnier_redirect = self::$instance;

            /**
             * Run after the plugin has been loaded.
             */
            do_action('wp_bonnier_redirect_loaded');
        }

        return self::$instance;
    }

}

/**
 * @return Plugin $instance returns an instance of the plugin
 */
function instance()
{
    return Plugin::instance();
}

register_activation_hook( __FILE__, function(){
    Bootstrap::create_redirects_table();
});


function wp_bonnier_redirect_options_page() {
    wp_enqueue_script('vue', plugin_dir_url(__FILE__) . 'assets/vue.min.js');
    wp_enqueue_script('vue-resource', plugin_dir_url(__FILE__) . 'assets/vue-resource.min.js');
    wp_enqueue_script('vue-paginate', plugin_dir_url(__FILE__) . 'assets/vue-paginate.js');
    wp_enqueue_script('lodash', plugin_dir_url(__FILE__) . 'assets/lodash.min.js');
    ?>

    <script>
        window.onload = function () {
            var app = new Vue({
                el: '#app',
                data: {
                    to: '',
                    from: '',
                    locale: '',
                    id: '',
                    paginationPage: 1,
                    redirects: [],
                    count: 0,
                    searchQueryIsDirty: false,
                    isCalculating: false
                },
                created() {
                    this.updateResource()
                },
                components: {
                    paginate: VuejsPaginate
                },
                watch: {
                    paginationPage: function (val) {
                        this.updateResource();
                    },
                },
                computed: {
                    pageCount() {
                        return Math.ceil(this.count / 20);
                    }
                },
                methods: {
                    updatePage(page) {
                        this.paginationPage = page;
                        this.$refs.pagination.selected = page - 1;
                    },
                    updateResource: this._.debounce(function () {
                        setTimeout(function () {
                            this.$http.post(ajaxurl,
                                'action=bonnier_redirects&page_number=' + this.paginationPage
                                + '&to=' + this.to
                                + '&from=' + this.from
                                + '&locale=' + this.locale
                                + '&id=' + this.id,
                                {
                                    'headers': { 'Content-Type': 'application/x-www-form-urlencoded' }
                                }
                            ).then(function (data, status, request) {
                                this.redirects = data.data.hits;
                                this.count = data.data.count;
                            });
                        }.bind(this), 1000)
                    }, 500),
                }
            });
        }
    </script>
<div id="app" v-cloak="true">
    <div class="wrap">
        <h2><?php _e('Bonnier Redirects', 'bonnier-redirects') ?></h2>
        <div v-on:keyup="updateResource; updatePage(1)">
            <span>To: </span> <input type="text" placeholder="Filter To" v-model="to">
            <span>From: </span> <input type="text" placeholder="Filter From" v-model="from">
            <span>Locale: </span> <input type="text" placeholder="Filter Locale" v-model="locale">
            <span>Id: </span> <input type="text" placeholder="Filter Id" v-model="id">
        </div>

        <br class="clear" />
        <table class="widefat">
            <thead>
            <tr>
                <th scope="col"><?php _e('To', 'bonnier-redirects') ?></th>
                <th scope="col"><?php _e('From', 'bonnier-redirects') ?></th>
                <th scope="col"><?php _e('Locale', 'bonnier-redirects') ?></th>
                <th scope="col"><?php _e('Type', 'bonnier-redirects') ?></th>
                <th scope="col"><?php _e('Id', 'bonnier-redirects') ?></th>
                <th scope="col"><?php _e('Code', 'bonnier-redirects') ?></th>
            </tr>
            </thead>
            <tbody>
                <tr valign="top" v-for="redirect in redirects">
                    <td>{{redirect.to}}</td>
                    <td>{{redirect.from}}</td>
                    <td>{{redirect.locale}}</td>
                    <td>{{redirect.type}}</td>
                    <td>{{redirect.id}}</td>
                    <td>{{redirect.code}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <br class="clear" />
    <paginate
            ref="pagination"
        :page-count="pageCount"
        :click-handler="updatePage"
        :prev-text="'Prev'"
        :next-text="'Next'"
        :container-class="'pagination'">
    </paginate>
</div>
    <style>
        [v-cloak] {
            display: none;
        }
        .pagination{height:36px;margin:0;padding: 0;}
        .pager,.pagination ul{margin-left:0;*zoom:1}
        .pagination ul{padding:0;display:inline-block;*display:inline;margin-bottom:0;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;-webkit-box-shadow:0 1px 2px rgba(0,0,0,.05);-moz-box-shadow:0 1px 2px rgba(0,0,0,.05);box-shadow:0 1px 2px rgba(0,0,0,.05)}
        .pagination li{display:inline}
        .pagination a{float:left;padding:0 12px;line-height:30px;text-decoration:none;border:1px solid #ddd;border-left-width:0}
        .pagination .active a,.pagination a:hover{background-color:#f5f5f5;color:#94999E}
        .pagination .active a{color:#94999E;cursor:default}
        .pagination .disabled a,.pagination .disabled a:hover,.pagination .disabled span{color:#94999E;background-color:transparent;cursor:default}
        .pagination li:first-child a,.pagination li:first-child span{border-left-width:1px;-webkit-border-radius:3px 0 0 3px;-moz-border-radius:3px 0 0 3px;border-radius:3px 0 0 3px}
        .pagination li:last-child a{-webkit-border-radius:0 3px 3px 0;-moz-border-radius:0 3px 3px 0;border-radius:0 3px 3px 0}
        .pagination-centered{text-align:center}
        .pagination-right{text-align:right}
        .pager{margin-bottom:18px;text-align:center}
        .pager:after,.pager:before{display:table;content:""}
        .pager li{display:inline}
        .pager a{display:inline-block;padding:5px 12px;background-color:#fff;border:1px solid #ddd;-webkit-border-radius:15px;-moz-border-radius:15px;border-radius:15px}
        .pager a:hover{text-decoration:none;background-color:#f5f5f5}
        .pager .next a{float:right}
        .pager .previous a{float:left}
        .pager .disabled a,.pager .disabled a:hover{color:#999;background-color:#fff;cursor:default}
        .pagination .prev.disabled span{float:left;padding:0 12px;line-height:30px;text-decoration:none;border:1px solid #ddd;border-left-width:1}
        .pagination .next.disabled span{float:left;padding:0 12px;line-height:30px;text-decoration:none;border:1px solid #ddd;border-left-width:0}
        .pagination li.active, .pagination li.disabled {
            float:left;padding:0 1px;line-height:30px;text-decoration:none;border:1px solid #ddd;border-left-width:0
        }
        .pagination li.active {
            background: #7aacde;
            color: #fff;
        }
        .pagination li:first-child {
            border-left-width: 1px;
        }
    </style>
    <?php
}

function bonnier_redirects_admin_rows() {
    list($posts, $count) = BonnierRedirect::paginateFetchRedirect(
        $_REQUEST['page_number'] ?? 1,
        $_REQUEST['to'] ?? '',
        $_REQUEST['from'] ?? '',
        $_REQUEST['locale'] ?? ''
    );
    wp_send_json(['hits' => json_decode(json_encode($posts), true), 'count' => $count]);
}

function wp_bonnier_redirect_setup_admin_menu() {
    add_management_page('Bonnier Redirects', 'Bonnier Redirects', 'edit_others_pages', 'bonnier_redirects', __NAMESPACE__ . '\wp_bonnier_redirect_options_page');
}

//function wp_bonnier_redirect_setup_admin_head() {
//    wp_enqueue_script('admin-forms');
//}

//add_action('admin_head', 'wp_bonnier_redirect_setup_admin_head');
add_action('admin_menu', __NAMESPACE__ . '\wp_bonnier_redirect_setup_admin_menu');

add_action( 'wp_ajax_bonnier_redirects', __NAMESPACE__ .'\bonnier_redirects_admin_rows' );

add_action('plugins_loaded', __NAMESPACE__ . '\instance', 0);