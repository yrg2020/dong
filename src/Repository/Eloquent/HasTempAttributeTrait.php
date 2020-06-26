<?php
/**
 * has template attribute trait
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace CXD2020\Repository\Eloquent;

/**
 * has template attribute trait
 */
trait HasTempAttributeTrait
{
    /**
     * @var array
     */
    private $_tempAttributes = [];

    /**
     * @return array
     */
    public function getTempAttributeKeys(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getTempAttributes(): array
    {
        return $this->_tempAttributes;
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasTempAttribute($key): bool
    {
        return in_array($key, $this->getTempAttributeKeys());
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getTempAttribute($key, $default = null)
    {
        return array_key_exists($key, $this->_tempAttributes) ?
            $this->_tempAttributes[$key] : $default;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setTempAttribute($key, $value)
    {
        $this->_tempAttributes[$key] = $value;
        return $this;
    }
}
