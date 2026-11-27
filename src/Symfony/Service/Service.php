<?php

namespace AtpCore\Symfony\Service;

use AtpCore\BaseClass;
use AtpCore\Error;
use AtpCore\Symfony\Repository\AbstractRepository;

/**
 * @template T of object
 * @template R of AbstractRepository<T>
 */
class Service extends BaseClass
{
    /** @var R */
    protected $repository;

    /**
     * @param array $data
     * @param array $overrule
     * @return T|false
     */
    public function create($data, $overrule = [])
    {
        $res = $this->repository->create($data, $overrule);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param array $data
     * @param array $overrule
     * @return T|Error
     */
    public function createNew($data, $overrule = [])
    {
        return $this->repository->createNew($data, $overrule);
    }

    /**
     * @param int $id
     * @param bool $remove
     * @param bool $refresh
     * @return mixed
     */
    public function delete($id, $remove = false, $refresh = false)
    {
        $res = $this->repository->delete($id, $remove, $refresh);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function exists($id)
    {
        return $this->repository->exists($id);
    }

    /**
     * @param int $id
     * @param bool $refresh
     * @return T|false
     */
    public function get($id, $refresh = false)
    {
        $res = $this->repository->get($id, $refresh);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param int $id
     * @param bool $refresh
     * @return T|Error
     */
    public function getNew($id, $refresh = false)
    {
        return $this->repository->getNew($id, $refresh);
    }

    /**
     * @param array $parameters
     * @param bool $multiple
     * @return mixed
     */
    public function getByParameters($parameters, $multiple = true)
    {
        $res = $this->repository->getByParameters($parameters, $multiple);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param array $parameters
     * @param array|null $orderBy
     * @return \AtpCore\Symfony\Doctrine\EntityCollection<T>
     */
    public function getByParametersNew($parameters, $orderBy = null)
    {
        return $this->repository->getByParametersNew($parameters, $orderBy);
    }

    /**
     * @param \Psr\Container\ContainerInterface $serviceManager
     * @return static
     */
    public static function getFromServiceManager($serviceManager)
    {
        return $serviceManager->get(static::class);
    }

    /**
     * Get repository of service
     *
     * @return R
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param int $id
     * @param array $data
     * @param bool $refresh
     * @return T|false
     */
    public function patch($id, $data, $refresh = false)
    {
        $res = $this->repository->update($id, $data, $refresh);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param int $id
     * @param array $data
     * @param bool $refresh
     * @return T|false
     */
    public function update($id, $data, $refresh = false)
    {
        $res = $this->repository->update($id, $data, $refresh);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param int $id
     * @param array $data
     * @param bool $refresh
     * @return T|Error
     */
    public function updateNew($id, $data, $refresh = false)
    {
        return $this->repository->updateNew($id, $data, $refresh);
    }
}