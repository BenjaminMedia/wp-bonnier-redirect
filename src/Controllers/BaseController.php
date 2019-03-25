<?php

namespace Bonnier\WP\Redirect\Controllers;

use Bonnier\WP\Redirect\Repositories\RedirectRepository;
use Symfony\Component\HttpFoundation\Request;

class BaseController
{
    protected $redirectRepository;
    protected $request;
    protected $notices = [];
    protected $validationErrors = [];

    public function __construct(RedirectRepository $redirectRepository, Request $request)
    {
        $this->redirectRepository = $redirectRepository;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getNotices(): array
    {
        return $this->notices;
    }

    /**
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @param string $field
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->validationErrors[$field] ?? null;
    }

    /**
     * @param string $message
     * @param string $type
     */
    protected function addNotice(string $message, string $type = 'error')
    {
        $this->notices[] = [$type => $message];
    }
}
