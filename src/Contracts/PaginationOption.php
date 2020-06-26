<?php
/**
 * PaginationOption
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;

use Illuminate\Support\Fluent;


/**
 * Class PaginationOption
 *
 * @property int $page
 * @property int $limit
 */
class PaginationOption extends Fluent
{
    /**
     * PaginationOption constructor.
     * @param int $page
     * @param int $limit
     */
    public function __construct(int $page, int $limit)
    {
        parent::__construct([
            'page'  => $page,
            'limit' => $limit
        ]);
    }

}