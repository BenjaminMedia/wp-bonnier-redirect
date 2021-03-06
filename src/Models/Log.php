<?php

namespace Bonnier\WP\Redirect\Models;

use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class Log implements Arrayable
{
    /** @var int */
    private $logID;
    /** @var string|null */
    private $slug;
    /** @var string|null */
    private $hash;
    /** @var string|null */
    private $type;
    /** @var int */
    private $wpID;
    /** @var \DateTime */
    private $createdAt;

    public function __construct()
    {
        $this->logID = 0;
        $this->wpID = 0;
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->logID;
    }

    /**
     * @param int $logID
     * @return Log
     */
    public function setID(int $logID): Log
    {
        $this->logID = $logID;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     * @return Log
     */
    public function setSlug(?string $slug): Log
    {
        $this->slug = UrlHelper::normalizePath($slug);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        if ($this->hash) {
            return $this->hash;
        }

        if ($this->slug) {
            $this->hash = hash('md5', $this->slug);
            return $this->hash;
        }

        return null;
    }

    /**
     * @param string|null $hash
     * @return Log
     */
    public function setHash(?string $hash): Log
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return Log
     */
    public function setType(?string $type): Log
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
     * @return Log
     */
    public function setWpID(int $wpID): Log
    {
        $this->wpID = $wpID;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Log
     */
    public function setCreatedAt(\DateTime $createdAt): Log
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getID(),
            'slug' => $this->getSlug(),
            'hash' => $this->getHash(),
            'type' => $this->getType(),
            'wp_id' => $this->getWpID(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function fromArray(array $data): Log
    {
        $this->logID = Arr::get($data, 'id', 0);
        $this->slug = Arr::get($data, 'slug');
        $this->hash = Arr::get($data, 'hash');
        $this->type = Arr::get($data, 'type');
        $this->wpID = intval(Arr::get($data, 'wp_id'));
        if ($createdAt = Arr::get($data, 'created_at')) {
            $this->createdAt = new \DateTime($createdAt);
        }

        return $this;
    }
}
