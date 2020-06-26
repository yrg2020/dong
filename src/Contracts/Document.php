<?php
/**
 * document
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Contracts;

use Illuminate\Support\Fluent;


/**
 * document
 *
 * @property mixed        $document
 * @property DocumentMeta $meta
 */
final class Document extends Fluent
{
    /**
     * Document constructor.
     *
     * @param mixed        $document raw document
     * @param DocumentMeta $meta
     */
    public function __construct($document, DocumentMeta $meta)
    {
        parent::__construct([
            'document' => $document,
            'meta'     => $meta
        ]);
    }

}