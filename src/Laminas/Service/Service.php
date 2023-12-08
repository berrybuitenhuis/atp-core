<?php

namespace AtpCore\Laminas\Service;

use AtpCore\BaseClass;

class Service extends BaseClass
{
    /**
     * @var mixed
     */
    protected $repository;

    /**
     * @param array $data
     * @param string $output
     * @param array $overrule
     * @return mixed
     */
    public function create($data, $output = 'object', $overrule = [])
    {
        $res = $this->repository->create($data, $output, $overrule);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
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
     * @param string $output
     * @param bool $refresh
     * @param null $fields
     * @return mixed
     */
    public function get($id, $output = 'object', $refresh = false, $fields = NULL)
    {
        $res = $this->repository->get($id, $output, $refresh, $fields);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param array $parameters
     * @param string $output
     * @param bool $multiple
     * @return mixed
     */
    public function getByParameters($parameters, $output = 'object', $multiple = true)
    {
        $res = $this->repository->getByParameters($parameters, $output, $multiple);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getDefaultFilterOptions()
    {
        $res = $this->repository->getDefaultFilterOptions();
        return $res;
    }

    /**
     * @param string $output
     * @param null|false|array $fields
     * @param null|array $defaultFilter
     * @param null|array $filter
     * @param null|array $groupBy
     * @param null|array $having
     * @param null|array $orderBy
     * @param int $limitRecords
     * @param int $offset
     * @param bool $paginator
     * @param bool $debug
     * @return mixed
     */
    public function getList($output = 'object', $fields = NULL, $defaultFilter = NULL, $filter = NULL, $groupBy = null, $having = null, $orderBy = NULL, $limitRecords = 25, $offset = 0, $paginator = false, $debug = false)
    {
        $res = $this->repository->getList($output, $fields, $defaultFilter, $filter, $groupBy, $having, $orderBy, $limitRecords, $offset, $paginator, $debug);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param int $id
     * @param array $data
     * @param string $output
     * @param bool $refresh
     * @return mixed
     */
    public function patch($id, $data, $output = 'object', $refresh = false)
    {
        $res = $this->repository->update($id, $data, $output, $refresh);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

    /**
     * @param int $id
     * @param array $data
     * @param string $output
     * @param bool $refresh
     * @return mixed
     */
    public function update($id, $data, $output = 'object', $refresh = false)
    {
        $res = $this->repository->update($id, $data, $output, $refresh);
        if ($res === false) {
            $this->setMessages($this->repository->getMessages());
            $this->setErrorData($this->repository->getErrorData());
        }
        return $res;
    }

}