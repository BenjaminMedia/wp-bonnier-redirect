<?php

namespace Bonnier\WP\Redirect\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Database\Migrations\Migrate;
use Bonnier\WP\Redirect\Database\Query;
use Bonnier\WP\Redirect\Models\Log;
use Illuminate\Support\Collection;

class LogRepository extends BaseRepository
{
    public function __construct(DB $database)
    {
        $this->tableName = Migrate::LOG_TABLE;
        parent::__construct($database);
    }

    /**
     * @param int $logID
     * @return Log|null
     */
    public function findById(int $logID): ?Log
    {
        if ($data = $this->database->findById($logID)) {
            $log = new Log();
            return $log->fromArray($data);
        }

        return null;
    }

    public function findBySlug(string $slug): ?Collection
    {
        $query = $this->database->query()->select('*')
            ->where(['slug', $slug]);
        if ($logs = $this->database->getResults($query)) {
            return $this->mapLogs($logs);
        }
        return null;
    }

    /**
     * @return Collection|null
     * @throws \Exception
     */
    public function findAll(): ?Collection
    {
        $query = $this->database->query()->select('*');
        if ($logs = $this->database->getResults($query)) {
            return $this->mapLogs($logs);
        }
        return null;
    }

    /**
     * @param int $wpID
     * @param string $type
     * @return Collection|null
     * @throws \Exception
     */
    public function findByWpIDAndType(int $wpID, string $type): ?Collection
    {
        $query = $this->database->query()->select('*')
            ->where(['wp_id', $wpID], Query::FORMAT_INT)
            ->andWhere(['type', $type]);

        if ($logs = $this->database->getResults($query)) {
            return $this->mapLogs($logs);
        }

        return null;
    }

    /**
     * @param Log $log
     * @return Log|null
     * @throws DuplicateEntryException
     * @throws \Exception
     */
    public function save(Log &$log): ?Log
    {
        // start don't save if it's a duplicated record
        $logs = $this->findByWpIDAndType($log->getWpID(), $log->getType());
        if (! is_null($logs)){
            foreach($logs as $tmpLog){
                if(
                    ($tmpLog->getSlug() == $log->getSlug()) && 
                    ($tmpLog->getType() == $log->getType()) && 
                    ($tmpLog->getWpID() == $log->getWpID())
                ){
                    error_log('this log is duplicated 01:');
                    error_log($log->getSlug() . ' *** ' . $log->getWpID());
                    error_log('this log is duplicated 02:');
                    error_log($tmpLog->getSlug() . ' *** ' . $tmpLog->getWpID());
                    return $log;
                }
            }
        }
        // end don't save if it's a duplicated record
        $this->database->setTable($this->tableName);
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

    /**
     * @param array $logs
     * @return Collection
     */
    private function mapLogs(array $logs)
    {
        return collect($logs)->map(function (array $data) {
            $log = new Log();
            return $log->fromArray($data);
        });
    }
}
