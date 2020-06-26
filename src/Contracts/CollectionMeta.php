<?php
/**
 * collection meta info
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Contracts;

use Illuminate\Support\Fluent;


/**
 * collection meta info
 *
 * @property string                   $href
 * @property int                      $total
 * @property CollectionPaginationMeta $pagination
 * @property array                    $links
 * @property array                    $rels
 */
class CollectionMeta extends Fluent
{
    /**
     * CollectionMeta constructor.
     * @param string                   $href
     * @param int                      $total
     * @param CollectionPaginationMeta $pagination
     * @param array                    $links
     * @param array                    $rels
     */
    public function __construct(
        string $href,
        int $total,
        CollectionPaginationMeta $pagination,
        array $links = [],
        array $rels = []
    ) {
        $href = \DONG2020\stdPath($href);
        foreach ($links as &$link) {
            $link['href'] = \DONG2020\stdPath($link['href']);
        }
        parent::__construct([
            'href'       => $href,
            'total'      => $total,
            'pagination' => $pagination,
            'links'      => $links,
            'rels'       => $rels
        ]);
    }

}