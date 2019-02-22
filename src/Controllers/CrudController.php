<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Database\Bootstrap;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\WpBonnierRedirect;
use Symfony\Component\HttpFoundation\Request;

class CrudController
{
    /** @var RedirectRepository */
    private $redirects;
    /** @var Request */
    private $request;

    /** @var Redirect */
    private $redirect;

    private $notices = [];

    public function __construct(RedirectRepository $redirectRepository, Request $request)
    {
        $this->redirects = $redirectRepository;
        $this->request = $request;
        if ($redirectID = $this->request->query->get('redirect_id')) {
            if ($redirect = $this->redirects->getRedirectById($redirectID)) {
                $this->redirect = $redirect;
            }
        } else {
            $this->redirect = new Redirect();
            $this->redirect->setLocale(LocaleHelper::getLanguage());
        }
    }

    public function displayAddRedirectPage()
    {
        $redirect = $this->redirect;
        $languages = LocaleHelper::getLanguages();

        include_once(WpBonnierRedirect::instance()->getViewPath('addRedirect.php'));
    }

    public function handlePost()
    {
        if ($this->request->isMethod(Request::METHOD_POST)) {
            if ($this->request->request->get('redirect_id')) {
                $this->updateRedirect();
            } else {
                $this->createRedirect();
            }
        }
    }

    public function registerScripts()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_register_script(
                'bonnier_redirect_manage_page_script',
                WpBonnierRedirect::instance()->assetURI('scripts/crud.js'),
                false,
                WpBonnierRedirect::instance()->assetVersion('scripts/crud.js'),
                true
            );
            wp_register_style(
                'bonnier_redirect_manage_page_style',
                WpBonnierRedirect::instance()->assetURI('styles/crud.css'),
                false,
                WpBonnierRedirect::instance()->assetVersion('styles/crud.css')
            );
            wp_enqueue_style('bonnier_redirect_manage_page_style');
            wp_enqueue_script('bonnier_redirect_manage_page_script');
        });
    }

    public function getNotices(): array
    {
        if ($notices = $this->notices) {
            return $notices;
        }

        return [];
    }

    private function updateRedirect()
    {

    }

    private function createRedirect()
    {
        $this->redirect = new Redirect();
        $this->redirect->fromArray([
            'from' => $this->request->request->get('redirect_from'),
            'to' => $this->request->request->get('redirect_to'),
            'locale' => $this->request->request->get('redirect_locale'),
            'type' => 'manual',
            'code' => $this->request->request->get('redirect_code'),
        ]);
        try {
            $this->redirects->save($this->redirect);
        } catch (DuplicateEntryException $exception) {
            $this->addNotice(function () use ($exception) {
                ?>
                <div id="message" class="notice notice-error is-dismissible">
                    <p>
                        <strong>Error:</strong>
                        <?php echo $exception->getMessage(); ?>
                    </p>
                </div>
                <?php
            });
        }
    }

    private function addNotice(callable $notice)
    {
        $this->notices[] = $notice;
    }
}
