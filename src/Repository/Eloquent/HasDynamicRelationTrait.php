<?php
/**
 * has dynamic relation
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace DONG2020\Repository\Eloquent;

/**
 * has dynamic relation
 */
trait HasDynamicRelation
{

    private static $_dRelations = [];

    /**
     * @param $name
     * @param $closure
     */
    public static function addDynamicRelation($name, $closure)
    {
        static::$_dRelations[$name] = $closure;
    }

    /**
     * @param $name
     * @return bool
     */
    public static function hasDynamicRelation($name)
    {
        return array_key_exists($name, static::$_dRelations);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        /** @var \Illuminate\Database\Eloquent\Model $self */
        $self = $this;
        if (static::hasDynamicRelation($name)) {
            if ($self->relationLoaded($name)) {
                return $self->relations[$name];
            }
            return $self->getRelationshipFromMethod($name);
        }
        return parent::__get($name);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (static::hasDynamicRelation($name)) {
            return call_user_func(static::$_dRelations[$name], $this);
        }
        return parent::__call($name, $arguments);
    }

}
