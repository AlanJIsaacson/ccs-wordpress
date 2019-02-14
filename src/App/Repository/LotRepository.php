<?php

namespace App\Repository;

use App\Model\Lot;

class LotRepository extends AbstractRepository
{

    /**
     * Database table name
     *
     * @var string
     */
    protected $tableName = 'ccs_lots';

    public function createModel($data = null)
    {
        return new Lot($data);
    }

    /**
     * @param \App\Model\Lot $lot
     * @return mixed
     */
    public function create(Lot $lot) {

        $databaseBindings = [
          'framework_id'   => ':framework_id',
          'wordpress_id'   => ':wordpress_id',
          'salesforce_id'  => ':salesforce_id',
          'lot_number'     => ':lot_number',
          'title'          => ':title',
          'status'         => ':status',
          'expiry_date'    => ':expiry_date',
          'hide_suppliers' => ':hide_suppliers',
        ];

        // Build the bindings PDO statement
        $columns = implode(", ", array_keys($databaseBindings));
        $fieldParams = implode(", ", array_values($databaseBindings));

        $sql = 'INSERT INTO ' . $this->tableName . ' (' . $columns . ') VALUES(' . $fieldParams . ')';

        $query = $this->connection->prepare($sql);

        $query = $this->bindValues($databaseBindings, $query, $lot);

        return $query->execute();
    }

    /**
     * @param $searchField
     * @param $searchValue
     * @param \App\Model\Lot $lot
     * @return mixed
     */
    public function update($searchField, $searchValue, Lot $lot)
    {
        $databaseBindings = [
          'framework_id'   => ':framework_id',
          'wordpress_id'   => ':wordpress_id',
          'salesforce_id'  => ':salesforce_id',
          'lot_number'     => ':lot_number',
          'title'          => ':title',
          'status'         => ':status',
          'expiry_date'    => ':expiry_date',
          'hide_suppliers' => ':hide_suppliers',
        ];

        // Remove the field which we're using for the update command
        if (isset($databaseBindings[$searchField]))
        {
            unset($databaseBindings[$searchField]);
        }

        // Build the bindings PDO statement
        $sql = 'UPDATE ' . $this->tableName . ' SET ';
        $count = 0;
        foreach ($databaseBindings as $column => $field) {
            $sql .= '`' . $column . '` = ' . $field;
            if (count($databaseBindings) != ($count + 1)) {
                $sql .= ', ';
            } else {
                $sql .= ' ';
            }
            $count++;
        }

        $sql .= 'WHERE ' . $searchField . ' = :searchValue';
        $query = $this->connection->prepare($sql);
        $query->bindParam(':searchValue', $searchValue, \PDO::PARAM_STR);

        $query = $this->bindValues($databaseBindings, $query, $lot);

        return $query->execute();
    }


    /**
     * Bind PDO Values
     *
     * @param $databaseBindings
     * @param $query
     * @param $lot
     * @return mixed
     */
    protected function bindValues($databaseBindings, $query, Lot $lot)
    {
        if (isset($databaseBindings['framework_id']))
        {
            $frameworkId = $lot->getFrameworkId();
            $query->bindParam(':framework_id', $frameworkId, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['wordpress_id']))
        {
            $wordpressId = $lot->getWordpressId();
            $query->bindParam(':wordpress_id', $wordpressId, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['salesforce_id']))
        {
            $salesforceId = $lot->getSalesforceId();
            $query->bindParam(':salesforce_id', $salesforceId, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['lot_number']))
        {
            $lotNumber = $lot->getLotNumber();
            $query->bindParam(':lot_number', $lotNumber, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['title']))
        {
            $title = $lot->getTitle();
            $query->bindParam(':title', $title, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['status']))
        {
            $status = $lot->getStatus();
            $query->bindParam(':status', $status, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['expiry_date']))
        {
            $expiryDate = $lot->getExpiryDate();
            if ($expiryDate instanceof \DateTime)
            {
                $expiryDate = $expiryDate->format('Y-m-d');
            }
            $query->bindParam(':expiry_date', $expiryDate, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['hide_suppliers']))
        {
            $hideSuppliers = $lot->isHideSuppliers();
            $query->bindParam(':hide_suppliers', $hideSuppliers, \PDO::PARAM_BOOL);
        }

        return $query;
    }


}