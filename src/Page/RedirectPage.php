<?php

namespace Bonnier\WP\Redirect\Page;

use Bonnier\WP\Redirect\Http\BonnierRedirect;
use Bonnier\WP\Redirect\WpBonnierRedirect;

class RedirectPage
{
    public static function register()
    {
        add_action('admin_menu', [__CLASS__, 'wpBonnierRedirectSetupAdminMenu']);
        add_action('wp_ajax_bonnier_redirects', [__CLASS__, 'bonnierRedirectsAdminRows']);
        add_action('wp_ajax_bonnier_redirect_add', [__CLASS__, 'bonnierRedirectsAdminAddRow']);
        add_action('wp_ajax_bonnier_redirect_delete', [__CLASS__, 'bonnierRedirectsAdminDeleteRow']);
    }

    public static function bonnierRedirectsAdminAddRow()
    {
        if (isset(
            $_REQUEST['from'],
            $_REQUEST['to'],
            $_REQUEST['locale'],
            $_REQUEST['type'],
            $_REQUEST['id'],
            $_REQUEST['code']
        )) {
            $response = BonnierRedirect::createRedirect(
                $_REQUEST['from'] ?? '',
                $_REQUEST['to'] ?? '',
                $_REQUEST['locale'] ?? '',
                $_REQUEST['type'] ?? '',
                $_REQUEST['id'] ?? '',
                $_REQUEST['code'] ?? ''
            );
            if ($response['success']) {
                wp_send_json_success(['message' => $response['message']]);
            } else {
                wp_send_json_error(['message' => $response['message']]);
            }
        }
        wp_send_json_error(['message' => 'Could not create redirect - make sure all settings has a value.']);
    }

    public static function bonnierRedirectsAdminDeleteRow()
    {
        if (isset($_REQUEST['id'])) {
            $response = BonnierRedirect::deleteRedirect(
                $_REQUEST['id'] ?? ''
            );
            if ($response) {
                wp_send_json(true);
            }
        }
        wp_send_json_error(false);
    }

    /**
     * Function to fetch rows of redirects
     */
    public static function bonnierRedirectsAdminRows()
    {
        list($posts, $count) = BonnierRedirect::paginateFetchRedirect(
            $_REQUEST['page_number'] ?? 1,
            $_REQUEST['to'] ?? '',
            $_REQUEST['from'] ?? '',
            $_REQUEST['locale'] ?? ''
        );
        wp_send_json(['hits' => json_decode(json_encode($posts), true), 'count' => $count]);
    }

    public static function wpBonnierRedirectSetupAdminMenu()
    {
        add_management_page(
            'Bonnier Redirects',
            'Bonnier Redirects',
            'edit_others_pages',
            'bonnier_redirects',
            [__CLASS__, 'wpBonnierRedirectOptionsPage']
        );
    }

