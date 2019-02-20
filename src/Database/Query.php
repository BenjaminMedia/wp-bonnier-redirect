<?php

namespace Bonnier\WP\Redirect\Database;

class Query
{
    const FORMAT_INT = 0;
    const FORMAT_STRING = 1;

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    private $table;
    private $query;

    /** @var bool */
    private $selection;

    public function __construct($table)
    {
        $this->table = $table;
        $this->selection = false;
    }

    /**
     * @param string|array $columns
     * @return Query
     * @throws \Exception
     */
    public function select($columns): Query
    {
        if ($this->selection) {
            throw new \Exception('Selection already specified!');
        }
        $this->query = "SELECT ";
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $this->query .= $this->formatSelectColumn($column) . ", ";
            }
            $this->query = substr($this->query, 0, -2);
        } else {
            $this->query .= $this->formatSelectColumn($columns);
        }
        $this->query .= " FROM `$this->table`";
        $this->selection = true;
        return $this;
    }

    public function where(array $clause, $format = self::FORMAT_STRING): Query
    {
        if (count($clause) === 3) {
            $this->query .= " WHERE `$clause[0]` $clause[2] ";
        } else {
            $this->query .= " WHERE `$clause[0]` = ";
        }
        if ($format === self::FORMAT_INT) {
            $this->query .= $clause[1];
        } else {
            $this->query .= "'$clause[1]'";
        }
        return $this;
    }

    public function orWhere(array $clause, $format = self::FORMAT_STRING): Query
    {
        if (count($clause) === 3) {
            $this->query .= " OR `$clause[0]` $clause[2] ";
        } else {
            $this->query .= " OR `$clause[0]` = ";
        }
        if ($format === self::FORMAT_INT) {
            $this->query .= $clause[1];
        } else {
            $this->query .= "'$clause[1]'";
        }

        return $this;
    }

    public function andWhere(array $clause, $format = self::FORMAT_STRING): Query
    {
        if (count($clause) === 3) {
            $this->query .= " AND `$clause[0]` $clause[2] ";
        } else {
            $this->query .= " AND `$clause[0]` = ";
        }

        if ($format === self::FORMAT_INT) {
            $this->query .= $clause[1];
        } else {
            $this->query .= "'$clause[1]'";
        }

        return $this;
    }

    public function orderBy(string $orderBy, ?string $order = null): Query
    {
        $this->query .= " ORDER BY `$orderBy`";
        if ($order && in_array($order, [self::ORDER_ASC, self::ORDER_DESC])) {
            $this->query .= " $order";
        }

        return $this;
    }

    public function limit(int $limit): Query
    {
        $this->query .= " LIMIT $limit";

        return $this;
    }

    public function offset(int $offset): Query
    {
        $this->query .= " OFFSET $offset";

        return $this;
    }

    public function getQuery(): string
    {
        if (!$this->selection) {
            throw new \Exception('A selection needs to be specified!');
        }
        return $this->query;
    }

    private function formatSelectColumn($column): string
    {
        if (preg_match('/^(\*)|[A-Z]+\([\w]+\)/', $column)) {
            return $column;
        }
        return "`$column`";
    }
}
