<?php
/**
 * sort option
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;

use Illuminate\Support\Fluent;


/**
 * Class SortOption
 * @property string $sort
 * @property string $type ASC | DESC
 */
class SortOption extends Fluent
{
    const SortDesc = 'DESC';
    const SortAsc = 'ASC';

    const AllSortTypes = [self::SortDesc, self::SortAsc];

    /**
     * SortOption constructor.
     * @param string $sort
     * @param string $type
     */
    public function __construct(string $sort, string $type)
    {
        $type = in_array(strtoupper($type), self::AllSortTypes) ? $type : self::SortAsc;
        parent::__construct([
            'sort' => $sort,
            'type' => $type
        ]);
    }

}