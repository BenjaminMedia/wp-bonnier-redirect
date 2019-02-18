<?php

namespace Bonnier\WP\Redirect\Repositories;

use Bonnier\WP\Redirect\Database\Bootstrap;
use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Models\Log;
use Illuminate\Support\Collection;

class LogRepository extends BaseRepository
{
    public function __construct(DB $database)
    {
        $this->tableName = Bootstrap::LOG_TABLE;
        parent::__construct($database);
    }

    public function findById(int $logID): ?Log
    {
        if ($data = $this->database->findById($logID)) {
            $log = new Log();
            return $log->fromArray($data);
        }

        return null;
    }

    public function findAll(): ?Collection
    {
        $query = $this->database->query()->select('*');
        if ($logs = $this->database->getResults($query)) {
            return collect($logs)->map(function (array $data) {
                $log = new Log();
                return $log->fromArray($data);
            });
        }
        return null;
    }

    public function save(Log &$log): ?Log
    {
        $data = $log->toArray();
        unset($data['id']);

        if ($logID = $log->getID()) {
            $this->database->update($logID, $data);
            return $log;
        }
        if ($logID = $this->database->insert($data)) {
            $log->setID($logID);

            return $log;
        }

        return null;
    }
}
