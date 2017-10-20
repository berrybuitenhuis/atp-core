<?php

namespace AtpCore\Zf\Options;

/**
 * Class AbstractOptions
 */
abstract class AbstractOptions
{

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /*
     * Get filter for client
     *
     * @return array
     */
    public abstract function getClientFilter();

    /**
     * Get default filter-options
     *
     * @return array
     */
    public abstract function getDefaultFilterOptions();

    /**
     * Transform filter-name(s) into filters
     *
     * @param array $filters
     * @return array|null
     */
    public abstract function getDefaultFilter($filters);

    /**
     * Get data-fields for client
     *
     * @return array
     * @throws \Exception
     */
    public abstract function getDataFields();

}
