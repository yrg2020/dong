<?php

/**
 * resource repository interface
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;


/**
 * resource repository interface
 */
interface ICollectionFindOptionParser
{

    /**
     * @param CollectionFindOption $findOption
     * @param mixed $context
     *
     * @return void
     */
    public function parse(CollectionFindOption $findOption, $context);

}