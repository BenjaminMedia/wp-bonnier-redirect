<?php

namespace Bonnier\WP\Redirect\Repositories;

use Bonnier\WP\Redirect\Database\Bootstrap;
use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Models\Redirect;

class RedirectRepository extends BaseRepository
{
    public function __construct(DB $database)
    {
        $this->tableName = Bootstrap::REDIRECTS_TABLE;
        parent::__construct($database);
    }

    public function getRedirectById(int $redirectID): ?Redirect
    {
        $data = $this->database->findById($redirectID);
        if ($data) {
            $redirect = new Redirect();
            return $redirect->fromArray($data);
        }

        return null;
    }

    public function find(
        ?string $searchQuery = null,
        ?string $orderBy = null,
        ?string $order = null,
        ?int $perPage = null,
        ?int $offset = null
    ) {
        $query = $this->database->query()->select('*');
        if ($searchQuery) {
            $query->where(['from', '%' . $searchQuery . '%', 'LIKE'])
                ->orWhere(['to', '%' . $searchQuery . '%', 'LIKE']);
        }
        if ($orderBy) {
            $query->orderBy($orderBy, $order);
        }
        if (!is_null($perPage)) {
            $query->limit($perPage);
        }
        if (!is_null($offset)) {
            $query->offset($offset);
        }

        return $this->database->getResults($query);
    }

    public function countRows(?string $searchKey = null): int
    {
        $query = $this->database->query()->select('COUNT(id)');
        if ($searchKey) {
            $query->where(['from', '%' . $searchKey . '%', 'LIKE'])
                ->orWhere(['to', '%' . $searchKey . '%', 'LIKE']);
        }

        return intval($this->database->getVar($query));
    }

    /**
     * @param Redirect $redirect
     * @return Redirect
     * @throws DuplicateEntryException|\Exception
     */
    public function save(Redirect $redirect): Redirect
    {
        $data = $redirect->toArray();
        unset($data['id']);

        if ($redirectId = $redirect->getID()) {
            $this->database->update($redirectId, $data);
        } else {
            $redirect->setID($this->database->insert($data));
        }

        return $redirect;
    }

    /**
     * @param Redirect $redirect
     * @return bool
     * @throws \Exception
     */
    public function delete(Redirect $redirect)
    {
        return $this->database->delete($redirect->getID()) !== false;
    }
}
