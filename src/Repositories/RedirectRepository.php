<?php

namespace Bonnier\WP\Redirect\Repositories;

use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Database\Exceptions\DuplicateEntryException;
use Bonnier\WP\Redirect\Database\Migrations\Migrate;
use Bonnier\WP\Redirect\Database\Query;
use Bonnier\WP\Redirect\Exceptions\IdenticalFromToException;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Models\Redirect;
use Illuminate\Support\Collection;

class RedirectRepository extends BaseRepository
{
    /**
     * RedirectRepository constructor.
     * @param DB $database
     * @throws \Exception
     */
    public function __construct(DB $database)
    {
        $this->tableName = Migrate::REDIRECTS_TABLE;
        parent::__construct($database);
    }

    /**
     * @param int $redirectID
     * @return Redirect|null
     */
    public function getRedirectById(int $redirectID): ?Redirect
    {
        $data = $this->database->findById($redirectID);
        if ($data) {
            $redirect = new Redirect();
            return $redirect->fromArray($data);
        }

        return null;
    }

    /**
     * @param string $path
     * @param string|null $locale
     * @return Redirect|null
     * @throws \Exception
     */
    public function findRedirectByPath(string $path, string $locale = null): ?Redirect
    {
        if ($redirect = $this->findExactRedirectByPath($path, $locale)) {
            return $this->handleQueryParams($redirect, $path);
        }

        if ($redirect = $this->findRedirectByIgnoringQueryParams($path, $locale)) {
            return $this->handleQueryParams($redirect, $path);
        }

        if ($redirect = $this->findWildcardRedirectsByPath($path, $locale)) {
            return $this->handleQueryParams($redirect, $path);
        }

        return null;
    }

    /**
     * @param string $path
     * @param string|null $locale
     * @return Redirect|null
     * @throws \Exception
     */
    public function findExactRedirectByPath(string $path, string $locale = null): ?Redirect
    {
        if (is_null($locale)) {
            $locale = LocaleHelper::getLanguage();
        }

        $normalizedPath = UrlHelper::normalizePath($path);

        $query = $this->database->query()->select('*')
            ->where(['from_hash', hash('md5', $normalizedPath)])
            ->andWhere(['locale', $locale]);
        $results = $this->database->getResults($query);

        if ($results) {
            $redirect = new Redirect();
            return $redirect->fromArray($results[0]);
        }

        return null;
    }

    /**
     * @param string $path
     * @param string|null $locale
     * @return Redirect|null
     * @throws \Exception
     */
    public function findRedirectByIgnoringQueryParams(string $path, string $locale = null): ?Redirect
    {
        if (is_null($locale)) {
            $locale = LocaleHelper::getLanguage();
        }

        if (parse_url($path, PHP_URL_QUERY)) {
            if ($redirect = $this->findRedirectByPath(parse_url($path, PHP_URL_PATH), $locale)) {
                return $redirect;
            }
        }

        return null;
    }

    /**
     * @param string $path
     * @param string|null $locale
     * @return Redirect|null
     * @throws \Exception
     */
    public function findWildcardRedirectsByPath(string $path, string $locale = null): ?Redirect
    {
        if (is_null($locale)) {
            $locale = LocaleHelper::getLanguage();
        }

        $normalizedPath = UrlHelper::normalizePath($path);

        $query = $this->database->query()->select('*')
            ->where(['is_wildcard', 1], Query::FORMAT_INT)
            ->andWhere(['locale', $locale]);

        $results = $this->database->getResults($query);

        if (!$results) {
            return null;
        }

        $redirects = $this->mapRedirects($results);
        return $redirects->first(function (Redirect $redirect) use ($normalizedPath) {
            $regex = str_replace(
                ['.', '?', '$', '^', '*', '+', '|', '-', '(', ')', '[', ']'],
                ['\\.', '\\?', '\\$', '\\^', '\\*', '\\+', '\\|', '\\-', '\\(', '\\)', '\\[', '\\]'],
                substr($redirect->getFrom(), 0, -1)
            );
            return preg_match(sprintf('`^%s.*$`', $regex), $normalizedPath);
        });
    }

    /**
     * @return Collection|null
     * @throws \Exception
     */
    public function findAll(): ?Collection
    {
        $query = $this->database->query()->select('*');
        if ($redirects = $this->database->getResults($query)) {
            return $this->mapRedirects($redirects);
        }
        return null;
    }

