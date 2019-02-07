<?php

namespace Bonnier\WP\Redirect\Models;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Http\Request;
use Illuminate\Contracts\Support\Arrayable;

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

    public function __construct(?int $redirectID = null)
    {
        if ($redirectID) {
            if (!$redirect = DB::getRedirect($redirectID)) {
                throw new \RuntimeException(sprintf('A redirect with id \'%s\' does not exist!', $redirectID));
            }
            $this->redirectID = intval(data_get($redirect, 'id', 0));
            $this->from = data_get($redirect, 'from');
            $this->fromHash = data_get($redirect, 'from_hash');
            $this->destination = data_get($redirect, 'to');
            $this->toHash = data_get($redirect, 'to_hash');
            $this->locale = data_get($redirect, 'locale');
            $this->type = data_get($redirect, 'type');
            $this->wpID = intval(data_get($redirect, 'wp_id', 0));
            $this->setCode(intval(data_get($redirect, 'code')));
            $this->paramlessFromHash = data_get($redirect, 'paramless_from_hash');
        } else {
            $this->redirectID = 0;
            $this->type = '';
            $this->wpID = 0;
            $this->code = Request::HTTP_PERMANENT_REDIRECT;
        }
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
        $this->from = $from;
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
        $this->destination = $destination;
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
            Request::HTTP_PERMANENT_REDIRECT,
            Request::HTTP_TEMPORARY_REDIRECT,
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
        $this->from = array_get($data, 'from');
        $this->fromHash = array_get($data, 'from_hash');
        $this->destination = array_get($data, 'to');
        $this->toHash = array_get($data, 'to_hash');
        $this->locale = array_get($data, 'locale');
        $this->type = array_get($data, 'type');
        $this->wpID = intval(array_get($data, 'wp_id', 0));
        $this->setCode(intval(array_get($data, 'code')));
        $this->paramlessFromHash = array_get($data, 'paramless_from_hash');

        return $this;
    }
}
