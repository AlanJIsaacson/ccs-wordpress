<?php

namespace App\Repository;

use App\Services\Database\DatabaseConnection;

abstract class AbstractRepository implements RepositoryInterface {

    /**
     * Database bindings array
     *
     * @var array
     */
    protected $databaseBindings = [];

    /**
     * Database table name
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * @var \App\Services\Database\DatabaseConnection
     */
    protected $connection;

    /**
     * AbstractRepository constructor.
     */
    public function __construct()
    {
        $this->connection  = ( new DatabaseConnection() )->connection;
    }

    /**
     * Find all
     *
     * @return mixed
     */
    public function findAll()
    {
        $sql = 'SELECT * from  ' . $this->tableName;
        $query = $this->connection->prepare($sql);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($results)) {
            return false;
        }

        $modelCollection = $this->translateResultsToModels($results);

        return $modelCollection;
    }

    /**
     * Find a row with a certain Id
     *
     * @param string $fieldName
     * @param $id
     * @return bool
     */
    public function findById($id, $fieldName = 'id')
    {
        $sql = 'SELECT * from ' . $this->tableName . ' where ' . $fieldName . ' = :id';

        $query = $this->connection->prepare($sql);
        $query->bindParam(':id', $id, \PDO::PARAM_STR);

        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;
        }

        return $this->translateSingleResultToModel($result);
    }

    /**
     * Find a row with a certain Id
     *
     * @param string $fieldName
     * @param $id
     * @return bool
     */
    public function findAllById($id, $fieldName = 'id')
    {
        $sql = 'SELECT * from ' . $this->tableName . ' where ' . $fieldName . ' = :id';

        $query = $this->connection->prepare($sql);
        $query->bindParam(':id', $id, \PDO::PARAM_STR);

        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($results)) {
            return false;
        }

        $modelCollection = $this->translateResultsToModels($results);

        return $modelCollection;
    }

    /**
     * This method excludes the Wordpress Id, so it will not be overwritten with new (or null) data.
     * Create the the current data object in the database or update it if it already exists
     *
     * @param $searchField
     * @param $searchValue
     * @param $object
     * @return mixed
     */
    public function createOrUpdateExcludingWordpressId($searchField, $searchValue, $object)
    {
        $originalDataBindings = $this->databaseBindings;
        if (isset($this->databaseBindings['wordpress_id'])) {
            unset($this->databaseBindings['wordpress_id']);
        }
        $response = $this->createOrUpdate($searchField, $searchValue, $object);

        $this->databaseBindings = $originalDataBindings;

        return $response;
    }

    /**
     * Create the the current data object in the database or update it if it already exists
     *
     * @param $searchField
     * @param $searchValue
     * @param $object
     * @return mixed
     */
    public function createOrUpdate($searchField, $searchValue, $object)
    {
        if ($this->idExists($searchValue, $searchField))
        {
            return $this->update($searchField, $searchValue, $object);
        }

        return $this->create($object);
    }

    /**
     * Check if an Id exists in the DB already
     *
     * @param $fieldName
     * @param $id
     * @return bool
     */
    public function idExists($id, $fieldName = 'id')
    {
        $sql = 'SELECT * from ' . $this->tableName . ' where ' . $fieldName . ' = :id';

        $query = $this->connection->prepare($sql);
        $query->bindParam(':id', $id, \PDO::PARAM_STR);

        $query->execute();

        $results = $query->fetchAll();

        if (empty($results)) {
            return false;
        }

        return true;
    }

    /**
     * Delete a record via ID
     *
     * @param $id
     * @param string $fieldName
     * @return mixed
     */
    public function deleteById($id, $fieldName = 'id')
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' where ' . $fieldName . ' = :id';
        $query = $this->connection->prepare($sql);

        $query->bindParam(':id', $id, \PDO::PARAM_STR);

        $outcome = $query->execute();

        return $outcome;
    }

    /**
     * Translates a bunch of DB row results to an array of appropriate models
     *
     * @param array $results
     * @return array
     */
    protected function translateResultsToModels(array $results)
    {
        $modelCollection = [];

        foreach ($results as $result)
        {
            $modelCollection[] = $this->translateSingleResultToModel($result);
        }

        return $modelCollection;
    }

    /**
     * Translates a single result to a model
     *
     * @param array $result
     */
    protected function translateSingleResultToModel(array $result)
    {
        return $this->createModel($result);
    }

}