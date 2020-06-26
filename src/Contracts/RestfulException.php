<?php
/**
 * repository exception
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;


/**
 * repository exception
 *
 */
class RestfulException extends \Exception
{

    /**
     * @var array
     */
    private $_errorMessage;

    /**
     * RestfulException constructor.
     * @param array        $errorFromErrorMessage
     * @param string|array $extra
     * @param int          $status
     */
    public function __construct(array $errorFromErrorMessage, $extra = '', $status = 422)
    {
        $extra = is_array($extra) ? json_encode($extra) : $extra;
        parent::__construct($errorFromErrorMessage['message'] . ($extra ? " $extra" : ''));

        $errorFromErrorMessage['httpStatus'] = isset($errorFromErrorMessage['httpStatus']) ?
            $errorFromErrorMessage['httpStatus'] : $status;
        $this->_errorMessage = $errorFromErrorMessage;
    }

    /**
     * @return array
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

}