<?php
/**
 * base model
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Repository\Eloquent;


use Illuminate\Database\Eloquent\Relations\Relation;


/**
 * base model
 *
 */
trait ModelRelationTrait
{

    private static $_traitAllRelationships = [];
    /**
     * @var array
     */
    private static $_traitRelations = [];

    /**
     * @return void
     */
    public function fireRelationEvent()
    {
        $this->fireModelEvent('relationSaved', false);
    }

    /**
     * @var array
     *
     * @return array
     */
    public function getRelationships()
    {
        $class = get_called_class();
        if (!isset(self::$_traitAllRelationships[$class])) {
            self::$_traitAllRelationships[$class] = [];
            try {
                $ref = new \ReflectionClass(get_class($this));
                foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    $returnType = $method->getReturnType();
                    if ($returnType) {
                        $typeName = $returnType->getName();
                        if (class_exists($typeName) && is_a($typeName, Relation::class, true)) {
                            self::$_traitAllRelationships[$class][] = $method->getName();
                        }
                    }
                }
            } catch (\ReflectionException $e) {
                // for ignore ide warning \ReflectionException if the class does not exist.
            }
        }
        return self::$_traitAllRelationships[$class] ?: [];
    }

    /**
     * @param $rel
     * @return \Illuminate\Database\Eloquent\Relations\HasOneOrMany|null
     */
    public function getRelationship($rel)
    {
        $parts = explode('.', $rel);
        if (count($parts) > 1) {
            return $this->getDeepRelationship($parts);
        }
        $class = get_called_class();
        if (!isset(self::$_traitRelations[$class])) {
            self::$_traitRelations[$class] = [];
        }
        if (!array_key_exists($rel, self::$_traitRelations[$class])) {
            if (method_exists($this, $rel)) {
                self::$_traitRelations[$class][$rel] = $this->{$rel}();
            } else {
                self::$_traitRelations[$class][$rel] = null;
            }
        }
        return self::$_traitRelations[$class][$rel];
    }

    /**
     * @param array $parts
     * @return \Illuminate\Database\Eloquent\Relations\HasOneOrMany|null
     */
    public function getDeepRelationship(array $parts)
    {
        $class = get_called_class();
        /** @var   \Illuminate\Database\Eloquent\Builder $query */
        $query = (new $class())->newQuery();
        /** @var \Illuminate\Database\Eloquent\Relations\HasOneOrMany $relation */
        $relation = null;
        foreach ($parts as $part) {
            if (!$relation) {
                try {
                    $relation = $query->getRelation($part);
                } catch (\Exception $e) {
                    return null;
                }
            } else {
                try { // detect deep relation
                    $relation = $relation->newModelInstance()->newQuery()->getRelation($part);
                } catch (\Exception $e) {
                    return null;
                }
            }
        }
        return $relation;
    }

    /**
     * @param string $rel
     *
     * @return bool
     */
    public function hasRelationship(string $rel)
    {
        return $this->getRelationship($rel) != null;
    }

}