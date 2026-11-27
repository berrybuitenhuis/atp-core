<?php

namespace AtpCore\Symfony\Repository;

use AtpCore\BaseClass;
use AtpCore\Error;
use AtpCore\Symfony\Hydrator\DoctrineHydrator;
use AtpCore\Symfony\Write\UpdatableFields;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * @template T of object
 */
abstract class AbstractRepository extends BaseClass
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var DoctrineHydrator
     */
    private $hydrator;

    /**
     * @var array inputData
     */
    protected $inputData;

    /**
     * @var ValidatorInterface|null
     */
    protected $validator;

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

    private ?UpdatableFields $updatableFields = null;

    /**
     * @var ContainerInterface
     */
    protected $serviceManager;

    /**
     * Constructor
     *
     * @param EntityManager|null $objectManager
     * @param ValidatorInterface|null $validator
     */
    public function __construct(?EntityManager $objectManager = null, ?ValidatorInterface $validator = null)
    {
        if (!empty($objectManager)) {
            $this->objectManager = $objectManager;
        }
        $this->validator = $validator;
        $this->resetErrors();
    }

    /**
     * Create a new object
     *
     * @param $data
     * @param $overrule
     * @return T|false
     */
    public function create($data, $overrule = [])
    {
        // Set operation
        $this->operation = __FUNCTION__;

        // Reset errors
        $this->resetErrors();

        // Create object-instance
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
            return $object;
        } else {
            return false;
        }
    }

    /**
     * Create a new object
     *
     * @param $data
     * @param $overrule
     * @return T|Error
     */
    public function createNew($data, $overrule = [])
    {
        // Set operation
        $this->operation = __FUNCTION__;

        // Reset errors
        $this->resetErrors();

        // Create object-instance
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
            return $object;
        } else {
            return new Error(messages: ["Record creation failed for $this->objectName"], stackTrace: debug_backtrace());
        }
    }

    /**
     * Create new objects (in bulk)
     *
     * @param $data
     * @param $overrule
     * @return array<T>|false
     */
    public function createBulk($data, $overrule = [])
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
            return $objects;
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
     * @return T|bool
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
            $result = $this->update($id, ['status'=>false]);
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

        // Validate input data against constraints (if defined in repository)
        if ($this->validator !== null) {
            $constraints = $this->getConstraints();
            if ($constraints !== null) {
                $violations = $this->validator->validate($data, $constraints, $this->getValidationGroups());
                if (count($violations) > 0) {
                    $messages = [];
                    foreach ($violations as $violation) {
                        $path = $violation->getPropertyPath() ?: 'general';
                        $messages[$path] = $violation->getMessage();
                    }
                    $this->addMessage($messages);
                }
            }

            // Validate object (e.g. Assert attributes on entity)
            if (empty($this->messages)) {
                $violations = $this->validator->validate($object, null, $this->getValidationGroups());
                if (count($violations) > 0) {
                    $messages = [];
                    foreach ($violations as $violation) {
                        $path = $violation->getPropertyPath() ?: 'general';
                        $messages[$path] = $violation->getMessage();
                    }
                    $this->addMessage($messages);
                }
            }
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
     * @param boolean $refresh
     * @return T|false
     */
    public function get($id, $refresh = false)
    {
        // get object from the repository specified by primary key
        $object = $this->objectManager
            ->getRepository($this->objectName)
            ->find($id);

        // return error if object not found
        if ($object == null) {
            $this->setMessages(['notFound' => $this->objectName. ' not found']);
            return false;
        }

        // refresh entity (clear all local changes)
        if ($refresh === true) {
            $this->objectManager->refresh($object);
        }

        // return
        return $object;
    }

    /**
     * Return a single object from the repository
     *
     * @param int $id
     * @param boolean $refresh
     * @return T|Error
     */
    public function getNew($id, $refresh = false)
    {
        // Get object by primary key
        $object = $this->objectManager
            ->getRepository($this->objectName)
            ->find($id);

        // Refresh entity (clear all local changes)
        if ($refresh === true) {
            $this->objectManager->refresh($object);
        }

        // Check if object found
        if ($object == null) {
            return new Error(messages: ["No results found for $this->objectName (id: $id)"], stackTrace: debug_backtrace());
        }

        // Return
        return $object;
    }

    /**
     * Return all objects from the repository
     *
     * @param bool $refresh
     * @return list<T>
     */
    public function getAll($refresh = false)
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

        // return
        return $objects;
    }



    /**
     * Return all objects from the repository with parameters
     *
     * @param array $parameters
     * @param boolean $multiple
     * @return ($multiple is true ? list<T>|false : T|false)
     */
    public function getByParameters($parameters, $multiple = true)
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

        // return
        if ($multiple === false) {
            return current($objects);
        } else {
            return $objects;
        }
    }

    /**
     * Return collection of objects with parameters
     *
     * @param array $parameters
     * @param array|null $orderBy
     * @return \AtpCore\Symfony\Doctrine\EntityCollection<T>
     */
    public function getByParametersNew($parameters, $orderBy = null)
    {
        // Get object(s) by filter
        $objects = $this->objectManager
            ->getRepository($this->objectName)
            ->findBy($parameters, $orderBy);

        // Return
        return new \AtpCore\Symfony\Doctrine\EntityCollection($this->objectName, ($objects == null) ? [] : $objects);
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
        $records = $this->getByParameters(['id'=>$id], $multiple);
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
        $records = $this->getByParameters([$searchField=>$value], $multiple);
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
     * @param ContainerInterface $serviceManager
     * @return static
     */
    public static function getFromServiceManager($serviceManager)
    {
        return $serviceManager->get(static::class);
    }

    /**
     * Get Hydrator
     *
     * @return DoctrineHydrator
     */
    public function getHydrator()
    {
        // create hydrator if not created yet
        if ($this->hydrator === null) {
            $this->hydrator = new DoctrineHydrator($this->objectManager);
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
        $records = $this->getByParameters([$field=>$value], $multiple);
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
     * Get constraints for input data validation
     *
     * Override in repository to define validation constraints.
     *
     * @return Constraint|array|null
     */
    protected function getConstraints(): Constraint|array|null
    {
        return null;
    }

    /**
     * Get validation groups for Symfony Validator
     *
     * @return array|null
     */
    protected function getValidationGroups(): ?array
    {
        return null;
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
     * Get the ServiceManager
     *
     * @return mixed
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
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
     * Set Hydrator
     *
     * @param DoctrineHydrator $hydrator
     */
    public function setHydrator(DoctrineHydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * Set update-whitelist provider (always checked on update)
     */
    #[Required]
    public function setUpdatableFields(UpdatableFields $updatableFields): void
    {
        $this->updatableFields = $updatableFields;
    }

    /**
     * Set Validator
     *
     * @param ValidatorInterface $validator
     * @return static
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;

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
     * Update an existing object
     *
     * @param $id
     * @param $data
     * @param $refresh
     * @return T|false
     */
    public function update($id, $data, $refresh = false)
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
            return $object;
        } else {
            return false;
        }
    }

    /**
     * Update an existing object
     *
     * @param $id
     * @param $data
     * @param $refresh
     * @return T|Error
     */
    public function updateNew($id, $data, $refresh = false)
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
            return new Error(messages: ["No results found for $this->objectName (id: $id)"], stackTrace: debug_backtrace());
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
        if ($res === false) {
            return new Error(messages: ["Invalid data/fields provided for $this->objectName (id: $id)"], stackTrace: debug_backtrace());
        }

        // hydrate object, apply inputfilter, save it, and return result
        if ($this->filterAndPersist($this->inputData, $object)) {
            return $object;
        } else {
            return new Error(messages: ["Record update failed for $this->objectName (id: $id)"], stackTrace: debug_backtrace());
        }
    }

    /**
     * Update existing objects (in bulk)
     *
     * @param $data
     * @param $refresh
     * @return array<T>|false
     */
    public function updateBulk($data, $refresh = false)
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

            // Verify data-fields for update
            if ($this->verifyDataFields(__FUNCTION__) === false) return false;
            $recordData[$id] = $this->inputData;
            $objects[$id] = $object;
        }

        // Hydrate object, apply inputfilter, and save it
        if ($this->filterAndPersistBulk($recordData, $objects)) {
            return $objects;
        } else {
            return false;
        }
    }

    public function verifyDataFields($operation = "update") {
        // Get update-whitelist of the resource for the current client (null = no whitelist)
        if ($this->updatableFields === null) throw new \LogicException("UpdatableFields not set for " . static::class);
        $updatable = $this->updatableFields->forEntity($this->objectName);

        // Keep whitelisted keys (exact, case-sensitive like legacy)
        if ($updatable !== null) {
            $fields = array_intersect_key($this->inputData, array_flip($updatable));
            if (empty($fields)) {
                $this->setMessages(["invalidInput"=>"No data for $operation ($this->objectName)"]);
                return false;
            }
            if (array_key_exists('lastUpdated', $this->inputData)) $fields['lastUpdated'] = $this->inputData['lastUpdated'];
            $this->inputData = $fields;
        }

        // Return
        return $this->inputData;
    }

}