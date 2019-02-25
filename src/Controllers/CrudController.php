<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Models\Redirect;
use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Bonnier\WP\Redirect\WpBonnierRedirect;
use Symfony\Component\HttpFoundation\ParameterBag;
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
    private $validationErrors = [];

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
        return $this->notices;
    }

    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getError(string $field): ?string
    {
        return $this->validationErrors[$field] ?? null;
    }

    private function updateRedirect()
    {
        if (!$this->validateRequest()) {
            $this->addNotice('Invalid data was submitted - fix fields marked with red.');
            return;
        }
        if (($redirectID = $this->request->request->get('redirect_id')) &&
            $redirect = $this->redirects->getRedirectById($redirectID)
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

            try {
                $this->redirects->save($redirect);
                $this->addNotice('Redirect updated!', 'success');
            } catch (DuplicateEntryException $exception) {
                $this->addNotice('A redirect with the same \'from\' and \'locale\' already exists!');
            } catch (\Exception $exception) {
                $this->addNotice(sprintf('Unable to update redirect! (%s)', $exception->getMessage()));
            }
        }
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
            'keep_query' => $this->request->request->get('redirect_keep_query') ? 1 : 0,
        ]);
        if (!$this->validateRequest()) {
            $this->addNotice('Invalid data was submitted - fix fields marked with red.');
            return;
        }
        try {
            $this->redirects->save($this->redirect);
            $this->addNotice('The redirect was saved!', 'success');
        } catch (DuplicateEntryException $exception) {
            $this->addNotice('A redirect with the same \'from\' and \'locale\' already exists!');
        } catch (\Exception $exception) {
            $this->addNotice(sprintf('An error occured, creating the redirect (%s)', $exception->getMessage()));
        }
    }

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

        return $validRequest;
    }

    private function addNotice(string $message, string $type = 'error')
    {
        $this->notices[] = [$type => $message];
    }
}
