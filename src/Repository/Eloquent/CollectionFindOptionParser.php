<?php
/**
 * parse collection find option to query
 *
 * PHP Version 7.2
 *
 * @author    v.k
 *
 */

namespace DONG2020\Repository\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use DONG2020\Contracts\CollectionFindOption;
use DONG2020\Contracts\ICollectionFindOptionParser;

/**
 * parse collection find option to query
 *
 * cases:
 * a=3&b=4 to a = 3 and b = 4
 * a=%3% to a like '%3%'
 * a=!3&b=!4 to a != 3 and b != 4
 * a=>3 to a > 3
 * a=<3 to a < 3
 * a=[1,2,3] to a in 1,2,3
 * a=>=1<=2 to a >= 1 and a <= 2
 */
class CollectionFindOptionParser implements ICollectionFindOptionParser
{
    /**
     * @var array
     */
    protected $tableColMap = [];

    /**
     * @param string $table
     * @param string $col
     * @return bool
     */
    protected function hasColumn($table, $col)
    {
        if (!isset($this->tableColMap[$table])) {
            $this->tableColMap[$table] = Schema::getColumnListing($table);
        }
        return in_array(
            strtolower($col), array_map('strtolower', $this->tableColMap[$table])
        );
    }

    /**
     * @param CollectionFindOption $findOption
     * @param mixed                $context
     */
    public function parse(CollectionFindOption $findOption, $context)
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        list($model, $query) = $context;
        foreach ($findOption->query as $key => $value) {
            if ($this->hasColumn($model->getTable(), $key)) {
                $this->parseCondition($query, $key, $value);
            }
        }
        if (!empty($findOption->sorts)) {
            foreach ($findOption->sorts as $sort) {
                if ($this->hasColumn($model->getTable(), $sort->sort)) {
                    $query->orderBy($sort->sort, $sort->type);
                }
            }
        } else {
            if ($model instanceof Model) {
                $query->orderBy($model->getKeyName(), 'desc');
            }
        }
    }

    /**
     * @param $delimiter
     * @param $value
     *
     * @return array
     */
    protected function getParts($delimiter, $value)
    {
        $parts = array_filter(explode($delimiter, $value), function ($s) {
            return trim($s) != '';
        });
        return array_unique($parts);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $key
     * @param mixed                                 $value
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function parseCondition($query, $key, $value)
    {
        if ($value === '' || is_array($value) || is_object($value)) {
            return $query;
        }

        if (Str::startsWith($value, '!')) {
            if ($value == '!') {
                return $query;
            }
            $query->where($key, '!=', str_replace('!', '', $value));
            return $query;
        }


        if (Str::startsWith($value, '%')) {
            if ($value == '%%') {
                return $query;
            }
            return $query->where($key, 'like', $value);
        }

        if (Str::startsWith($value, '>') || Str::startsWith($value, '<')) {
            if (in_array($value, ['>', '<', '>=', '<='])) {
                return $query;
            }
            if (preg_match_all('/>=|<=|>|</', $value, $operators)) {
                if (count($operators) > 0) {
                    $partValues = preg_split('/>=|<=|>|</', $value);
                    foreach ($operators[0] as $index => $operator) {
                        if (isset($partValues[$index + 1])) {
                            $query->where($key, $operator, $partValues[$index + 1]);
                        }
                    }
                }
                return $query;
            }
        }

        if (Str::startsWith($value, '[') && Str::endsWith($value, ']')) {
            $arr = json_decode($value, true);
            return $query->whereIn($key, $arr);
        }


        return $query->where($key, '=', $value);
    }

}