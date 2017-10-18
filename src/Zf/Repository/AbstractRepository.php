<?php

namespace AtpCore\Zf\Repository;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Doctrine\ORM\EntityManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;

/**
 * Class AbstractRepository
 */
abstract class AbstractRepository implements InputFilterAwareInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $om;

    /**
     * @var \DoctrineModule\Stdlib\Hydrator\DoctrineObject
     */
    private $hydrator;

    /**
     * @var Name of the \Doctrine\ORM\EntityRepository
     */
    protected $objectName;

    /**
     * @var List of filter-associations
     */
    protected $filterAssociations;

    /**
     * @var inputData
     */
    protected $inputData;

    /**
     * @var InputFilter
     */
    protected $inputFilter;

    /**
     * @var Error-messages
     */
    private $messages;

    /**
     * @var Error-data
     */
    private $errorData;

    /**
     * Constructor
     *
     * @param EntityManager $objectManager
     */
    public function __construct(EntityManager $objectManager = null)
    {
        if (!empty($objectManager)) {
            $this->om = $objectManager;
        }
        $this->messages = [];
        $this->errorData = [];
    }

    /**
     * Get ObjectManager
     *
     * @return EntityManager
     */
    public function getObjectManager()
    {
        return $this->om;
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
     * Get the ObjectName
     *
     * @return mixed
     */
    public function getObjectName()
    {
        return $this->objectName;
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
            $this->hydrator = new DoctrineObject($this->om);
        }

        return $this->hydrator;
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
     * Get input filter
     *
     * @return object
     */
    public abstract function getInputFilter();

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
     * Set filter-associations of entity
     *
     * @param array $filterAssociations
     */
    public function setFilterAssociations($filterAssociations)
    {
        $this->filterAssociations = $filterAssociations;
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
     * Set error-data
     *
     * @param $data
     * @return array
     */
    public function setErrorData($data)
    {
        $this->errorData = $data;
    }

    /**
     * Get error-data
     *
     * @return array
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Set error-message
     *
     * @param array $messages
     */
    public function setMessages($messages)
    {
        if (!is_array($messages)) $messages = [$messages];
        $this->messages = $messages;
    }

    /**
     * Add error-message
     *
     * @param array $message
     */
    public function addMessage($message)
    {
        if (!is_array($message)) $message = [$message];
        $this->messages = array_merge($this->messages, $message);
    }

    /**
     * Get error-messages
     *
     * @return array|Error
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Reset error-messages and error-data
     */
    public function resetErrors()
    {
        $this->messages = [];
        $this->errorData = [];
    }

    /**
     * Prepare input-data (default)
     *
     * @param  array $data
     * @param  array $overrule
     * @return array
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
     * Prepare input-data (specific for entity)
     *
     * @return array
     */
    public abstract function prepareInputData();

    /**
     * Hydrate object, apply inputfilter, save it, and return result
     *
     * @param $data
     * @param $object
     * @return bool
     */
    public function filterAndPersist($data, &$object, $flush = true)
    {
        // Hydrate data to object
        $this->getHydrator()->hydrate($data, $object);

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
                $this->getObjectManager()->persist($object);

                // Only flush (if permitted, used for bulk mutations)
                if ($flush === true) {
                    $this->getObjectManager()->flush();
                }
            } catch (Exception $e) {
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
            } catch (Exception $e) {
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
     * Transform object-data into usable data
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    public function transformData($data, $fields = NULL)
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

    public abstract function transformRecord($record, $fields = NULL);

    /**
     * Transform object-values into usable values
     *
     * @param mixed $data
     * @param array $fields
     * @return array
     */
    public function transformValues($data, $fields)
    {
        if (empty($fields)) return;
        if (empty($data)) return;

        if ($data instanceof \Doctrine\ORM\PersistentCollection) {
            if (count($data) < 1) return;

            $values = [];
            foreach ($data AS $k => $v) {
                $values[$k] = $this->transformValues($v, $fields);
            }
        } else {
            $values = [];
            foreach ($fields AS $k => $field) {
                if (is_array($field)) {
                    $func = 'get' . ucfirst($k);
                    $values[$k] = $this->transformValues($data->$func(), $field);
                } else {
                    $fieldValue = "";

                    if (is_object($data)) {
                        // Check if get-function exists (in entity-class)
                        $func = 'get' . ucfirst($field);
                        if (!method_exists($data, $func)) {
                            // Get repository-class by entity-class
                            $className = (get_parent_class($data)) ? get_parent_class($data) : get_class($data);
                            $repository = str_replace("\Entity\\", "\Repository\\", $className);
                            $repository = preg_replace("~Entity(?!.*Entity)~", "Repository", $repository) . 'Repository';

                            // Check if convert-function exists (in corresponding repository-class)
                            $func = 'conv' . ucfirst($field);
                            if (method_exists($repository, $func)) {
                                $fieldValue = $repository::$func($data, $this->config['application']);
                            }
                        } else {
                            $fieldValue = $data->$func();
                        }
                    } elseif (is_array($data)) {
                        $fieldValue = $data[$field];
                    }
                    $values[$field] = $fieldValue;
                }
            }
        }

        return $values;
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
            if (stristr($customField,"-")) {
                // Get fields (explode by "-")
                $fieldParts = explode("-", $customField);
                $configuredFields = $dataFields["entities"];
                foreach($fieldParts AS $k1 => $v1) {
                    // Check if field-part is entity
                    if (isset($configuredFields[$v1])) {
                        // Check if fieldPart is last, then set entity (array of properties) to fieldParts
                        if (count($fieldParts) == ($k1 + 1)) {
                            array_push($fieldParts, $configuredFields[$v1]);
                        }
                        $configuredFields = $configuredFields[$v1];
                    } elseif (in_array($v1, $configuredFields)) {
                        // Check if field-part is property, then set property (array) to fieldParts
                        $fieldParts[$k1] = [$v1];
                    } else {
                        // Field-part is no entity or property (so unset fieldParts, maybe not configured or misspelled)
                        unset($fieldParts);
                        break;
                    }
                }

                if (is_array($fieldParts) && !empty($fieldParts)) {
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
            } elseif (in_array(strtolower($customField), $dataFields['fields'])) {
                // Add field to requestedFields
                $requestedFields["fields"][] = strtolower($customField);
            } elseif (array_key_exists($customField, $dataFields['entities'])) {
                // Add entire entity to requestedFields (no fields of entity specified)
                $requestedFields["entities"][$customField] = $dataFields['entities'][$customField];
            }
        }

        // Return
        return $requestedFields;
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
        $object = $this->om
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
     * Return a single object from the repository
     *
     * @param $id
     * @param $output
     * @param $refresh
     * @param array $fields
     * @return object|array
     */
    public function get($id, $output = 'object', $refresh = false, $fields = NULL)
    {
        // get object from the repository specified by primary key
        if ($output == 'array') {
            $filter = ["AND"=>[["id", "eq", $id]]];
            $objects = $this->getByFilter($filter, null, null, null, 1, false);
            $object = current($objects);
        } else {
            $object = $this->om
                ->getRepository($this->objectName)
                ->find($id);

            // refresh entity (clear all local changes)
            if ($refresh === true) {
                $this->om->refresh($object);
            }
        }

        // return error if object not found
        if ($object == null || $object === false) {
            $this->setMessages(['notFound' => $this->objectName. ' not found']);
            return false;
        }

        // return
        if ($output == 'array') {
            $record = $this->getHydrator()->extract($object);

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
     * @param $output
     * @param $refresh
     * @param array $fields
     * @return object|array
     */
    public function getAll($output = 'object', $refresh = false, $fields = NULL)
    {
        // get all objects from the repository
        $objects = $this->om
            ->getRepository($this->objectName)
            ->findAll();

        // refresh entity (clear all local changes)
        if ($refresh === true) {
            $this->om->refresh($objects);
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
     * Return a list of objects from the repository
     *
     * @param string $output
     * @param array $fields
     * @param array $filter
     * @param array $groupBy
     * @param array $having
     * @param array $orderBy
     * @param integer $limitRecords
     * @param integer $offset
     * @param boolean $paginator
     * @param boolean $debug
     * @return array/object
     */
    public function getList($output = 'object', $fields = NULL, $filter = NULL, $groupBy = null, $having = null, $orderBy = NULL, $limitRecords = 25, $offset = 0, $paginator = false, $debug = false)
    {
        if (!empty($limitRecords)) $limit['limit'] = (int) $limitRecords;
        else $limit['limit'] = 25;
        $limit['offset'] = $offset;
        if (!is_array($filter)) $filter = array();

        // Get results
        $records = $this->getByFilter($filter, $groupBy, $having, $orderBy, $limit, $paginator, $debug);

        // Convert object to array (if output is array)
        if ($output == 'array') {
            $hydrator = $this->getHydrator();
            if ($paginator === true) {
                foreach ($records['results'] AS $k => $v) {
                    $records['results'][$k] = $hydrator->extract($v);
                }
            } else {
                foreach ($records AS $k => $v) {
                    $records[$k] = $hydrator->extract($v);
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
     * Return objects by filter
     *
     * @param $filter
     * @param $groupBy
     * @param $having
     * @param $orderBy
     * @param $limit
     * @param $paginator
     * @param $debug
     * @return array/object
     * @throws \Exception
     */
    public function getByFilter($filter = NULL, $groupBy = null, $having = null, $orderBy = null, $limit = NULL, $paginator = false, $debug = false)
    {
        // Get default-filter
        $defaultFilter = $this->options->getDefaultFilter();

        // Build query
        $query = $this->om->createQueryBuilder();
        if ($paginator) $queryPaginator = $this->om->createQueryBuilder();
        $parameters = [];

        // Set fields
        $query->select('f');
        if ($paginator) $queryPaginator->select(array('COUNT(f.id) total'));
        // Set from
        $query->from($this->objectName, 'f');
        if ($paginator) $queryPaginator->from($this->objectName, 'f');
        // Set joins (if available/needed)
        if ((!empty($filter) || !empty($defaultFilter) || !empty($orderBy)) && !empty($this->getFilterAssociations())) {
            $joins = [];
            foreach ($this->getFilterAssociations() AS $filterAssociation) {
                $match = false;
                if (!empty($filter["AND"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($filter["AND"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($filter["OR"]) && !empty(preg_grep('/' . $filterAssociation['alias'] . "." . '/', array_column($filter["OR"], 0))) && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (stristr($defaultFilter['filter'], $filterAssociation['alias'] . ".") && !in_array($filterAssociation['alias'], $joins)) {
                    $match = true;
                } elseif (!empty($orderBy)) {
                    foreach ($orderBy AS $orderByField) {
                        if (stristr($orderByField['field'], $filterAssociation['alias'] . ".") && !in_array($filterAssociation['alias'], $joins)) {
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
                            $query->leftJoin($filterAssociationJoin['join'], $filterAssociationJoin['alias']);
                            if ($paginator) $queryPaginator->leftJoin($filterAssociationJoin['join'], $filterAssociationJoin['alias']);
                        }
                    }
                    // Set association
                    $joins[] = $filterAssociation['alias'];
                    $query->leftJoin($filterAssociation['join'], $filterAssociation['alias']);
                    if ($paginator) $queryPaginator->leftJoin($filterAssociation['join'], $filterAssociation['alias']);
                }
            }
        }
        // Set customized filter (if available)
        if (!empty($filter)) {
            $allowedOperators = ['eq','neq','like','lt','lte','gt','gte','isnull','isnotnull','in','notin'];
            // Set AND-conditions (if available)
            if (isset($filter['AND'])) {
                $filterConditions = $query->expr()->andX();
                // Iterate conditions
                foreach ($filter['AND'] AS $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $value = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array($operator, $allowedOperators)) throw new \Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, $value));
                }
                // Add filter-conditions to query
                $query->andWhere($filterConditions);
                if ($paginator) $queryPaginator->andWhere($filterConditions);
            }
            // Set OR-conditions (if available)
            if (isset($filter['OR'])) {
                $filterConditions = $query->expr()->orX();
                // Iterate conditions
                foreach ($filter['OR'] AS $filterParams) {
                    $field = (stristr($filterParams[0], ".")) ? $filterParams[0] : "f." . $filterParams[0];
                    $operator = $filterParams[1];
                    $value = $filterParams[2];

                    // Check if operator is allowed
                    if (!in_array($operator, $allowedOperators)) throw new \Exception("Not allowed operator: " . $operator);
                    // Set filter-condition
                    $filterConditions->add($query->expr()->{$operator}($field, $value));
                }
                // Add filter-conditions to query
                $query->orWhere($filterConditions);
                if ($paginator) $queryPaginator->orWhere($filterConditions);
            }
        }
        // Set default-filter
        if (!empty($defaultFilter)) {
            $query->andWhere($defaultFilter['filter']);
            if ($paginator) $queryPaginator->andWhere($defaultFilter['filter']);
            $parameters = (empty($parameters)) ? $defaultFilter['parameters'] : array_merge($parameters, $defaultFilter['parameters']);
        }
        // Set group-by (if available)
        if (!empty($groupBy)) {
            foreach ($groupBy AS $groupByField) {
                $groupByField = (stristr($groupByField, ".")) ? $groupByField : "f." . $groupByField;
                $query->addGroupBy($groupByField);
                if ($paginator) $queryPaginator->addGroupBy($groupByField);
            }
        }
        // Set having (if available)
        if (!empty($having)) {
            $query->having($having['filter']);
            if ($paginator) {
                $queryPaginator->having($having['filter']);
                // Prevent error "Unknown column in having clause"
                $queryPaginator->addSelect($having['fields']);
                // Prevent error "In aggregated query without GROUP BY"
                if (empty($groupBy)) {
                    foreach ($having['groupBy'] AS $groupByField) {
                        $groupByField = (stristr($groupByField, ".")) ? $groupByField : "f." . $groupByField;
                        $queryPaginator->addGroupBy($groupByField);
                    }
                }
            }
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
            if ($paginator) $queryPaginator->setParameters($parameters);
        }

        // Return DQL (in debug-mode)
        if ($debug) {
            return array("results"=>array("query"=>$query->getQuery()->getDQL(), "parameters"=>$parameters));
        }

        // Get results
        if ($paginator) {
            // Set paginator-results
            $paginatorResults = $queryPaginator->getQuery()->getOneOrNullResult();
            $paginatorData['records'] = (int) $paginatorResults['total'];
            $paginatorData['pages'] = (int) ceil($paginatorResults['total'] / $limit['limit']);
            $paginatorData['currentPage'] = (int) (ceil($limit['offset'] / $limit['limit']) + 1);
            $paginatorData['recordsPage'] = (int) $limit['limit'];

            // Get "page"-results
            $results = $query->getQuery()->getResult();

            // Return
            return array("paginator"=>$paginatorData, "results"=>$results);
        } else {
            // Return
            return $query->getQuery()->getResult();
        }
    }

    /**
     * Return all objects from the repository with parameters
     *
     * @param array $parameters
     * @param string $output [object, array]
     * @param boolean $multiple
     * @return array/object
     */
    public function getByParameters($parameters, $output = 'object', $multiple = true)
    {
        // get object from the repository specified by primary key
        $objects = $this->om
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
        } else {
            if ($multiple === false) {
                return current($objects);
            } else {
                return $objects;
            }
        }
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
     * Create a new object
     *
     * @param $data
     * @param $output
     * @param $overrule
     * @param array $fields
     * @return array
     */
    public function create($data, $output = 'object', $overrule = [], $fields = NULL)
    {
        // Reset errors
        $this->resetErrors();

        // Create object instance
        $object = new $this->objectName();

        // Prepare data
        $this->prepareInputDataDefault($data, $overrule);
        $this->prepareInputData();

        // Set default data (if not available)
        if (property_exists($object, 'created')) $this->inputData['created'] = new \DateTime();
        if (property_exists($object, 'status')) $this->inputData['status'] = true;

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
     * @return array
     */
    public function createBulk($data, $output = 'object', $overrule = [], $fields = NULL)
    {
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
            if (property_exists($objects[$key], 'created')) $this->inputData['created'] = new \DateTime();
            if (property_exists($objects[$key], 'status')) $this->inputData['status'] = true;
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
            } else {
                return $objects;
            }
        } else {
            return false;
        }
    }

    /**
     * Update an existing object
     *
     * @param $id
     * @param $data
     * @param $output
     * @param $refresh
     * @param array $fields
     * @return array
     */
    public function update($id, $data, $output = 'object', $refresh = false, $fields = NULL)
    {
        // Reset errors
        $this->resetErrors();

        // Get existing object
        $object = $this->getObjectManager()
            ->getRepository($this->getObjectName())
            ->find($id);

        // Refresh entity (clear all local changes)
        if ($refresh === true) {
            $this->om->refresh($object);
        }

        if ($object == null) {
            $this->setMessages(['notFound' => $this->objectName. ' not found']);
            return false;
        }

        // Prepare data
        $this->prepareInputDataDefault($data);
        $this->prepareInputData();

        // Set default data (if not available)
        if (property_exists($object, 'lastUpdated')) $this->inputData['lastUpdated'] = new \DateTime();

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
     * @return array
     */
    public function updateBulk($data, $output = 'object', $refresh = false, $fields = NULL)
    {
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
                $this->om->refresh($object);
            }

            if ($object == null) {
                $this->setMessages(['notFound' => $this->objectName. ' not found']);
                return false;
            }

            // Prepare data
            $this->prepareInputDataDefault($value);
            $this->prepareInputData();

            // Set default data (if not available)
            if (property_exists($object, 'lastUpdated')) $this->inputData['lastUpdated'] = new \DateTime();
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

    /**
     * Delete an object from the repository
     *
     * @param $id
     * @param $remove
     * @param $refresh
     * @return array
     */
    public function delete($id, $remove = false, $refresh = false)
    {
        // Reset errors
        $this->resetErrors();

        // get object from the repository specified by primary key
        $object = $this->om
            ->getRepository($this->objectName)
            ->find($id);

        // refresh entity (clear all local changes)
        if ($refresh === true) {
            $this->om->refresh($object);
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
                $this->om->remove($object);
                $this->om->flush();
                return true;
            } catch (Exception $e) {
                $this->setMessages($e->getMessage());
                return false;
            }
        }
    }

}