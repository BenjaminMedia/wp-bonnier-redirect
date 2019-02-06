<?php

namespace Bonnier\WP\Redirect\Models;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
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
            return hash('md5', $from);
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
            return hash('md5', $destination);
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
            return hash('md5', parse_url($from, PHP_URL_PATH));
        }

        return null;
    }

    /**
     * @throws \Exception
     * @throws DuplicateEntryException
     *
     * @return Redirect
     */
    public function save()
    {
        $data = [
            'from' => $this->getFrom(),
            'from_hash' => $this->getFromHash(),
            'paramless_from_hash' => $this->getParamlessFromHash(),
            'to' => $this->getTo(),
            'to_hash' => $this->getToHash(),
            'locale' => $this->getLocale(),
            'type' => $this->getType(),
            'wp_id' => $this->getWpID(),
            'code' => $this->getCode(),
        ];

        if ($redirectId = $this->getID()) {
            DB::update($redirectId, $data);
        } else {
            $this->redirectID = DB::insert($data);
        }

        return $this;
    }

    /**
     * @throws
     *
     * @return bool
     */
    public function delete()
    {
        return DB::delete($this->getID()) !== false;
    }

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
}
