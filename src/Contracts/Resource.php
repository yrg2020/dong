<?php
/**
 * base resource class
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace CXD2020\Contracts;

use Illuminate\Support\Fluent;

/**
 * base resource class
 */
abstract class Resource extends Fluent
{
    /**
     * update resource key
     *
     * @param string $key
     * @param mixed  $value
     */
    public function update(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }
}
