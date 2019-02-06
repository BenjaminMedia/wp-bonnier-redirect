<?php

namespace Bonnier\WP\Redirect\Database;

use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;

class DB
{
    const TABLE_NAME = 'bonnier_redirects';

    /** @var bool */
    private static $instantiated;

    /** @var \wpdb */
    private static $wpdb;
    private static $table;

    /**
     * @param int $redirectID
     * @return array|object|null
     */
    public static function getRedirect(int $redirectID)
    {
        self::init();

        return self::$wpdb->get_row("SELECT * FROM " . self::$table . " WHERE id = $redirectID");
    }

    /**
     * @param string|null $searchQuery
     * @param string|null $orderBy
     * @param string|null $order
     * @param int|null $perPage
     * @param int|null $offset
     * @param string $output
     * @return array|object|null
     */
    public static function fetchTable(
        ?string $searchQuery = null,
        ?string $orderBy = null,
        ?string $order = null,
        ?int $perPage = null,
        ?int $offset = null,
        string $output = OBJECT
    ) {
        self::init();

        $query = "SELECT * FROM " . self::$table;
        if ($searchQuery) {
            $query .= " WHERE `from` LIKE '%$searchQuery%' OR `to` LIKE '%$searchQuery%'";
        }
        if ($orderBy && $order) {
            $query .= " ORDER BY `$orderBy` $order";
        }
        if ($orderBy && !$order) {
            $query .= " ORDER BY `$orderBy`";
        }
        if (!is_null($perPage)) {
            $query .= " LIMIT $perPage";
        }
        if (!is_null($offset)) {
            $query .= " OFFSET $offset";
        }

        return self::$wpdb->get_results($query, $output);
    }

    /**
     * @param string|null $searchQuery
     *
     * @return int
     */
    public static function countRedirects(?string $searchQuery = null)
    {
        self::init();

        $query = "SELECT COUNT(id) FROM " . self::$table;

        if ($searchQuery) {
            $query .= " WHERE `from` LIKE '%$searchQuery%' OR `to` LIKE '%$searchQuery%'";
        }

        return intval(self::$wpdb->get_var($query));
    }

    /**
     * @param int $redirectID
     *
     * @return bool
     *
     * @throws \Exception
     */
    public static function delete(int $redirectID)
    {
        self::init();

        if (self::$wpdb->delete(self::$table, ['id' => $redirectID], ['%d']) === false) {
            throw new \Exception(
                sprintf('Could not delete redirect ID:%s (%s)', $redirectID, self::$wpdb->last_error)
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
     *
     * @return int
     *
     * @throws \Exception
     * @throws DuplicateEntryException
     */
    public static function insert(array $data)
    {
        self::init();
        if (!self::$wpdb->insert(self::$table, $data, self::getDataFormat($data))) {
            $error = self::$wpdb->last_error;
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
        return self::$wpdb->insert_id;
    }

    /**
     * @param int $redirectID
     * @param array $data
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function update(int $redirectID, array $data)
    {
        self::init();
        if (self::$wpdb->update(
            self::$table,
            $data,
            ['id' => $redirectID],
            self::getDataFormat($data),
            ['%d']
        ) === false) {
            throw new \Exception(
                sprintf('Unable to update redirect ID:%s (%s)', $redirectID, self::$wpdb->last_error)
            );
        }

        return true;
    }

    public static function createRedirectsTable()
    {
        self::init();
        $table = self::$table;
        $charset = self::$wpdb->get_charset_collate();

        $sql = "SET sql_notes = 1;
            CREATE TABLE `$table` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `from` text CHARACTER SET utf8 NOT NULL,
              `from_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `paramless_from_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `to` text CHARACTER SET utf8 NOT NULL,
              `to_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `locale` varchar(2) CHARACTER SET utf8 NOT NULL,
              `type` text CHARACTER SET utf8 NOT NULL,
              `wp_id` text CHARACTER SET utf8 DEFAULT NULL,
              `code` int(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `hashes` (`from_hash`,`to_hash`,`locale`),
              UNIQUE KEY `from_hash_locale` (`from_hash`, `locale`),
              KEY `from_hash_2` (`from_hash`,`to_hash`,`locale`),
              KEY `from_hash_3` (`from_hash`),
              KEY `paramless_from_hash` (`paramless_from_hash`)
            ) $charset;
            SET sql_notes = 1;
            ";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * @param array $data
     * @return array
     */
    private static function getDataFormat(array $data)
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

    private static function init()
    {
        if (!self::$instantiated) {
            global $wpdb;
            self::$wpdb = $wpdb;
            self::$wpdb->hide_errors();
            self::$wpdb->suppress_errors(true);
            self::$table = self::$wpdb->prefix . self::TABLE_NAME;
        }
    }
}
