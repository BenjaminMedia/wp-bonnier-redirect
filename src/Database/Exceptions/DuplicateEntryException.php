<?php

namespace Bonnier\WP\Redirect\Database\Exceptions;

class DuplicateEntryException extends \Exception
{
    private $data;

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
