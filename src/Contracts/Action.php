<?php
/**
 * action
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;


/**
 * action
 *
 * @property mixed      $data
 * @property mixed|null $meta
 */
final class Action extends Resource
{
    /**
     * Document constructor.
     *
     * @param mixed $data data
     * @param null  $meta
     */
    public function __construct($data, $meta = null)
    {
        $attrs = ['data' => $data];
        if ($meta) {
            $attrs['meta'] = $meta;
        }
        parent::__construct($attrs);
    }

}