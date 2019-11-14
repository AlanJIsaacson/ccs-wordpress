<?php

namespace App\Search;

use App\Model\Supplier;
use App\Search\Mapping\SupplierMapping;
use App\Services\Logger\SearchLogger;
use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Elastica\Index;
use Elastica\Mapping;
use Elastica\Query;
use Elastica\Query\Term;
use Elastica\Request;
use Elastica\ResultSet;
use Elastica\Search;
use Psr\Log\LoggerInterface;

class Client extends \Elastica\Client
{

    /**
     * @var \Elastica\Index
     */
    // TODO: We need to set the environment name here on the end of the index, to allow using the same server for both live and staging. But where is environment set?
    /**
     *
     */
    const SUPPLIER_TYPE_NAME = 'supplier';

    /**
     * @var null
     */
    protected $supplierIndexExists = null;

    /**
     * Client constructor.
     * @param array $config
     * @param callable|null $callback
     * @param \Psr\Log\LoggerInterface|null $logger
     * @throws \Exception
     */
    public function __construct($config = [], callable $callback = null, LoggerInterface $logger = null) {

        if (empty($logger)) {
            $logger = new SearchLogger();
        }

        parent::__construct($config, $callback, $logger);

        if (!getenv('ELASTIC_SUFFIX')) {
            throw new \Exception('Please set the ELASTIC_SUFFIX environment variable before continuing.');
        }
    }

    /**
     *
     */
    protected function createSupplierIndex() {
        $index = $this->getIndex($this->getIndexName(self::SUPPLIER_TYPE_NAME));

        // Create the index new
        $index->create();

        $index->setMapping(new SupplierMapping());
    }

    /**
     * Returns the ElasticSearch index for the supplier
     *
     * @return \Elastica\Index
     */
    protected function getSupplierIndex(): Index
    {
        if (!$this->supplierIndexExists) {
            $response = $this->request( $this->getIndexName(self::SUPPLIER_TYPE_NAME), Request::HEAD);
            
            if ($response->getStatus() > 299) {
                $this->createSupplierIndex();
            }

            $this->supplierIndexExists = true;
        }

        return $this->getIndex($this->getIndexName(self::SUPPLIER_TYPE_NAME));
    }

    /**
     * Remove a supplier from the ElasticSearch index
     *
     * @param \App\Model\Supplier $supplier
     */
    public function removeSupplier(Supplier $supplier)
    {
        try {
            $this->getSupplierIndex()->deleteById($supplier->getId());
        } catch (NotFoundException $exception)
        {
            // We can ignore this exception. The document was never in the index.
        }

    }

    /**
     * Updates a supplier or creates a new one if it doesnt already exist
     *
     * @param \App\Model\Supplier $supplier
     * @param array|null $frameworks
     */
    public function createOrUpdateSupplier(Supplier $supplier, array $frameworks = null) {

        // Create a document
        $supplierData = [
          'id'            => $supplier->getId(),
          'salesforce_id' => $supplier->getSalesforceId(),
          'name'          => $supplier->getName(),
          'duns_number'   => $supplier->getDunsNumber(),
          'trading_name'  => $supplier->getTradingName(),
          'city'          => $supplier->getCity(),
          'postcode'      => $supplier->getPostcode(),
        ];

        $frameworkData = [];
        if (!empty($frameworks)) {
            /** @var \App\Model\Framework $framework */
            foreach ($frameworks as $framework)
            {
                $tempFramework['title'] = $framework->getTitle();
                $tempFramework['rm_number'] = $framework->getRmNumber();
                $tempFramework['end_date'] = $framework->getEndDate()->format('Y-m-d');
                $frameworkData[] = $tempFramework;
            }
        }

        $supplierData['live_frameworks'] = $frameworkData;

        // Create a new document with the data we need
        $document = new Document();
        $document->setData($supplierData);
        $document->setId($supplier->getId());
        $document->setDocAsUpsert(true);

        // Add document
        $this->getSupplierIndex()->updateDocument($document);

        // Refresh Index
        $this->getSupplierIndex()->refresh();
    }

    /**
     * Get the index name for the current index
     *
     * @param string $type
     * @return string
     */
    protected function getIndexName(string $type): string {
        return $type . '_' . getenv('ELASTIC_SUFFIX');
    }

    /**
     * Provide this class with a index type string and it will return the index
     *
     * @param string $type
     * @return \Elastica\Index
     * @throws \IndexNotFoundException
     */
    protected function convertIndexTypeToIndex(string $type): Index {
        switch ($type) {
            case self::SUPPLIER_TYPE_NAME:
                return $this->getSupplierIndex();
                break;
        }

        throw new \IndexNotFoundException('Index with the name: "' . $type . '" not found');
    }

    /**
     * Query's the fields on a given index
     *
     * @param string $type
     * @param string $keyword
     * @param int $limit
     * @return array
     * @throws \IndexNotFoundException
     */
    public function querySupplierIndexByKeyword(string $type, string $keyword = '', int $page, int $limit): ResultSet {
        $search = new Search($this);

        $search->addIndex($this->convertIndexTypeToIndex($type));

        // The default search all query
        $matchAll = new Query\MatchAll();
        $query = new Query($matchAll);

        if (!empty($keyword)) {
            // Create a bool query to allow us to set up multiple query types
            $boolQuery = new Query\BoolQuery();

            // Create a multimatch query so we can search multiple fields
            $multiMatchQuery = new Query\MultiMatch();
            $multiMatchQuery->setQuery($keyword);
            $multiMatchQuery->setFuzziness(1);
            $boolQuery->addShould($multiMatchQuery);

            $multiMatchQueryWithoutFuzziness = new Query\MultiMatch();
            $multiMatchQueryWithoutFuzziness->setQuery($keyword);
            $nestedQuery = new Query\Nested();
            $nestedQuery->setQuery($multiMatchQueryWithoutFuzziness);
            $nestedQuery->setPath('live_frameworks');

            $boolQuery->addShould($nestedQuery);

            $query = new Query($boolQuery);
        }

        $query->setSize($limit);
        $query->setFrom($this->translatePageNumberAndLimitToStartNumber($page, $limit));
        $query->addSort('name.raw');
        $search->setQuery($query);

        return $search->search();
    }

    /**
     * For pagination we work out the result number to start searching from
     *
     * @param int $page
     * @param int $limit
     * @return int
     */
    protected function translatePageNumberAndLimitToStartNumber(int $page, int $limit): int {
        if ($page >= 2)
        {
            $page = $page-1;
        } else {
            $page = 0;
        }

        return $page * $limit;
    }



}