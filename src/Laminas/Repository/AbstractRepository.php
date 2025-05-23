<?php

namespace AtpCore\Laminas\Repository;

use AtpCore\BaseClass;
use AtpCore\Format;
use DateTime;
use Exception;
use Throwable;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class AbstractRepository
 */
abstract class AbstractRepository extends BaseClass implements InputFilterAwareInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var array of filter-associations
     */
    protected $filterAssociations;

    /**
     * @var DoctrineObject
     */
    private $hydrator;

    /**
     * @var array inputData
     */
    protected $inputData;

    /**
     * @var array InputFilter
     */
    protected $inputFilter;

    /**
     * @var array Method-check
     */
    private $methodCheck;

    /**
     * @var EntityManager
     */
    protected $objectManager;

    /**
     * @var string of the \Doctrine\ORM\EntityRepository
     */
    protected $objectName;

    /**
     * @var string for (possible) selection input-filter
     */
    protected $operation;

    /**
     * @var mixed options
     */
    protected $options;

    /**
     * @var \Interop\Container\ContainerInterface
     */
    protected $serviceManager;

    /**
     * @var array of supported filters for query-optimizer (first request PK of results by filter and then request data of results by PK)
     */
    protected $supportedFiltersForQueryOptimizer;

    /**
     * Constructor
     *
     * @param EntityManager $objectManager
     */
    public function __construct(EntityManager $objectManager = null)
    {
        if (!empty($objectManager)) {
            $this->objectManager = $objectManager;
        }
        $this->resetErrors();
        $this->methodCheck = [];
    }

    /**
     * Create a new object
     *
     * @param $data
     * @param $output
     * @param $overrule
     * @param array $fields
     * @return array|bool
     */
    public function create($data, $output = 'object', $overrule = [], $fields = null)
    {
        // Set operation
        $this->operation = __FUNCTION__;

        // Reset errors
        $this->resetErrors();

        // Create object instance
        $object = new $this->objectName();

        // Prepare data
        $this->prepareInputDataDefault($data, $overrule);
        $this->prepareInputData();

        // Set default data (if not available)
        if (property_exists($object, 'created')) $this->inputData['created'] = new DateTime();
        if (property_exists($object, 'status')) $this->inputData['status'] = true;
        if (property_exists($object, 'deleted')) $this->inputData['deleted'] = false;

        // Hydrate object, apply inputfilter, and save it
        if ($this->filterAndPersist($this->inputData, $object)) {
            if ($output == 'array') {
                // Return result
                $record = $this->getHydrator()->extract($object);
                if (method_exists($this, 'transformData')) return $this->transformData($record, $fields);
                else return $record;
            } else {
                return $object;
            }
        } else {
            return false;
        }
    }

    /**
     * Create new objects (in bulk)
     *
     * @param $data
     * @param $output
     * @param $overrule
     * @param array $fields
     * @return array|bool
     */
    public function createBulk($data, $output = 'object', $overrule = [], $fields = null)
    {
        // Set operation
        $this->operation = __FUNCTION__;

        // Reset errors
        $this->resetErrors();

        // Iterate data
        $objects = [];
        $recordData = [];
        foreach ($data AS $key => $value) {
            // Create object instance
            $objects[$key] = new $this->objectName();

            // Prepare data
            $this->prepareInputDataDefault($value, $overrule);
            $this->prepareInputData();

            // Set default data (if not available)
            if (property_exists($objects[$key], 'created')) $this->inputData['created'] = new DateTime();
            if (property_exists($objects[$key], 'status')) $this->inputData['status'] = true;
            if (property_exists($objects[$key], 'deleted')) $this->inputData['deleted'] = false;
            $recordData[$key] = $this->inputData;
        }

        // Hydrate object, apply inputfilter, and save it
        if ($this->filterAndPersistBulk($recordData, $objects)) {
            if ($output == 'array') {
                // Return results
                $records = [];
                foreach ($objects AS $key => $object) {
                    $record = $this->getHydrator()->extract($object);
                    if (method_exists($this, 'transformData')) $records[$key] = $this->transformData($record, $fields);
                    else $records[$key] = $record;
                }
                return $records;
            } elseif ($output == 'boolean') {
                return true;
            } else {
                return $objects;
            }
        } else {
            return false;
        }
    }

    /**
     * Delete an object from the repository
     *
     * @param $id
     * @param $remove
     * @param $refresh
     * @return array|bool
     */
    public function delete($id, $remove = false, $refresh = false)
    {
        // Reset errors
        $this->resetErrors();

        // get object from the repository specified by primary key
        $object = $this->objectManager
            ->getRepository($this->objectName)
            ->find($id);

        // refresh entity (clear all local changes)
        if ($refresh === true) {
            $this->objectManager->refresh($object);
        }

        // return error if object not found
        if ($object == null) {
            $this->setMessages(['notFound' => $this->objectName. ' not found']);
            return false;
        }

        // check if object really has to move of only update status
        if ($remove === false) {
            $result = $this->update($id, ['status'=>false], 'array');
            return $result;
        } else {
            // remove the object from the repository or return error if something went wrong
            try {
                $this->objectManager->remove($object);
                $this->objectManager->flush();
                return true;
            } catch (Throwable $e) {
                $this->setMessages($e->getMessage());
                return false;
            }
        }
    }

    /**
     * Check if object exists
     *
     * @param $id
     * @return boolean
     */
    public function exists($id)
    {
        // get object from the repository specified by primary key
        $object = $this->objectManager
            ->getRepository($this->objectName)
            ->find($id);

        // return
        if ($object == null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Hydrate object (if applicable), apply inputfilter, save it, and return result
     *
     * @param mixed $data
     * @param $object
     * @param bool $flush
     * @return bool
     */
    public function filterAndPersist($data, &$object, $flush = true)
    {
        // Hydrate data to object (if applicable)
        if ($data instanceof $object) {
            $object = $data;
        } else {
            $this->getHydrator()->hydrate($data, $object);
        }

        // Convert object-data to prepare for validation (if function available in repository)
        if (method_exists($this, 'prepareObjectData')) {
            $object = $this->prepareObjectData($object, true);
        }

        // Check if data is valid
        $this->getInputFilter()->setData($this->getHydrator()->extract($object));
        if (!$this->getInputFilter()->isValid()) {
            // Get error messages from inputfilter
            $this->addMessage($this->getInputFilter()->getMessages());
        }

        // If no problems found, continue to save it
        if (empty($this->messages)) {
            // Persist and flush object
            try {
                // Convert object-data (if function available in repository)
                if (method_exists($this, 'prepareObjectData')) {
                    $object = $this->prepareObjectData($object);
                }

                // Persist object to database
                $this->getObjectManager()->persist($object);

                // Only flush (if permitted, used for bulk mutations)
                if ($flush === true) {
                    $this->getObjectManager()->flush();
                }
            } catch (Throwable $e) {
                $this->addMessage(['flushException' => $e->getMessage()]);
            }
        }

        // Return false if errors were found
        if (empty($this->messages)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Hydrate object, apply inputfilter, save it, and return result (in bulk)
     *
     * @param $records
     * @param $objects
     * @return bool
     */
    public function filterAndPersistBulk($records, &$objects)
    {
        // Iterate data
        foreach ($records AS $key => $record) {
            $res = $this->filterAndPersist($record, $objects[$key], false);
            if (!$res) break;
        }

        // Flush prepared records
        if (empty($this->messages)) {
            try {
                $this->getObjectManager()->flush();
            } catch (Throwable $e) {
                $this->addMessage(['flushException' => $e->getMessage()]);
            }
        }

        // Return false if errors were found
        if (empty($this->messages)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return a single object from the repository
     *
     * @param int $id
     * @param string $output
     * @param boolean $refresh
     * @param null|array $fields
     * @return object|array|bool
     * @throws Exception
     */
    public function get($id, $output = 'object', $refresh = false, $fields = null)
    {
        // get object from the repository specified by primary key
        if ($output == 'array') {
            $filter = ["AND"=>[["id", "eq", $id]]];
            $objects = $this->getByFilter($fields, null, $filter, null, null, null, 1, false);
            if ($objects === false) return false;
            $object = current($objects);
        } else {
            $object = $this->objectManager
                ->getRepository($this->objectName)
                ->find($id);

            // refresh entity (clear all local changes)
            if ($refresh === true) {
                $this->objectManager->refresh($object);
            }
        }

        // return error if object not found
        if ($object == null || $object === false) {
            $this->setMessages(['notFound' => $this->objectName. ' not found']);
            return false;
        }

        // return
        if ($output == 'array') {
            if (gettype($object) == 'array') {
                $record = $object;
            } else {
                $record = $this->getHydrator()->extract($object);
            }

            // Return result
            if (method_exists($this, 'transformData')) return $this->transformData($record, $fields);
            else return $record;
        } else {
            return $object;
        }
    }

    /**
     * Return all objects from the repository
     *
     * @param string $output
     * @param bool $refresh
     * @param array $fields
     * @return object|array
     */
    public function getAll($output = 'object', $refresh = false, $fields = null)
    {
        // get all objects from the repository
        $objects = $this->objectManager
            ->getRepository($this->objectName)
            ->findAll();

        // refresh entity (clear all local changes)
        if ($refresh === true) {
            foreach ($objects AS $object) {
                $this->objectManager->refresh($object);
            }
        }

        // convert objects to arrays
        if ($output == 'array') {
            $records = [];
            $hydrator = $this->getHydrator();
            foreach ($objects as $object) {
                $records[] = $hydrator->extract($object);
            }

            // Return result
            if (method_exists($this, 'transformData')) return $this->transformData($records, $fields);
            else return $records;
        } else {
            return $objects;
        }
    }

    /**
     * Return objects by filter
     *
     * @param null|array|false $fields
     * @param null|array $defaultFilter
     * @param null|array $filter
     * @param null|array $groupBy
     * @param null|array $having
     * @param null|array $orderBy
     * @param null|array $limit
     * @param boolean $paginator
     * @param boolean$debug
     * @return array|object|boolean
     * @throws Exception
     */
    public function getByFilter($fields = null, $defaultFilter = null, $filter = null, $groupBy = null, $having = null, $orderBy = null, $limit = null, $paginator = false, $debug = false)
    {
        // Get client-filter
        $clientFilter = $this->options->getClientFilter();
        // Get default-filter(s)
        if (!empty($defaultFilter)) {
            $defaultFilter = $this->options->getDefaultFilter($defaultFilter);
            if ($defaultFilter === false) {
                $this->setMessages("Provided unsupported/unknown default-filter");
                return false;
            }
        }
        // Set allowed operators (for custom/default filters)
        $allowedOperators = ['eq','neq','like','lt','lte','gt','gte','isnull','isnotnull','in','notin'];

        // Build query
        $query = $this->objectManager->createQueryBuilder();
        $parameters = [];

        // Validate fields if provided
        if (!empty($fields)) {
            // Add required conversion-fields (used in conversion-function)
            if (method_exists($this->options, 'getRequiredConversionFields')) {
                $fields = $this->options->getRequiredConversionFields($fields);
            }

            // Get object and field-methods
            $objectMethods = get_class_methods(new $this->objectName());
            $fieldMethods = preg_filter('/^/', 'get', array_map("ucfirst", $fields));
        }

        // Set fields
        if ($fields !== false && !empty($fields) && \AtpCore\Input::containsCapitalizedValue($fields) === false && empty(array_diff($fieldMethods, $objectMethods))) {
            $query->select("f." . implode(", f.", $fields));
        } else {
            $query->select('f');
        }

        // Set from
        $query->from($this->objectName, 'f');
        // Set joins (if available/needed)
        if ((!empty($filter) || !empty($clientFilter) || !empty($defaultFilter) || !empty($orderBy) || !empty($groupBy)) && !empty($this->getFilterAssociations())) {
            $joins = [];
            foreach ($this->getFilterAssociations() AS $filterAssociation) {
                $match = false;
                if (!empty($filter["AND"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($filter["AND"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($filter["OR"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($filter["OR"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($filter["OR_AND"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($filter["OR_AND"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($filter["AND_OR"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($filter["AND_OR"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($clientFilter["filter"]) && stristr($clientFilter["filter"], $filterAssociation['alias'] . ".") && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($defaultFilter["AND"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($defaultFilter["AND"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($defaultFilter["OR"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($defaultFilter["OR"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($defaultFilter["OR_AND"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($defaultFilter["OR_AND"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($defaultFilter["AND_OR"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($defaultFilter["AND_OR"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($defaultFilter["filter"]) && stristr($defaultFilter["filter"], $filterAssociation['alias'] . ".") && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($orderBy)) {
                    foreach ($orderBy AS $orderByField) {
                        if (stristr($orderByField['field'], $filterAssociation['alias'] . ".") && !in_array($filterAssociation['alias'], $joins)) {
                            $match = true;
                        }
                    }
                } elseif (!empty($groupBy)) {
                    foreach ($groupBy AS $groupByField) {
                        if (stristr($groupByField, $filterAssociation['alias'] . ".") && !in_array($filterAssociation['alias'], $joins)) {
                            $match = true;
                        }
                    }
                }

                if ($match === true) {
                    // Loop associations (to set filter-association-joins) till base (f.xx) reached (ORDER OF ADDING JOINS TO QUERY IS IMPORTANT, THEREFORE NOT ADD DIRECTLY TO QUERY!)
                    $filterAssociationJoins = [];
                    $association = $filterAssociation;
                    while (substr($association['join'], 0, 2) != "f.") {
                        $alias = current(explode(".", $association['join']));
                        $key = array_search($alias, array_column($this->getFilterAssociations(), 'alias'));
                        $association = $this->getFilterAssociations()[$key];
                        if (!in_array($association['alias'], $joins)) {
                            $joins[] = $association['alias'];
                            $filterAssociationJoins[] = $association;
                        }
                    }
                    // Set filter-association-joins (reverse order), if available for filter-association
                    if (!empty($filterAssociationJoins)) {
                        krsort($filterAssociationJoins);
                        foreach ($filterAssociationJoins AS $filterAssociationJoin) {
                            if (array_key_exists('condition', $filterAssociationJoin) && !empty($filterAssociationJoin['condition'])) {
                                $query->leftJoin($filterAssociationJoin['join'], $filterAssociationJoin['alias'], "WITH", $filterAssociationJoin['condition']);
                            } else {
                                $query->leftJoin($filterAssociationJoin['join'], $filterAssociationJoin['alias']);
                            }
                        }
                    }
                    // Set association
                    $joins[] = $filterAssociation['alias'];
                    if (array_key_exists('condition', $filterAssociation) && !empty($filterAssociation['condition'])) {
                        $query->leftJoin($filterAssociation['join'], $filterAssociation['alias'], "WITH", $filterAssociation['condition']);
                    } else {
                        $query->leftJoin($filterAssociation['join'], $filterAssociation['alias']);
                    }
                }
            }
        }
        // Set customized filter (if available)
        if (!empty($filter)) {
            // Set AND-conditions (if available)
            if (isset($filter['AND'])) {
                $filterConditions = $query->expr()->andX();
                // Iterate conditions
                foreach ($filter['AND'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "customAnd" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->andWhere($filterConditions);
            }
            // Set OR-conditions (if available)
            if (isset($filter['OR'])) {
                $filterConditions = $query->expr()->orX();
                // Iterate conditions
                foreach ($filter['OR'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "customOr" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->orWhere($filterConditions);
            }
            // Set OR-conditions (if available)
            if (isset($filter['OR_AND'])) {
                $filterConditions = $query->expr()->andX();
                // Iterate conditions
                foreach ($filter['OR_AND'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "customOrAnd" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->orWhere($filterConditions);
            }
            // Set OR-conditions (if available)
            if (isset($filter['AND_OR'])) {
                $filterConditions = $query->expr()->orX();
                // Iterate conditions
                foreach ($filter['AND_OR'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "customAndOr" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->andWhere($filterConditions);
            }
        }
        // Set customized default-filter (if available)
        if (!empty($defaultFilter)) {
            // Set AND-conditions (if available)
            if (isset($defaultFilter['AND'])) {
                $filterConditions = $query->expr()->andX();
                // Iterate conditions
                foreach ($defaultFilter['AND'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "defaultAnd" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->andWhere($filterConditions);
            }
            // Set OR-conditions (if available)
            if (isset($defaultFilter['OR'])) {
                $filterConditions = $query->expr()->andX();
                // Iterate conditions
                foreach ($defaultFilter['OR'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "defaultOr" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->orWhere($filterConditions);
            }
            // Set OR-conditions (if available)
            if (isset($defaultFilter['OR_AND'])) {
                $filterConditions = $query->expr()->andX();
                // Iterate conditions
                foreach ($defaultFilter['OR_AND'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "defaultOrAnd" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->orWhere($filterConditions);
            }
            // Set OR-conditions (if available)
            if (isset($defaultFilter['AND_OR'])) {
                $filterConditions = $query->expr()->orX();
                // Iterate conditions
                foreach ($defaultFilter['AND_OR'] AS $k => $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $valueKey = "defaultAndOr" . $this->sanitizeField($field) . $k;
                    if (isset($filterParams[2])) $parameters[$valueKey] = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array(Format::lowercase($operator), $allowedOperators)) throw new Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, ':' . $valueKey));
                }
                // Add filter-conditions to query
                $query->andWhere($filterConditions);
            }
            // Set filter (if available)
            if (isset($defaultFilter['filter'])) {
                $query->andWhere($defaultFilter['filter']);
                $parameters = (empty($parameters)) ? $defaultFilter['parameters'] : array_merge($parameters, $defaultFilter['parameters']);
            }
        }
        // Set client-filter
        if (!empty($clientFilter)) {
            $query->andWhere($clientFilter['filter']);
            $parameters = (empty($parameters)) ? $clientFilter['parameters'] : array_merge($parameters, $clientFilter['parameters']);
        }
        // Set group-by (if available)
        if (!empty($groupBy)) {
            foreach ($groupBy AS $groupByField) {
                $groupByField = (stristr($groupByField, ".")) ? $groupByField : "f." . $groupByField;
                $query->addGroupBy($groupByField);
            }
        }
        // Set having (if available)
        if (!empty($having)) {
            $query->having($having['filter']);
            $parameters = (empty($parameters)) ? $having['parameters'] : array_merge($parameters, $having['parameters']);
        }
        // Set order-by (if available)
        if (!empty($orderBy)) {
            foreach ($orderBy AS $order) {
                $orderField = (stristr($order['field'], ".")) ? $order['field'] : "f." . $order['field'];
                $direction = (!empty($order['direction'])) ? $order['direction'] : null;
                $query->addOrderBy($orderField, $direction);
            }
        }
        // Set limit (if available)
        if (!empty($limit)) {
            if (!empty($limit['offset'])) {
                $query->setFirstResult($limit['offset']);
            }
            if (!empty($limit['limit'])) {
                // Set maximum limit to 1000 records!
                if ($limit['limit'] > 1000) {
                    $limit['limit'] = 1000;
                }

                $query->setMaxResults($limit['limit']);
            }
        }
        // Set parameters (if available)
        if (!empty($parameters)) {
            $query->setParameters($parameters);
        }

        // Return DQL (in debug-mode)
        if ($debug) {
            return ["results"=>["query"=>$query->getQuery()->getDQL(), "parameters"=>$parameters]];
        }

        // Get results
        if ($paginator) {
            // Set paginator-result
            $paginatorQuery = clone $query;
            if (property_exists($this->objectName, 'id')) {
                $paginatorQuery->select('f.id');
            }
            $paginatorQuery->resetDQLPart('orderBy');
            $paginatorResult = new Paginator($paginatorQuery, $fetchJoinCollection = true);
            $paginatorResult->setUseOutputWalkers(false);
            $paginatorData['records'] = (int) $paginatorResult->count();
            $paginatorData['pages'] = (int) ceil($paginatorData['records'] / $limit['limit']);
            $paginatorData['currentPage'] = (int) (ceil($limit['offset'] / $limit['limit']) + 1);
            $paginatorData['recordsPage'] = (int) $limit['limit'];

            // Return if only paginator requested (fields set to false)
            if ($fields === false) {
                return ["paginator"=>$paginatorData, "results"=>null];
            }

            // Get "page"-results (if any results found)
            if ($paginatorData['records'] > 0) {
                // Change limit if total records less than records per page
                if ($paginatorData['recordsPage'] > $paginatorData['records']) {
                    $query->setMaxResults($paginatorData['records']);
                }
                $results = $query->getQuery()->getResult();

                // Sometimes paginator is not showing correct values (for example: group-by queries)
                $resultCount = count($results);
                if ($resultCount < $limit['limit'] && $resultCount < $paginatorData['records'] && $paginatorData['currentPage'] == 1) {
                    $paginatorData['records'] = (int) $resultCount;
                    $paginatorData['pages'] = (int) ceil($paginatorData['records'] / $limit['limit']);
                    $paginatorData['currentPage'] = (int) (ceil($limit['offset'] / $limit['limit']) + 1);
                    $paginatorData['recordsPage'] = (int) $limit['limit'];
                }
            } else {
                $results = [];
            }

            // Return
            return ["paginator"=>$paginatorData, "results"=>$results];
        } else {
            // Return
            return $query->getQuery()->getResult();
        }
    }

    /**
     * Return objects by ids
     *
     * @param array $ids
     * @param array|null $fields
     * @param null|array $orderBy
     * @param boolean $debug
     * @return object|array
     * @throws Exception
     */
    public function getByIds($ids, $fields = null, $orderBy = null, $debug = false)
    {
        // Build query
        $query = $this->objectManager->createQueryBuilder();
        $parameters = [];

        // Validate fields if provided
        if (!empty($fields)) {
            // Add required conversion-fields (used in conversion-function)
            if (method_exists($this->options, 'getRequiredConversionFields')) {
                $fields = $this->options->getRequiredConversionFields($fields);
            }

            // Get object and field-methods
            $objectMethods = get_class_methods(new $this->objectName());
            $fieldMethods = preg_filter('/^/', 'get', array_map("ucfirst", $fields));
        }

        // Set fields
        if (!empty($fields) && \AtpCore\Input::containsCapitalizedValue($fields) === false && empty(array_diff($fieldMethods, $objectMethods))) {
            $query->select("f." . implode(", f.", $fields));
        } else {
            $query->select('f');
        }

        // Set from
        $query->from($this->objectName, 'f');

        // Set filter
        $query->where($query->expr()->in('f.id', ':ids'));
        foreach ($ids AS $id) {
            $parameters['ids'][] = (is_array($id)) ? $id['id'] : $id->getId();
        }

        // Set order-by (if available)
        if (!empty($orderBy)) {
            // Set joins (if available/needed)
            if (!empty($this->getFilterAssociations())) {
                $joins = [];
                foreach ($this->getFilterAssociations() AS $filterAssociation) {
                    $match = false;
                    foreach ($orderBy AS $orderByField) {
                        if (stristr($orderByField['field'], $filterAssociation['alias'] . ".") && !in_array($filterAssociation['alias'], $joins)) {
                            $match = true;
                        }
                    }

                    if ($match === true) {
                        // Loop associations (to set filter-association-joins) till base (f.xx) reached (ORDER OF ADDING JOINS TO QUERY IS IMPORTANT, THEREFORE NOT ADD DIRECTLY TO QUERY!)
                        $filterAssociationJoins = [];
                        $association = $filterAssociation;
                        while (substr($association['join'], 0, 2) != "f.") {
                            $alias = current(explode(".", $association['join']));
                            $key = array_search($alias, array_column($this->getFilterAssociations(), 'alias'));
                            $association = $this->getFilterAssociations()[$key];
                            if (!in_array($association['alias'], $joins)) {
                                $joins[] = $association['alias'];
                                $filterAssociationJoins[] = $association;
                            }
                        }
                        // Set filter-association-joins (reverse order), if available for filter-association
                        if (!empty($filterAssociationJoins)) {
                            krsort($filterAssociationJoins);
                            foreach ($filterAssociationJoins AS $filterAssociationJoin) {
                                if (array_key_exists('condition', $filterAssociationJoin) && !empty($filterAssociationJoin['condition'])) {
                                    $query->leftJoin($filterAssociationJoin['join'], $filterAssociationJoin['alias'], "WITH", $filterAssociationJoin['condition']);
                                } else {
                                    $query->leftJoin($filterAssociationJoin['join'], $filterAssociationJoin['alias']);
                                }
                            }
                        }
                        // Set association
                        $joins[] = $filterAssociation['alias'];
                        if (array_key_exists('condition', $filterAssociation) && !empty($filterAssociation['condition'])) {
                            $query->leftJoin($filterAssociation['join'], $filterAssociation['alias'], "WITH", $filterAssociation['condition']);
                        } else {
                            $query->leftJoin($filterAssociation['join'], $filterAssociation['alias']);
                        }
                    }
                }
            }
            foreach ($orderBy AS $order) {
                $orderField = (stristr($order['field'], ".")) ? $order['field'] : "f." . $order['field'];
                $direction = (!empty($order['direction'])) ? $order['direction'] : null;
                $query->addOrderBy($orderField, $direction);
            }
        }

        // Set parameters
        $query->setParameters($parameters);

        // Return DQL (in debug-mode)
        if ($debug) {
            return ["results"=>["query"=>$query->getQuery()->getDQL(), "parameters"=>$parameters]];
        }

        // Get results
        return $query->getQuery()->getResult();
    }

    /**
     * Return all objects from the repository with parameters
     *
     * @param array $parameters
     * @param string $output [boolean, object, array]
     * @param boolean $multiple
     * @return array|object|bool
     */
    public function getByParameters($parameters, $output = 'object', $multiple = true)
    {
        // get object from the repository specified by primary key
        $objects = $this->objectManager
            ->getRepository($this->objectName)
            ->findBy($parameters);

        // return error if object not found
        if ($objects == null) {
            $this->setMessages(['notFound' => $this->objectName. ' not found']);
            return false;
        }

        // convert objects to arrays
        if ($output == 'array') {
            $data = [];
            $hydrator = $this->getHydrator();
            foreach ($objects as $object) {
                $data[] = $hydrator->extract($object);
            }

            // return
            if ($multiple === false) {
                return current($data);
            } else {
                return $data;
            }
        } elseif ($output == 'boolean') {
            return true;
        } else {
            if ($multiple === false) {
                return current($objects);
            } else {
                return $objects;
            }
        }
    }

    /**
     * Return total number of objects from the repository
     *
     * @return int
     */
    public function getCount()
    {
        // Return count of objects from the repository
        return (int) $this->objectManager->createQueryBuilder()
            ->select('count(f.id)')
            ->from($this->objectName, 'f')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get default filter-options
     * @return array
     */
    public function getDefaultFilterOptions()
    {
        return $this->options->getDefaultFilterOptions();
    }

    /**
     * Get field-value by id(s)
     *
     * @param string $field
     * @param int|array $id
     * @return string|array
     */
    public function getFieldById($field, $id)
    {
        // Find records by field-value parameters
        $multiple = (is_array($id)) ? true : false;
        $records = $this->getByParameters(['id'=>$id], "object", $multiple);
        if ($records === false) return null;

        // Return
        if (is_array($id)) {
            // Get multiple values
            $values = [];
            foreach ($records AS $record) {
                $values[] = $record->{'get' . ucfirst($field)}();
            }

            return $values;
        } else {
            return $records->{'get' . ucfirst($field)}();
        }
    }

    /**
     * Get field-value (result) by field-value(s) (search)
     *
     * @param string $searchField
     * @param string|array $value
     * @param string $resultField
     * @return string|array
     */
    public function getFieldByValue($searchField, $value, $resultField)
    {
        // Find records by field-value parameters
        $multiple = (is_array($value)) ? true : false;
        $records = $this->getByParameters([$searchField=>$value], "object", $multiple);
        if ($records === false) return null;

        // Return
        if (is_array($value)) {
            // Get multiple values
            $values = [];
            foreach ($records AS $record) {
                $values[] = $record->{'get' . ucfirst($resultField)}();
            }

            return $values;
        } else {
            return $records->{'get' . ucfirst($resultField)}();
        }
    }

    /**
     * Get filter-associations of entity
     *
     * @return mixed
     */
    public function getFilterAssociations()
    {
        return $this->filterAssociations;
    }

    /**
     * Get Hydrator
     *
     * @return DoctrineObject
     */
    public function getHydrator()
    {
        // create hydrator if not created yet
        if ($this->hydrator === null) {
            // create hydrator
            $this->hydrator = new DoctrineObject($this->objectManager);
        }

        return $this->hydrator;
    }

    /**
     * Get id(s) by field-value(s)
     *
     * @param string $field
     * @param string|array $value
     * @return string|array
     */
    public function getIdByField($field, $value)
    {
        // Find records by field-value parameters
        $multiple = (is_array($value)) ? true : false;
        $records = $this->getByParameters([$field=>$value], "object", $multiple);
        if ($records === false) return null;

        // Return
        if (is_array($value)) {
            // Get multiple ids
            $ids = [];
            foreach ($records AS $record) {
                $ids[] = $record->getId();
            }

            return $ids;
        } else {
            return $records->getId();
        }
    }

    /**
     * Return all (active) id's from the repository
     *
     * @return array
     */
    public function getIds()
    {
        // Get all objects from the repository
        $objects = $this->objectManager
            ->getRepository($this->objectName)
            ->findAll();

        // Convert objects to list of id's
        $ids = [];
        foreach ($objects as $object) {
            // Skip deleted objects
            if (property_exists($object, 'status') && $object->getStatus() === false) {
                continue;
            }
            $ids[] = $object->getId();
        }

        // Return
        return $ids;
    }

    /**
     * Get input-data for create/update record
     *
     * @return mixed
     */
    public function getInputData()
    {
        return $this->inputData;
    }

    /**
     * Get input filter
     *
     * @return object
     */
    public abstract function getInputFilter();

    /**
     * Return a list of objects from the repository
     *
     * @param string $output
     * @param array|false $fields
     * @param array $defaultFilter
     * @param array $filter
     * @param array $groupBy
     * @param array $having
     * @param array $orderBy
     * @param integer $limitRecords
     * @param integer $offset
     * @param boolean $paginator
     * @param boolean $debug
     * @return array|object|false
     * @throws Exception
     */
    public function getList($output = 'object', $fields = null, $defaultFilter = null, $filter = null, $groupBy = null, $having = null, $orderBy = null, $limitRecords = 25, $offset = 0, $paginator = false, $debug = false)
    {
        if (!empty((int) $limitRecords)) $limit['limit'] = (int) $limitRecords;
        else $limit['limit'] = 25;
        $limit['offset'] = $offset;
        if (!is_array($filter)) $filter = [];

        // Get results
        $isSupportedForQueryOptimizer = (empty($groupBy) && empty($having)) ? $this->isSupportedForQueryOptimizer($defaultFilter, $filter) : false;
        if ($fields !== false && $fields != ["id"] && property_exists($this->objectName, 'id') && $isSupportedForQueryOptimizer) {
            $res = $this->getByFilter(["id"], $defaultFilter, $filter, $groupBy, $having, $orderBy, $limit, $paginator, $debug);
            if ($res === false) return false;
            $ids = ($paginator) ? $res["results"] : $res;
            if (empty($ids)) {
                $records = ($paginator) ? ["paginator"=>$res["paginator"], "results"=>$ids] : $ids;
            } else {
                $records = $this->getByIds($ids, $fields, $orderBy, $debug);
                if ($paginator) {
                    $records = ["paginator"=>$res["paginator"], "results"=>$records];
                }
            }
        } else {
            $records = $this->getByFilter($fields, $defaultFilter, $filter, $groupBy, $having, $orderBy, $limit, $paginator, $debug);
        }
        if ($records === false) return false;

        // Return if only paginator requested (fields set to false)
        if ($fields === false) {
            return $records;
        }

        // Convert object to array (if output is array)
        if ($output == 'array') {
            $hydrator = $this->getHydrator();
            if ($paginator === true) {
                foreach ($records['results'] AS $k => $v) {
                    if (gettype($v) == 'array') {
                        $records['results'][$k] = $v;
                    } else {
                        $records['results'][$k] = $hydrator->extract($v);
                    }
                }
            } else {
                foreach ($records AS $k => $v) {
                    if (gettype($v) == 'array') {
                        $records[$k] = $v;
                    } else {
                        $records[$k] = $hydrator->extract($v);
                    }
                }
            }

            // Return result
            if (method_exists($this, 'transformData')) return $this->transformData($records, $fields);
            else return $records;
        } else {
            // Return result
            return $records;
        }
    }

    /**
     * Get ObjectManager
     *
     * @return EntityManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Get the ObjectName
     *
     * @return mixed
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * Get array of customized request-fields by application and user
     *
     * @param array $dataFields
     * @param array $customFields
     * @return array
     */
    public function getRequestedFields($dataFields, $customFields)
    {
        $requestedFields = ["fields"=>[], "entities"=>[]];
        foreach ($customFields AS $customField) {
            // Add specific fields of entity to requestedFields
            if (stristr($customField, "-")) {
                // Get fields (explode by "-")
                $fieldParts = explode("-", $customField);
                $configuredFields = $dataFields["entities"];
                foreach ($fieldParts AS $k => $v) {
                    // Check if field-part is entity
                    if (isset($configuredFields[$v])) {
                        // Check if fieldPart is last, then set entity (array of properties) to fieldParts
                        if (count($fieldParts) == ($k + 1)) {
                            array_push($fieldParts, $configuredFields[$v]);
                        }
                        $configuredFields = $configuredFields[$v];
                    } elseif (in_array($v, $configuredFields)) {
                        // Check if field-part is property, then set property (array) to fieldParts
                        $fieldParts[$k] = [$v];
                    } else {
                        // Field-part is no entity or property (so unset fieldParts, maybe not configured or misspelled)
                        unset($fieldParts);
                        break;
                    }
                }

                if (isset($fieldParts) && is_array($fieldParts) && !empty($fieldParts)) {
                    // Sort in reverse order to set proper values
                    krsort($fieldParts);

                    $tmpRequestedFields = [];
                    foreach ($fieldParts AS $fieldPart) {
                        if (empty($tmpRequestedFields)) {
                            // Set first values to array
                            $tmpRequestedFields = $fieldPart;
                        } else {
                            // Preserve existing values (empty array for new structure, set to variable by fieldPart-name)
                            $values = $tmpRequestedFields;
                            unset($tmpRequestedFields);
                            $tmpRequestedFields[$fieldPart] = $values;
                        }
                    }

                    // Set/merge to requestedFields
                    $requestedFields["entities"] = (!empty($requestedFields["entities"])) ? array_merge_recursive($requestedFields["entities"], $tmpRequestedFields) : $tmpRequestedFields;
                }
            } elseif (in_array(Format::lowercase($customField), array_map("\AtpCore\Format::lowercase", $dataFields['fields']))) {
                // Add field to requestedFields
                $requestedFields["fields"][] = $customField;
            } elseif (array_key_exists($customField, $dataFields['entities'])) {
                // Add entire entity to requestedFields (no fields of entity specified)
                $requestedFields["entities"][$customField] = $dataFields['entities'][$customField];
            }
        }

        // Return
        return $requestedFields;
    }

    /**
     * Get the ServiceManager
     *
     * @return mixed
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function isSupportedForQueryOptimizer($defaulFilters, $filters)
    {
        // Validation on inputs
        if (empty($defaulFilters) && empty($filters)) return true;
        if (empty($this->supportedFiltersForQueryOptimizer)) return false;

        // Check (default)filters supported
        if (!empty($filters)) {
            if (!isset($this->supportedFiltersForQueryOptimizer['allowedFilterFields'])) return false;
            foreach ($filters as $filterType) {
                foreach ($filterType as $filter) {
                    if (!isset($filter[0]) || !in_array(\AtpCore\Format::lowercase($filter[0]), array_map("\AtpCore\Format::lowercase", $this->supportedFiltersForQueryOptimizer['allowedFilterFields']))) {
                        return false;
                    }
                }
            }
        }
        if (!empty($defaulFilters)) {
            if (!isset($this->supportedFiltersForQueryOptimizer['allowedDefaultFilters'])) return false;
            foreach ($defaulFilters AS $defaultFilter) {
                if (!in_array(\AtpCore\Format::lowercase($defaultFilter), array_map("\AtpCore\Format::lowercase", $this->supportedFiltersForQueryOptimizer['allowedDefaultFilters']))) {
                    return false;
                }
            }
        }

        // Return
        return true;
    }

    /**
     * Prepare input-data (specific for entity)
     *
     * @return array
     */
    public abstract function prepareInputData();

    /**
     * Prepare input-data (default)
     *
     * @param  array $data
     * @param  array $overrule
     */
    public function prepareInputDataDefault($data, $overrule = [])
    {
        // Unset specific database-fields (if available)
        if (isset($data['id']) && !in_array('id', $overrule)) unset($data['id']);
        if (isset($data['created']) && !in_array('created', $overrule)) unset($data['created']);
        if (isset($data['lastUpdated']) && !in_array('lastUpdated', $overrule)) unset($data['lastUpdated']);

        $this->inputData = $data;
    }

    /**
     * Set filter-associations of entity
     *
     * @param array $filterAssociations
     */
    public function setFilterAssociations($filterAssociations)
    {
        $this->filterAssociations = $filterAssociations;
    }

    /**
     * Set Hydrator
     *
     * @param DoctrineObject $hydrator
     */
    public function setHydrator(DoctrineObject $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * Set input filter
     *
     * @param  InputFilterInterface $inputFilter
     * @return object
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;

        return $this;
    }

    /**
     * Set the ObjectName
     *
     * @param $entityNamespace
     */
    public function setObjectName($entityNamespace)
    {
        $this->objectName = $entityNamespace;
    }

    /**
     * Set the ServiceManager
     *
     * @param $container
     */
    public function setServiceManager($container)
    {
        $this->serviceManager = $container;
    }

    /**
     * Set supported filter for query optimizer (first request PK of results by filter and then request data of results by PK)
     *
     * @param array $filters
     */
    public function setSupportedFiltersForQueryOptimizer($filters)
    {
        $this->supportedFiltersForQueryOptimizer = $filters;
    }

    /**
     * Transform object-data into usable data
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    public function transformData($data, $fields = null)
    {
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] AS $k => $record) {
                $data['results'][$k] = $this->transformRecord($record, $fields);
            }
        } elseif (is_array($data) && !isset($data['id'])) {
            foreach ($data AS $k => $record) {
                $data[$k] = $this->transformRecord($record, $fields);
            }
        } else {
            $data = $this->transformRecord($data, $fields);
        }
        return $data;
    }

    /**
     * Transform object-record into usable record
     *
     * @param array $record
     * @param array $fields
     * @return array
     */
    public function transformRecord($record, $fields = null)
    {
        // Get data-fields configured by application
        $dataFields = $this->options->getDataFields();
        $recordOrig = $record;

        // Get fields requested by user (if available, else all configured data-fields)
        if (!empty($fields)) {
            $requestedFields = $this->getRequestedFields($dataFields, $fields);
        } else {
            $requestedFields = $dataFields;
        }

        // Check unknown properties of record
        $processedFields = [];
        foreach ($requestedFields["fields"] AS $fieldName) {
            if (!array_key_exists($fieldName, $record)) {
                $values = $this->transformValues($record, [$fieldName], $recordOrig);
                if (array_key_exists($fieldName, $values)) {
                    $record[$fieldName] = $values[$fieldName];
                }
                $processedFields[] = $fieldName;
            }
        }

        // Iterate data-fields
        $requestedFields["fields"] = array_map("\AtpCore\Format::lowercase", $requestedFields["fields"]);
        foreach ($record AS $fieldName => $fieldValue) {
            // Skip field if not configured for application
            if (!in_array(Format::lowercase($fieldName), $requestedFields["fields"]) && !array_key_exists($fieldName, $requestedFields["entities"])) {
                unset($record[$fieldName]);
                continue;
            }

            // Transform/unset values
            if (in_array(Format::lowercase($fieldName), $requestedFields["fields"]) && !in_array($fieldName, $processedFields)) {
                // Overwrite values
                $values = $this->transformValues($record, [$fieldName], $recordOrig);
                if (array_key_exists($fieldName, $values)) {
                    $record[$fieldName] = $values[$fieldName];
                } elseif (is_object($record[$fieldName]) || is_array($record[$fieldName])) {
                    $record[$fieldName] = null;
                }
            } elseif (array_key_exists($fieldName, $requestedFields["entities"])) {
                // Overwrite values
                $fields = $requestedFields["entities"][$fieldName];
                $values = $this->transformValues($fieldValue, $fields, $fieldValue);
                if (!empty($values)) $record[$fieldName] = $values;
                else unset($record[$fieldName]);
            } elseif (is_object($fieldValue) && !($fieldValue instanceof DateTime)) {
                // Unset data-field if value is object
                unset($record[$fieldName]);
            }
        }

        return $record;
    }

    /**
     * Transform object-values into usable values
     *
     * @param mixed $data
     * @param array $fields
     * @param mixed $dataOrig
     * @return array
     */
    public function transformValues($data, $fields, $dataOrig = null)
    {
        if (empty($fields)) return null;
        if (empty($data)) return null;

        if ($data instanceof PersistentCollection) {
            if (count($data) < 1) return null;

            $values = [];
            foreach ($data AS $k => $v) {
                $values[$k] = $this->transformValues($v, $fields, $dataOrig[$k]);
            }
        } else {
            if (is_object($data)) {
                // Get repository-class by entity-class
                $className = (get_parent_class($data)) ? get_parent_class($data) : get_class($data);
                $repositoryName = str_replace("\Entity\\", "\Repository\\", $className);
                $repositoryName = preg_replace("~Entity(?!.*Entity)~", "Repository", $repositoryName) . 'Repository';
                if (class_exists($repositoryName)) {
                    $repository = $this->getServiceManager()->get($repositoryName);
                } else {
                    $repository = null;
                }
            } elseif (is_array($data)) {
                // Get repository-class
                $repositoryName = get_class($this);
                if (class_exists($repositoryName)) {
                    $repository = $this->getServiceManager()->get($repositoryName);
                } else {
                    $repository = null;
                }
            } else {
                return null;
            }

            $values = [];
            foreach ($fields AS $k => $field) {
                if (is_array($field)) {
                    $func = 'get' . ucfirst($k);
                    $values[$k] = $this->transformValues($data->$func(), $field, $dataOrig->$func());
                } else {
                    $fieldValue = null;

                    // Method-check
                    $func = 'conv' . ucfirst($field);
                    if (!isset($this->methodCheck[$repositoryName]) || !isset($this->methodCheck[$repositoryName][$func])) {
                        if (!isset($this->methodCheck[$repositoryName])) $this->methodCheck[$repositoryName] = [];
                        $this->methodCheck[$repositoryName][$func] = method_exists($repositoryName, $func);
                    }
                    $methodConvCheck = $this->methodCheck[$repositoryName][$func];

                    if (is_object($data)) {
                        // Check if convert-function exists (in corresponding repository-class)
                        if ($methodConvCheck === true) {
                            $fieldValue = $repository->$func($data, $this->config, $dataOrig);
                        } else {
                            // Check if get-function exists (in entity-class)
                            $func = 'get' . ucfirst($field);
                            if (method_exists($data, $func)) {
                                $fieldValue = $data->$func();
                            }
                        }
                    } elseif (is_array($data)) {
                        // Check if convert-function exists (in corresponding repository-class)
                        if ($methodConvCheck === true) {
                            $fieldValue = $repository->$func($data, $this->config, $dataOrig);
                        } else {
                            if (isset($data[$field])) {
                                $fieldValue = $data[$field];
                            }
                        }
                    }
                    $values[$field] = $fieldValue;
                }
            }
        }

        return $values;
    }

    /**
     * Update an existing object
     *
     * @param $id
     * @param $data
     * @param $output
     * @param $refresh
     * @param array $fields
     * @return array|object|bool
     */
    public function update($id, $data, $output = 'object', $refresh = false, $fields = null)
    {
        // Set operation
        $this->operation = __FUNCTION__;

        // Reset errors
        $this->resetErrors();

        // Get existing object
        $object = $this->getObjectManager()
            ->getRepository($this->getObjectName())
            ->find($id);

        if ($object == null) {
            $this->setMessages(['notFound' => $this->objectName. ' not found']);
            return false;
        }

        // Refresh entity (clear all local changes)
        if ($refresh === true) {
            $this->objectManager->refresh($object);
        }

        // Prepare data
        $this->prepareInputDataDefault($data);
        $this->prepareInputData();

        // Set default data (if not available)
        if (property_exists($object, 'lastUpdated') || property_exists($this->getObjectName(), 'lastUpdated')) {
            $this->inputData['lastUpdated'] = new DateTime();
        }

        // Verify data-fields for update
        $res = $this->verifyDataFields(__FUNCTION__);
        if ($res === false) return false;

        // hydrate object, apply inputfilter, save it, and return result
        if ($this->filterAndPersist($this->inputData, $object)) {
            if ($output == 'array') {
                // Return result
                $record = $this->getHydrator()->extract($object);
                if (method_exists($this, 'transformData')) return $this->transformData($record, $fields);
                else return $record;
            } else {
                return $object;
            }
        } else {
            return false;
        }
    }

    /**
     * Update existing objects (in bulk)
     *
     * @param $data
     * @param $output
     * @param $refresh
     * @param array $fields
     * @return array|bool
     */
    public function updateBulk($data, $output = 'object', $refresh = false, $fields = null)
    {
        // Set operation
        $this->operation = __FUNCTION__;

        // Reset errors
        $this->resetErrors();

        // Iterate data
        $objects = [];
        $recordData = [];
        foreach ($data AS $id => $value) {
            // Get existing object
            $object = $this->getObjectManager()
                ->getRepository($this->getObjectName())
                ->find($id);

            // Refresh entity (clear all local changes)
            if ($refresh === true) {
                $this->objectManager->refresh($object);
            }

            if ($object == null) {
                $this->setMessages(['notFound' => $this->objectName. ' not found']);
                return false;
            }

            // Prepare data
            $this->prepareInputDataDefault($value);
            $this->prepareInputData();

            // Set default data (if not available)
            if (property_exists($object, 'lastUpdated') || property_exists($this->getObjectName(), 'lastUpdated')) {
                $this->inputData['lastUpdated'] = new DateTime();
            }
            $recordData[$id] = $this->inputData;
            $objects[$id] = $object;
        }

        // Hydrate object, apply inputfilter, and save it
        if ($this->filterAndPersistBulk($recordData, $objects)) {
            if ($output == 'array') {
                // Return results
                $records = [];
                foreach ($objects AS $key => $object) {
                    $record = $this->getHydrator()->extract($object);
                    if (method_exists($this, 'transformData')) $records[] = $this->transformData($record, $fields);
                    else $records[] = $record;
                }
                return $records;
            } else {
                return $objects;
            }
        } else {
            return false;
        }
    }

    public function verifyDataFields($operation = "update") {
        // Verify data-fields for update
        if (method_exists($this->options, "verifyDataFields")) {
            $this->inputData = $this->options->verifyDataFields($operation, $this->inputData);
            if (empty($this->inputData)) {
                $this->setMessages(["invalidInput"=>"No data for $operation ($this->objectName)"]);
                return false;
            }
        }

        // Return
        return $this->inputData;
    }

    private function sanitizeField($field)
    {
        $field = str_replace(".", "", $field);
        $field = str_replace("(", "", $field);
        $field = str_replace(")", "", $field);
        $field = str_replace(",", "", $field);
        $field = str_replace(" ", "", $field);
        return ucfirst($field);
    }
}