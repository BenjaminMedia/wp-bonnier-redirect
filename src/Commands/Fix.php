<?php

namespace Bonnier\WP\Redirect\Commands;

class Fix {
    private $id;
    private $from;
    private $to;
    private $fromUrl;
    private $toUrl;
    private $fromCode;
    private $toCode;
    private $language;
    private $type;
    private $wpId;
    private $wpIdPostLink;
    private $wpIdPostStatus;
    private $toCategoryLink;
    private $toPageByPathLink;
    private $action;
    private $reason;
    private $field;
    private $newValue;

    public function __construct()
    {
        $this->id = null;
        $this->from = null;
        $this->to = null;
        $this->fromUrl = null;
        $this->toUrl = null;
        $this->fromCode = null;
        $this->toCode = null;
        $this->language = null;
        $this->type = null;
        $this->wpId = null;
        $this->wpIdPostLink = null;
        $this->wpIdPostStatus = null;
        $this->toCategoryLink = null;
        $this->toPageByPathLink = null;
        $this->action = null;
        $this->reason = null;
        $this->field = null;
        $this->newValue = null;
    }

/*
    public function from($val=null): string
    {
        if (isset($val)) {
            $this->from = $val;
        }
        return $this->from;
    }

    public function to($val=null): string
    {
        if (isset($val)) {
            $this->to = $val;
        }
        return $this->to;
    }

    public function language($val=null): string
    {
        if (isset($val)) {
            $this->language = $val;
        }
        return $this->language;
    }
 */
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param null $from
     */
    public function setFrom($from): void
    {
        $this->from = $from;
    }

    /**
     * @return null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param null $to
     */
    public function setTo($to): void
    {
        $this->to = $to;
    }

    /**
     * @return mixed
     */
    public function getFromUrl()
    {
        return $this->fromUrl;
    }

    /**
     * @param mixed $fromUrl
     */
    public function setFromUrl($fromUrl): void
    {
        $this->fromUrl = $fromUrl;
    }

    /**
     * @return mixed
     */
    public function getToUrl()
    {
        return $this->toUrl;
    }

    /**
     * @param mixed $toUrl
     */
    public function setToUrl($toUrl): void
    {
        $this->toUrl = $toUrl;
    }

    /**
     * @return null
     */
    public function getFromCode()
    {
        return $this->fromCode;
    }

    /**
     * @param null $fromCode
     */
    public function setFromCode($fromCode): void
    {
        $this->fromCode = $fromCode;
    }

    /**
     * @return null
     */
    public function getToCode()
    {
        return $this->toCode;
    }

    /**
     * @param null $toCode
     */
    public function setToCode($toCode): void
    {
        $this->toCode = $toCode;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getWpId()
    {
        return $this->wpId;
    }

    /**
     * @param mixed $wpId
     */
    public function setWpId($wpId): void
    {
        $this->wpId = $wpId;
    }

    /**
     * @return mixed
     */
    public function getWpIdPostLink()
    {
        return $this->wpIdPostLink;
    }

    /**
     * @param mixed $wpIdPostLink
     */
    public function setWpIdPostLink($wpIdPostLink): void
    {
        $this->wpIdPostLink = $wpIdPostLink;
    }

    /**
     * @return mixed
     */
    public function getWpIdPostStatus()
    {
        return $this->wpIdPostStatus;
    }

    /**
     * @param mixed $wpIdPostStatus
     */
    public function setWpIdPostStatus($wpIdPostStatus): void
    {
        $this->wpIdPostStatus = $wpIdPostStatus;
    }

    /**
     * @return mixed
     */
    public function getToCategoryLink()
    {
        return $this->toCategoryLink;
    }

    /**
     * @param mixed $toCategoryLink
     */
    public function setToCategoryLink($toCategoryLink): void
    {
        $this->toCategoryLink = $toCategoryLink;
    }

    /**
     * @return null
     */
    public function getToPageByPathLink()
    {
        return $this->toPageByPathLink;
    }

    /**
     * @param null $toPageByPathLink
     */
    public function setToPageByPathLink($toPageByPathLink): void
    {
        $this->toPageByPathLink = $toPageByPathLink;
    }

    /**
     * @return null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param null $action
     */
    public function setAction($action): void
    {
        $this->action = $action;
    }

    /**
     * @return null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param null $reason
     */
    public function setReason($reason): void
    {
        $this->reason = $reason;
    }

    /**
     * @return null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param null $field
     */
    public function setField($field): void
    {
        $this->field = $field;
    }

    /**
     * @return null
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @param null $newValue
     */
    public function setNewValue($newValue): void
    {
        $this->newValue = $newValue;
    }

    public function outputBlock()
    {
        echo 'Redirect id:      ' . $this->getId() . PHP_EOL;
        echo 'language:         ' . $this->getLanguage() . PHP_EOL;
        echo 'Type:             ' . $this->getType() . PHP_EOL;
        echo 'From:             ' . $this->getFrom() . PHP_EOL;
        echo 'To:               ' . $this->getTo() . PHP_EOL;
        echo 'From url:         ' . $this->getFromUrl() . PHP_EOL;
        echo 'To url:           ' . $this->getToUrl() . PHP_EOL;
        echo 'From code:        ' . $this->getFromCode() . PHP_EOL;
        echo 'To code:          ' . $this->getToCode() . PHP_EOL;
        echo 'Wp_id:            ' . $this->getWpId() . PHP_EOL;
        echo 'wpIdPostLink:     ' . $this->getWpIdPostLink() . PHP_EOL;
        echo 'wpIdPostStatus:   ' . $this->getWpIdPostStatus() . PHP_EOL;
        echo 'toCategoryLink:   ' . $this->getToCategoryLink() . PHP_EOL;
        echo 'toPageByPathLink: ' . $this->getToPageByPathLink() . PHP_EOL;
        echo 'Action:           ' . $this->getAction() . PHP_EOL;
        echo 'Reason:           ' . $this->getReason() . PHP_EOL;
        echo 'Field:            ' . $this->getField() . PHP_EOL;
        echo 'New value:        ' . $this->getNewValue() . PHP_EOL;
        echo PHP_EOL;
    }

    public function outputCsv($progress = '')
    {
        echo implode(';', [
                $this->getId(),
                $this->getLanguage(),
                $this->getType(),
                $this->getFrom(),
                $this->getTo(),
                $this->getFromUrl(),
                $this->getToUrl(),
                $this->getFromCode(),
                $this->getToCode(),
                $this->getWpId(),
                $this->getWpIdPostLink(),
                $this->getWpIdPostStatus(),
                $this->getToCategoryLink(),
                $this->getToPageByPathLink(),
                $this->getAction(),
                $this->getReason(),
                $this->getField(),
                $this->getNewValue(),
                $progress
            ]) . PHP_EOL;
    }
}