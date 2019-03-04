<?php

namespace Bonnier\WP\Redirect\Models;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\HttpFoundation\Response;

class Redirect implements Arrayable
{
    /** @var int */
    private $redirectID;
    /** @var string|null */
    private $from;
    /** @var string */
    private $fromHash;
    /** @var string|null */
    private $destination;
    /** @var string */
    private $toHash;
    /** @var string|null */
    private $locale;
    /** @var string */
    private $type;
    /** @var int */
    private $wpID;
    /** @var int */
    private $code;
    /** @var string */
    private $paramlessFromHash;
    /** @var boolean */
    private $keepQuery;

    public function __construct()
    {
        $this->redirectID = 0;
        $this->type = '';
        $this->wpID = 0;
        $this->code = Response::HTTP_MOVED_PERMANENTLY;
        $this->keepQuery = false;
    }

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->redirectID;
    }

    /**
     * @param int $redirectID
     * @return Redirect
     */
    public function setID(int $redirectID): Redirect
    {
        $this->redirectID = $redirectID;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string $from
     * @return Redirect
     */
    public function setFrom(string $from): Redirect
    {
        $this->from = UrlHelper::normalizePath($from);
        $this->fromHash = hash('md5', $this->from);
        $this->paramlessFromHash = hash('md5', parse_url($this->from, PHP_URL_PATH));
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFromHash(): ?string
    {
        if ($fromHash = $this->fromHash) {
            return $fromHash;
        }

        if ($from = $this->getFrom()) {
            $this->fromHash = hash('md5', $from);
            return $this->fromHash;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     * @return Redirect
     */
    public function setTo(string $destination): Redirect
    {
        $this->destination = UrlHelper::normalizeUrl($destination, true);
        $this->toHash = hash('md5', $this->destination);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getToHash(): ?string
    {
        if ($toHash = $this->toHash) {
            return $toHash;
        }

        if ($destination = $this->getTo()) {
            $this->toHash = hash('md5', $destination);
            return $this->toHash;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return Redirect
     */
    public function setLocale(string $locale): Redirect
    {
        if (strlen($locale) !== 2) {
            throw new \InvalidArgumentException(sprintf('The locale \'%s\' is not valid!', $locale));
        }
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Redirect
     */
    public function setType(string $type): Redirect
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getWpID(): int
    {
        return $this->wpID;
    }

    /**
     * @param int $wpID
     * @return Redirect
     */
    public function setWpID(int $wpID): Redirect
    {
        $this->wpID = $wpID;
        return $this;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return Redirect
     */
    public function setCode(int $code): Redirect
    {
        if (!in_array($code, [
            Response::HTTP_MOVED_PERMANENTLY,
            Response::HTTP_FOUND,
        ])) {
            throw new \InvalidArgumentException(
                sprintf('Code \'%s\' is not a valid redirect response code!', $code)
            );
        }
        $this->code = $code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getParamlessFromHash(): string
    {
        if ($paramlessFromHash = $this->paramlessFromHash) {
            return $paramlessFromHash;
        }

        if ($from = $this->getFrom()) {
            $this->paramlessFromHash = hash('md5', parse_url($from, PHP_URL_PATH));
            return $this->paramlessFromHash;
        }

        return null;
    }

    public function keepsQuery(): bool
    {
        return $this->keepQuery;
    }

    public function setKeepQuery(bool $keepQuery): Redirect
    {
        $this->keepQuery = $keepQuery;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getID(),
            'from' => $this->getFrom(),
            'from_hash' => $this->getFromHash(),
            'to' => $this->getTo(),
            'to_hash' => $this->getToHash(),
            'locale' => $this->getLocale(),
            'type' => $this->getType(),
            'wp_id' => $this->getWpID(),
            'code' => $this->getCode(),
            'paramless_from_hash' => $this->getParamlessFromHash(),
            'keep_query' => $this->keepsQuery() ? 1 : 0,
        ];
    }

    /**
     * @param array $data
     *
     * @return Redirect
     */
    public function fromArray(array $data): Redirect
    {
        $this->redirectID = intval(array_get($data, 'id', 0));
        $this->setFrom(array_get($data, 'from'));
        if ($fromHash = array_get($data, 'from_hash')) {
            $this->fromHash = $fromHash;
        }
        $this->setTo(array_get($data, 'to'));
        if ($toHash = array_get($data, 'to_hash')) {
            $this->toHash = $toHash;
        }
        $this->setLocale(array_get($data, 'locale'));
        $this->setType(array_get($data, 'type', ''));
        $this->setWpID(intval(array_get($data, 'wp_id', 0)));
        $this->setCode(intval(array_get($data, 'code')));
        if ($paramlessFromHash = array_get($data, 'paramless_from_hash')) {
            $this->paramlessFromHash = $paramlessFromHash;
        }
        $this->setKeepQuery(boolval(array_get($data, 'keep_query')));

        return $this;
    }
}
