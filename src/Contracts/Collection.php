<?php
/**
 * collection
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;


/**
 * collection
 *
 * @property mixed[]        $collection raw documents in collection
 * @property CollectionMeta $meta
 */
class Collection extends Resource
{
    /**
     * Collection constructor.
     * @param mixed[]        $documents
     * @param CollectionMeta $meta
     */
    public function __construct(array $documents, ?CollectionMeta $meta)
    {
        parent::__construct([
            'collection' => $documents,
            'meta'       => $meta
        ]);
    }


}