<?php

namespace AtpCore\Laminas\Options;

/**
 * Class OptionsInterface
 */
interface OptionsInterface
{
    /*
     * Get filter for client
     *
     * @return array
     */
    public function getClientFilter();

    /**
     * Get default filter-options
     *
     * @return array
     */
    public function getDefaultFilterOptions();

    /**
     * Transform filter-name(s) into filters
     *
     * @param array $filters
     * @return array|null
     */
    public function getDefaultFilter($filters);

    /**
     * Get data-fields for client
     *
     * @return array
     * @throws \Exception
     */
    public function getDataFields();

}