    public static function wpBonnierRedirectOptionsPage()
    {
        wp_enqueue_script(
            'vue',
            plugin_dir_url(WpBonnierRedirect::instance()->file) . 'assets/vue.min.js'
        );
        wp_enqueue_script(
            'vue-resource',
            plugin_dir_url(WpBonnierRedirect::instance()->file) . 'assets/vue-resource.min.js'
        );
        wp_enqueue_script(
            'vue-paginate',
            plugin_dir_url(WpBonnierRedirect::instance()->file) . 'assets/vue-paginate.js'
        );
        wp_enqueue_script(
            'lodash',
            plugin_dir_url(WpBonnierRedirect::instance()->file) . 'assets/lodash.min.js'
        );
        ?>

        <script>
            window.onload = function () {
                Vue.component('modal', {
                    template: '#modal-template'
                });

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
                        isCalculating: false,
                        showModal: false,
                        status: '',
                        alert: '',
                        newRedirect: {
                            to: '',
                            from: '',
                            locale: '',
                            type: 'manual',
                            id: 0,
                            code: 301
                        }
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
                        },
                        statusClass: function () {
                            return 'alert-' + this.alert;
                        }
                    },
                    methods: {
                        submitNewRedirect: this._.debounce(function () {
                            setTimeout(function () {
                                this.$http.post(ajaxurl,
                                    'action=bonnier_redirect_add'
                                    + '&to=' + encodeURIComponent(this.newRedirect.to)
                                    + '&from=' + encodeURIComponent(this.newRedirect.from)
                                    + '&locale=' + this.newRedirect.locale
                                    + '&type=' + this.newRedirect.type
                                    + '&id=' + this.newRedirect.id
                                    + '&code=' + this.newRedirect.code,
                                    {
                                        'headers': { 'Content-Type': 'application/x-www-form-urlencoded' }
                                    }
                                ).then(function (data, status, request) {
                                    if(data.body.success === false) {
                                      this.alert = 'failed';
                                    } else {
                                        this.newRedirect = {
                                            to: '',
                                            from: '',
                                            locale: '',
                                            type: 'manual',
                                            id: 0,
                                            code: 301
                                        }
                                      this.alert = 'success';
                                    }
                                  this.status = data.body.data.message;
                                }, function (data, status, request) {
                                    this.status = 'failed';
                                });
                            }.bind(this), 1000)
                        }, 500),
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
                        deleteResource: function (id) {
                            var result = confirm("Sure you want to delete?" + id);
                            if (result) {
                                this.$http.post(ajaxurl,
                                    'action=bonnier_redirect_delete&id=' + id,
                                    {
                                        'headers': { 'Content-Type': 'application/x-www-form-urlencoded' }
                                    }
                                ).then(function (data, status, request) {
                                    this.updateResource();
                                });
                            }
                        }
                    }
                });
            }
        </script>
        <div id="app" v-cloak="true">
            <div w3-include-html="views/modal.html"></div>
            <div class="wrap">
                <h2><?php _e('Bonnier Redirects', 'bonnier-redirects') ?></h2>
                <div v-on:keyup="updateResource(); updatePage(1)">
                    <span>From: </span> <input type="text" placeholder="Filter From" v-model="from">
                    <span>To: </span> <input type="text" placeholder="Filter To" v-model="to">
                    <span>Locale: </span> <input type="text" placeholder="Filter Locale" v-model="locale">
                    <span>Id: </span> <input type="text" placeholder="Filter Id" v-model="id">
                </div>

                <button id="show-modal" @click="showModal = true">Add Redirect</button>

                <br class="clear" />
                <table class="widefat">
                    <thead>
                    <tr>
                        <th scope="col"><?php _e('From', 'bonnier-redirects') ?></th>
                        <th scope="col"><?php _e('To', 'bonnier-redirects') ?></th>
                        <th scope="col"><?php _e('Locale', 'bonnier-redirects') ?></th>
                        <th scope="col"><?php _e('Type', 'bonnier-redirects') ?></th>
                        <th scope="col"><?php _e('Id', 'bonnier-redirects') ?></th>
                        <th scope="col"><?php _e('Code', 'bonnier-redirects') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr valign="top" v-for="redirect in redirects">
                        <td>{{redirect.from}}</td>
                        <td>{{redirect.to}}</td>
                        <td>{{redirect.locale}}</td>
                        <td>{{redirect.type}}</td>
                        <td>{{redirect.id}}</td>
                        <td>{{redirect.code}}</td>
                        <td><button @click="deleteResource(redirect.id)">Delete</button></td>
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

            <!-- MODAL HERE -->
            <script type="text/x-template" id="modal-template">
                <transition name="modal">
                    <div class="modal-mask">
                        <div class="modal-wrapper">
                            <div class="modal-container">
                                <div class="modal-header">
                                    <slot name="header">
                                        default header
                                    </slot>
                                </div>

                                <div class="modal-body">
                                    <slot name="body">
                                        default body
                                    </slot>
                                </div>

                                <div class="modal-footer">
                                    <slot name="footer">
                                        <button class="modal-default-button" @click="$emit('close')">
                                            Close
                                        </button>
                                    </slot>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </script>

            <!-- use the modal component, pass in the prop -->
            <modal v-if="showModal" @close="showModal = false">
                <!--
                  you can use custom content here to overwrite
                  default content
                -->
                <h3 slot="header">Add Redirects</h3>
                <div slot="body">
                    <span>From: </span>
                    <br/>
                    <input type="text" placeholder="Filter From" v-model="newRedirect.from">
                    </br><br/>
                    <span>To: </span>
                    <br/>
                    <input type="text" placeholder="Filter To" v-model="newRedirect.to">
                    </br><br/>
                    <span>Locale: </span>
                    <br/>
                    <input type="text" placeholder="Locale" v-model="newRedirect.locale">
                    </br><br/>
                    <span>Type: </span>
                    <br/>
                    <input disabled type="text" placeholder="Type" v-model="newRedirect.type">
                    </br><br/>
                    <span>Id: </span>
                    <br/>
                    <input disabled type="text" placeholder="Id" v-model="newRedirect.id">
                    </br><br/>
                    <span>Code: </span>
                    <br/>
                    <input disabled type="text" placeholder="Code" v-model="newRedirect.code">
                    </br><br/>
                    <div v-if="status" class="alert fade in" v-bind:class="statusClass">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close" @click="status = ''">×</a>
                        <strong>{{status}}</strong>
                    </div>
                </div>
                <div slot="footer">
                    <button @click="submitNewRedirect">Submit</button>
                    <button class="modal-default-button" @click="showModal = false">Close</button>
                </div>
            </modal>
        </div>
        <style>
            [v-cloak] {
                display: none;
            }
            .pagination{height:36px;margin:0;padding: 0;}
            .pager,.pagination ul{margin-left:0;*zoom:1}
            .pagination ul {
                padding:0;
                display:inline-block;
                *display:inline;
                margin-bottom:0;
                -webkit-border-radius:3px;
                -moz-border-radius:3px;
                border-radius:3px;
                -webkit-box-shadow:0 1px 2px rgba(0,0,0,.05);
                -moz-box-shadow:0 1px 2px rgba(0,0,0,.05);
                box-shadow:0 1px 2px rgba(0,0,0,.05)
            }
            .pagination li{display:inline}
            .pagination a {
                float:left;
                padding:0 12px;
                line-height:30px;
                text-decoration:none;
                border:1px solid #ddd;
                border-left-width:0
            }
            .pagination .active a,.pagination a:hover{background-color:#f5f5f5;color:#94999E}
            .pagination .active a{color:#94999E;cursor:default}
            .pagination .disabled a,.pagination .disabled a:hover,.pagination .disabled span {
                color:#94999E;
                background-color:transparent;
                cursor:default
            }
            .pagination li:first-child a,.pagination li:first-child span {
                border-left-width:1px;
                -webkit-border-radius:3px 0 0 3px;
                -moz-border-radius:3px 0 0 3px;
                border-radius:3px 0 0 3px
            }
            .pagination li:last-child a {
                -webkit-border-radius:0 3px 3px 0;
                -moz-border-radius:0 3px 3px 0;
                border-radius:0 3px 3px 0
            }
            .pagination-centered{text-align:center}
            .pagination-right{text-align:right}
            .pager{margin-bottom:18px;text-align:center}
            .pager:after,.pager:before{display:table;content:""}
            .pager li{display:inline}
            .pager a {
                display:inline-block;
                padding:5px 12px;
                background-color:#fff;
                border:1px solid #ddd;
                -webkit-border-radius:15px;
                -moz-border-radius:15px;
                border-radius:15px
            }
            .pager a:hover{text-decoration:none;background-color:#f5f5f5}
            .pager .next a{float:right}
            .pager .previous a{float:left}
            .pager .disabled a,.pager .disabled a:hover{color:#999;background-color:#fff;cursor:default}
            .pagination .prev.disabled span {
                float:left;
                padding:0 12px;
                line-height:30px;
                text-decoration:none;
                border:1px solid #ddd;
                border-left-width:1
            }
            .pagination .next.disabled span {
                float:left;
                padding:0 12px;
                line-height:30px;
                text-decoration:none;
                border:1px solid #ddd;
                border-left-width:0
            }
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
            .modal-mask {
                position:fixed;
                z-index:9998;
                top:0;
                left:0;
                width:100%;
                height:100%;
                background-color:rgba(0,0,0,.5);
                display:table;
                transition:opacity .3s ease
            }
            .modal-wrapper{display:table-cell;vertical-align:middle}
            .modal-container {
                width:300px;
                margin:0 auto;
                padding:20px 30px;
                background-color:#fff;
                border-radius:2px;
                box-shadow:0 2px 8px rgba(0,0,0,.33);
                transition:all .3s ease;
                font-family:Helvetica,Arial,sans-serif
            }
            .modal-header h3{margin-top:0;color:#42b983}
            .modal-body{margin:20px 0}
            .modal-default-button{float:right}
            .modal-enter,.modal-leave-active{opacity:0}
            .modal-enter .modal-container, .modal-leave-active .modal-container {
                -webkit-transform:scale(1.1);
                transform:scale(1.1)
            }
            .fade.in {
                opacity: 1;
            }
            .alert-success {
                color: #3c763d;
                background-color: #dff0d8;
                border-color: #d6e9c6;
            }
            .alert-failed {
                color: #a94442;
                background-color: #f2dede;
                border-color: #ebccd1;
            }
            .alert {
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
            }
            .fade {
                opacity: 0;
                -webkit-transition: opacity .15s linear;
                -o-transition: opacity .15s linear;
                transition: opacity .15s linear;
            }
            .close {
                float: right;
                font-size: 21px;
                font-weight: 700;
                line-height: 1;
                color: #000;
                text-shadow: 0 1px 0 #fff;
                filter: alpha(opacity=20);
                opacity: .2;
            }
            a {
                color: #337ab7;
                text-decoration: none;
            }
            a {
                background-color: transparent;
            }

        </style>
        <?php
    }
}
