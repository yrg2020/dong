<?php
/**
 * resource meta pagination
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Contracts;

use Illuminate\Support\Fluent;


/**
 * resource meta pagination
 *
 * @property int $limit
 * @property int $currentPage
 * @property int $totalPage
 * @property int $hasNextPage
 */
class CollectionPaginationMeta extends Fluent
{
    /**
     * ResourceMetaPagination constructor.
     * @param int  $limit
     * @param int  $currentPage
     * @param int  $totalPage
     * @param bool $hasNextPage
     */
    public function __construct(int $limit, int $currentPage, int $totalPage, bool $hasNextPage)
    {
        parent::__construct([
            'limit'       => $limit,
            'currentPage' => $currentPage,
            'totalPage'   => $totalPage,
            'hasNextPage' => $hasNextPage
        ]);
    }

}
