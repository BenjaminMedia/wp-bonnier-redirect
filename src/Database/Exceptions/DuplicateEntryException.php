<?php

namespace Bonnier\WP\Redirect\Database\Exceptions;

class DuplicateEntryException extends \Exception
{
    private $data;

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return DuplicateEntryException
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
