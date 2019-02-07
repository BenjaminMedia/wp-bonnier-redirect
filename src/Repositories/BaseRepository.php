<?php

namespace Bonnier\WP\Redirect\Repositories;

use Bonnier\WP\Redirect\Database\DB;

class BaseRepository
{
    /** @var DB */
    protected $database;
    protected $tableName;

    /**
     * BaseRepository constructor.
     *
     * @param DB $database
     * @throws \Exception
     */
    public function __construct(DB $database)
    {
        $this->database = $database;
        if (!$this->tableName) {
            throw new \Exception('Missing required property \'$tableName\'');
        }
        $this->database->setTable($this->tableName);
    }
}
