<?php
/**
 * action controller
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Controller;

use Illuminate\Http\Response;
use DONG2020\Contracts\Action;


/**
 * action controller
 */
class ActionController extends MethController
{

    /**
     * @param       $data
     * @param null  $meta
     * @param int   $status
     * @param array $headers
     *
     * @return Response
     */
    protected function toResponse($data, $meta = null, $status = 200, $headers = []): Response
    {
        return response(new Action($data, $meta), $status, $headers);
    }

}