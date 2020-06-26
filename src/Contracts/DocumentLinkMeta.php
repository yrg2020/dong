<?php
/**
 * resource meta link
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;

use Illuminate\Support\Fluent;


/**
 * resource meta link
 *
 * @property string $rel   collection | document
 * @property string $href
 */
class DocumentLinkMeta extends Fluent
{

    const REL_COLLECTION = 'collection';
    const REL_DOCUMENT = 'document';

    /**
     * ResourceMetaLink constructor.
     * @param string $rel
     * @param string $href
     */
    public function __construct(string $rel, string $href)
    {
        parent::__construct(['rel' => $rel, 'href' => $href]);
    }

}
