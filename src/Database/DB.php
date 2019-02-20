<?php

namespace Bonnier\WP\Redirect\Database;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;

class DB
{
    /** @var \wpdb */
    private $wpdb;
    /** @var string */
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdb->show_errors(false);
        $this->wpdb->suppress_errors(true);
    }

    /**
     * @param string $tableName
     * @return string
     */
    public function setTable(string $tableName)
    {
        return $this->table = str_start($tableName, $this->wpdb->prefix);
    }

    /**
     * @param int $rowID
     * @return array|null
     */
    public function findById(int $rowID): ?array
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $rowID),
            ARRAY_A
        );
    }

    public function query(): Query
    {
        return new Query($this->table);
    }

    public function getResults(Query $query)
    {
        return $this->wpdb->get_results($query->getQuery(), ARRAY_A);
    }

    public function getVar(Query $query)
    {
        return $this->wpdb->get_var($query->getQuery());
    }

    /**
     * @param array $data
     * @return int
     * @throws DuplicateEntryException
    */
    public function insert(array $data)
    {
        if (!$this->wpdb->insert($this->table, $data, $this->getDataFormat($data))) {
            $error = $this->wpdb->last_error;
            if (starts_with($error, 'Duplicate entry ')) {
                $uniqueKey = str_after($error, ' for key ');
                $exception = new DuplicateEntryException(
                    sprintf('Cannot create entry, due to key constraint %s', $uniqueKey)
                );
                $exception->setData($data);
                throw $exception;
            } else {
                throw new \Exception(sprintf('Unable to insert row in `%s`! (%s)', $this->table, $error));
            }
        }
        return $this->wpdb->insert_id;
    }

    public function insertOrUpdate(array $data)
    {
        try {
            return $this->insert($data);
        } catch (DuplicateEntryException $exception) {
            if (!$this->wpdb->replace($this->table, $data, $this->getDataFormat($data))) {
                $error = $this->wpdb->last_error;
                throw new \Exception(sprintf('Unable to replace row in `%s`! (%s)', $this->table, $error));
            }
        }
        return $this->wpdb->insert_id;
    }

    /**
     * @param int $rowId
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function update(int $rowId, array $data)
    {
        if ($this->wpdb->update(
            $this->table,
            $data,
            ['id' => $rowId],
            self::getDataFormat($data),
            ['%d']
        ) === false) {
            throw new \Exception(
                sprintf('Unable to update row with ID:%s (%s)', $rowId, $this->wpdb->last_error)
            );
        }

        return true;
    }

    /**
     * @param int $rowID
     * @return bool
     * @throws \Exception
     */
    public function delete(int $rowID)
    {
        if ($this->wpdb->delete($this->table, ['id' => $rowID], ['%d']) === false) {
            throw new \Exception(
                sprintf(
                    'Could not delete row with ID %s from table \'%s\' (%s)',
                    $rowID,
                    $this->table,
                    $this->wpdb->last_error
                )
            );
        }

        return true;
    }

    /**
     * @param array $data
     * @return array
     */
    private function getDataFormat(array $data)
    {
        $format = [];
        foreach ($data as $item) {
            if (is_int($item)) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }
        return $format;
    }
}
