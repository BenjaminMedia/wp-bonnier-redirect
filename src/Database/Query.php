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

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function select(string $columns): Query
    {
        $this->query = "SELECT $columns FROM $this->table";
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
        return $this->query;
    }
}
