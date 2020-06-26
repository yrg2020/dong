<?php
/**
 * read only resource trait
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Controller\Traits;

/**
 * read only resource trait
 */
trait ReadOnlyResource
{

    /**
     * @var array
     */
    private $_traitAllowedMethods = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * @return array
     */
    public function allowedMethods()
    {
        return $this->_traitAllowedMethods;
    }

}