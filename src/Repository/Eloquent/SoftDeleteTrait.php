<?php
/**
 * soft delete trait
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace DONG2020\Repository\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * soft delete trait
 */
trait SoftDeleteTrait
{
    use SoftDeletes;

    protected abstract function getUniqueIndices(): array;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeleteTrait()
    {
        static::registerModelEvent('creating', function (Model $model) {
            return self::onSoftDeleteCreating($model);
        });
        static::registerModelEvent('updating', function (Model $model) {
            return self::onSoftDeleteUpdating($model);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|SoftDeletes|SoftDeleteTrait $model
     *
     * @return bool
     */
    public static final function onSoftDeleteCreating(Model $model)
    {
        $uniqueIndices = $model->getUniqueIndices();
        if (empty($uniqueIndices)) {
            return true;
        }

        $conditions = [];
        foreach ($uniqueIndices as $index) {
            $index = is_array($index) ? $index : [$index];
            foreach ($index as $key) {
                $conditions[$key] = $model->getAttribute($key);
            }
        }

        /** @var Model|SoftDeleteTrait $existedModel */
        $existedModel = $model->newQuery()->withTrashed()->where($conditions)->first();

        if ($existedModel && $existedModel->trashed()) {
            foreach ($model->getAttributes() as $key => $_) {
                $existedModel->setAttribute($key, $model->getAttribute($key));
            }
            $result = $existedModel->restore();
            if ($result) {
                foreach ($existedModel->getAttributes() as $key => $_) {
                    $model->setAttribute($key, $existedModel->getAttribute($key));
                }
                return false;
            }
        }
        return true;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|SoftDeleteTrait $model
     * @return bool
     */
    public static final function onSoftDeleteUpdating(Model $model)
    {
        $uniqueIndices = $model->getUniqueIndices();
        if (empty($uniqueIndices)) {
            return true;
        }

        $conditions = [];
        foreach ($uniqueIndices as $index) {
            $index = is_array($index) ? $index : [$index];
            foreach ($index as $key) {
                $conditions[$key] = $model->getAttribute($key);
            }
        }
        if (!empty($conditions) && $model->isDirty($uniqueIndices)) {
            /** @var Model $otherModel */
            $otherModel = $model->newQuery()->withTrashed()->where($conditions)->first();
            if ($otherModel) {
                $otherModel->forceDelete();
            }
        }
        return true;
    }


}
