<?php
/**
 * collection find option
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Contracts;

use Illuminate\Support\Fluent;
use Symfony\Component\HttpFoundation\ParameterBag;


/**
 * collection find option
 *
 * @property ParameterBag          $query
 * @property PaginationOption|null $page   optional
 * @property SortOption[]          $sorts  optional
 * @property bool                  $all    optional
 */
class CollectionFindOption extends Fluent
{
    /**
     * CollectionFindOption constructor.
     * @param ParameterBag          $query
     * @param PaginationOption|null $page
     * @param SortOption[]          $sorts
     * @param bool                  $all
     *
     */
    public function __construct(ParameterBag $query, ?PaginationOption $page = null, array $sorts = [], $all = false)
    {
        parent::__construct([
            'query' => $query,
            'page'  => $page,
            'sorts' => $sorts,
            'all'   => $all
        ]);
    }

}