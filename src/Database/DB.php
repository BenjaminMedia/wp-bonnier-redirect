<?php

namespace Bonnier\WP\Redirect\Database;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;

class DB
{
    /** @var \wpdb */
    private $wpdb;
    /** @var string */
    private $table;

    /**
     * DB constructor.
     * @param \wpdb $wpdb
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->wpdb->hide_errors();
        $this->wpdb->suppress_errors(true);
    }

    /**
     * @param string $tableName
     * @return string
     */
    public function setTable(string $tableName)
    {
        return $this->table = $this->wpdb->prefix . $tableName;
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
                throw new \Exception(sprintf('Unable to insert redirect! (%s)', $error));
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
     * @param array $redirectIDs
     *
     * @return bool
     *
     * @throws \Exception
     */
    public static function deleteMultiple(array $redirectIDs)
    {
        self::init();
        $placeholder = implode(',', array_fill(0, count($redirectIDs), '%d'));
        $result = self::$wpdb->query(
            self::$wpdb->prepare(
                "DELETE FROM " . self::$table . " WHERE id IN (" . $placeholder . ");",
                $redirectIDs
            )
        );
        if ($result === false) {
            throw new \Exception(
                sprintf('Could not delete redirects! (%s)', self::$wpdb->last_error)
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
        foreach ($data as $index => $item) {
            if (is_int($item)) {
                $format[$index] = '%d';
            } else {
                $format[$index] = '%s';
            }
        }
        return $format;
    }
}
