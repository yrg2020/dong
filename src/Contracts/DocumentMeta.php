<?php
/**
 * document meta info
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Contracts;

use Illuminate\Support\Fluent;


/**
 * document meta info
 *
 * @property mixed              $id
 * @property string             $etag
 * @property string             $href
 * @property DocumentLinkMeta[] $links
 */
class DocumentMeta extends Fluent
{
    /**
     * ResourceMeta constructor.
     * @param mixed              $id
     * @param string             $etag
     * @param string             $href
     * @param DocumentLinkMeta[] $links
     */
    public function __construct($id, string $etag, string $href, array $links)
    {
        parent::__construct([
            'id'    => $id,
            'etag'  => $etag,
            'href'  => $href,
            'links' => $links
        ]);
    }

}