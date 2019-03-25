<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Exceptions\IdenticalFromToException;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\WpBonnierRedirect;
use Symfony\Component\HttpFoundation\Request;

class CrudController extends BaseController
{
    /** @var Redirect */
    private $redirect;

    public function __construct(RedirectRepository $redirectRepository, Request $request)
    {
        parent::__construct($redirectRepository, $request);
        if ($redirectID = $this->request->query->get('redirect_id')) {
            if ($redirect = $this->redirectRepository->getRedirectById($redirectID)) {
                $this->redirect = $redirect;
            }
        } else {
            $this->redirect = new Redirect();
        }
    }

    public function displayAddRedirectPage()
    {
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

    /**
     * @return Redirect
     */
    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    private function updateRedirect()
    {
        if (!$this->validateRequest()) {
            $this->addNotice('Invalid data was submitted - fix fields marked with red.');
            return;
        }
        if (($redirectID = $this->request->request->get('redirect_id')) &&
            $redirect = $this->redirectRepository->getRedirectById($redirectID)
        ) {
            $redirectFrom = $this->request->request->get('redirect_from');
            $redirectTo = $this->request->request->get('redirect_to');
            $redirectLocale = $this->request->request->get('redirect_locale');
            $redirectCode = $this->request->request->get('redirect_code');
            $redirectKeepQuery = boolval($this->request->request->get('redirect_keep_query'));
            if ($redirect->getFrom() !== $redirectFrom) {
                $redirect->setFrom($redirectFrom);
            }
            if ($redirect->getTo() !== $redirectTo) {
                $redirect->setTo($redirectTo);
            }
            $redirect->setLocale($redirectLocale);
            $redirect->setCode($redirectCode);
            $redirect->setKeepQuery($redirectKeepQuery);
            $this->redirect = $redirect;
            $this->saveRedirect($redirect);
        }
    }

    private function createRedirect()
    {
        $redirect = new Redirect();
        $redirect->fromArray([
            'from' => $this->request->request->get('redirect_from'),
            'to' => $this->request->request->get('redirect_to'),
            'locale' => $this->request->request->get('redirect_locale'),
            'type' => 'manual',
            'code' => $this->request->request->get('redirect_code'),
            'keep_query' => $this->request->request->get('redirect_keep_query') ? 1 : 0,
        ]);
        if (!$this->validateRequest()) {
            $this->addNotice('Invalid data was submitted - fix fields marked with red.');
            $this->redirect = $redirect;
            return;
        }
        $this->redirect = $redirect;
        $this->saveRedirect($redirect);
    }

    /**
     * @param Redirect $redirect
     */
    private function saveRedirect(Redirect $redirect)
    {
        try {
            $toHash = $redirect->getToHash();
            $redirect = $this->redirectRepository->save($redirect);
            $editRedirectLink = esc_url(add_query_arg([
                'page' => 'add-redirect',
                'action' => 'edit',
                'redirect_id' => $redirect->getID(),
            ], admin_url('admin.php')));
            $this->addNotice(
                sprintf('The redirect was saved! <a href="%s">View redirect</a>', $editRedirectLink),
                'success'
            );
            if ($redirects = $this->redirectRepository->findAllBy('from_hash', $toHash)) {
                $redirects->each(function (Redirect $existingRedirect) use ($redirect) {
                    if ($existingRedirect->getLocale() === $redirect->getLocale()) {
                        $editRedirectLink = esc_url(add_query_arg([
                            'page' => 'add-redirect',
                            'action' => 'edit',
                            'redirect_id' => $existingRedirect->getID(),
                        ], admin_url('admin.php')));
                        $this->addNotice(
                            sprintf(
                                'The redirect was chaining, and its \'to\'-url has been updated!
                                        <a href="%s">View the other redirect</a>',
                                $editRedirectLink
                            ),
                            'warning'
                        );
                    }
                });
            }
            $this->redirect = new Redirect();
        } catch (IdenticalFromToException $exception) {
            $this->addNotice('From and to urls seems identical!');
        } catch (DuplicateEntryException $exception) {
            $this->addNotice('A redirect with the same \'from\' and \'locale\' already exists!');
        } catch (\Exception $exception) {
            $this->addNotice(sprintf('An error occured, creating the redirect (%s)', $exception->getMessage()));
        }
    }

    /**
     * @return bool
     */
    private function validateRequest(): bool
    {
        $validRequest = true;
        $redirectFrom = $this->request->request->get('redirect_from');
        if (empty($redirectFrom)) {
            $this->validationErrors['redirect_from'] = 'The \'from\'-value cannot be empty!';
            $validRequest = false;
        } elseif ($redirectFrom === '/*' || $redirectFrom === '*') {
            $this->validationErrors['redirect_from'] = 'You cannot create this destructive wildcard redirect!';
            $validRequest = false;
        } elseif ($redirectFrom === '/') {
            $this->validationErrors['redirect_from'] = 'You cannot create a redirect from the frontpage!';
            $validRequest = false;
        }
        if (empty($this->request->request->get('redirect_to'))) {
            $this->validationErrors['redirect_to'] = 'The \'to\'-value cannot be empty!';
            $validRequest = false;
        }

        if (!in_array($this->request->request->get('redirect_locale'), LocaleHelper::getLanguages())) {
            $this->validationErrors['redirect_locale'] = 'You have to specify a language for the redirect';
            $validRequest = false;
        }

        return $validRequest;
    }
}