    /**
     * @param $key
     * @param $value
     * @return Collection|null
     * @throws \Exception
     */
    public function findAllBy($key, $value): ?Collection
    {
        $query = $this->database->query()->select('*')
            ->where([$key, $value]);
        if ($redirects = $this->database->getResults($query)) {
            return $this->mapRedirects($redirects);
        }

        return null;
    }

    /**
     * @param string|null $searchQuery
     * @param string|null $orderBy
     * @param string|null $order
     * @param int|null $perPage
     * @param int|null $offset
     * @return array
     * @throws \Exception
     */
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

    /**
     * @param string|null $searchKey
     * @return int
     * @throws \Exception
     */
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
     * @param bool $updateOnDuplicate
     * @return Redirect
     * @throws IdenticalFromToException
     * @throws DuplicateEntryException
     * @throws \Exception
     */
    public function save(Redirect &$redirect, bool $updateOnDuplicate = false): Redirect
    {
        if ($redirect->getFrom() === $redirect->getTo()) {
            throw new IdenticalFromToException('A redirect with the same from and to, cannot be created!');
        }
        $data = $redirect->toArray();
        unset($data['id']);

        if ($redirectId = $redirect->getID()) {
            $this->database->update($redirectId, $data);
        } else {
            if ($updateOnDuplicate) {
                $redirect->setID($this->database->insertOrUpdate($data));
            } else {
                $redirect->setID($this->database->insert($data));
            }
        }

        $this->removeChainsByRedirect($redirect);

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

    /**
     * @param Collection $redirects
     * @return bool
     * @throws \Exception
     */
    public function deleteMultiple(Collection $redirects)
    {
        return $this->database->deleteMultiple($redirects->map(function (Redirect $redirect) {
            return $redirect->getID();
        })->toArray());
    }

    /**
     * @param array $redirectIDs
     * @return bool
     * @throws \Exception
     */
    public function deleteMultipleByIDs(array $redirectIDs)
    {
        return $this->database->deleteMultiple($redirectIDs);
    }

    /**
     * Finds all redirects that with to_hash that matches the specified redirects from hash.
     * I.e the redirect in the database that redirects from '/a/b' to /c/d'
     * will be updated according to the newly created redirect from '/c/d/' to '/e/f'
     * so both redirects will redirect to '/e/f'.
     *
     * @param Redirect $redirect
     * @throws \Exception
     */
    public function removeChainsByRedirect(Redirect $redirect)
    {
        if ($dbRedirects = $this->findAllBy('from_hash', $redirect->getToHash())) {
            $dbRedirects->each(function (Redirect $dbRedirect) use ($redirect) {
                if ($dbRedirect->getLocale() === $redirect->getLocale()) {
                    $redirect->setTo($dbRedirect->getTo());
                    $this->updateRedirect($redirect);
                }
            });
        }
        if ($redirects = $this->findAllBy('to_hash', $redirect->getFromHash())) {
            $redirects->each(function (Redirect $dbRedirect) use ($redirect) {
                if ($dbRedirect->getID() === $redirect->getID() ||
                    $dbRedirect->getLocale() !== $redirect->getLocale()
                ) {
                    return;
                }
                $dbRedirect->setTo($redirect->getTo());
                $this->updateRedirect($dbRedirect);
            });
        }
    }

    /**
     * @param array $redirects
     * @return Collection
     */
    private function mapRedirects(array $redirects): Collection
    {
        return collect($redirects)->map(function (array $data) {
            $redirect = new Redirect();
            return $redirect->fromArray($data);
        });
    }

    /**
     * @param Redirect $redirect
     * @throws \Exception
     */
    private function updateRedirect(Redirect $redirect)
    {
        $data = $redirect->toArray();
        unset($data['id']);
        $this->database->update($redirect->getID(), $data);
    }

    /**
     * @param Redirect $redirect
     * @param string $path
     * @return Redirect
     */
    private function handleQueryParams(Redirect $redirect, string $path): Redirect
    {
        if (!$redirect->keepsQuery()) {
            return $redirect;
        }
        if ($query = parse_url($path, PHP_URL_QUERY)) {
            return $redirect->addQuery($query);
        }

        return $redirect;
    }
}
