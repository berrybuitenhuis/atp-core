<?php

namespace AtpCore\Zf\Service;

class Service
{
    /**
     * @var mixed
     */
    protected $repository;

    /**
     * @var mixed Error-data
     */
    private $errorData = [];

    /**
     * @var array of Error-messages
     */
    private $messages = [];

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
     * @param null|array $fields
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
     * Set error-data
     *
     * @param $data
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
     * @return array
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
}