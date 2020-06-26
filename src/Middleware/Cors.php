<?php
/**
 * cors middleware
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Middleware;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


/**
 * cors middleware
 */
class Cors
{
    const CorsHeaders = [
        'Access-Control-Allow-Origin'      => '*',
        'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With',
        'Access-Control-Allow-Methods'     => 'GET, HEAD, HEAD, POST, PATCH, PUT, DELETE',
        'Access-Control-Expose-Headers'    => 'ETag, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, X-Total',
        'Access-Control-Max-Age'           => '86400',
        'Access-Control-Allow-Credentials' => 'true'
    ];

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {

        if ($request->getMethod() == 'OPTIONS') {
            return response('', Response::HTTP_NO_CONTENT, static::CorsHeaders);
        }

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        if (!$response->headers) {
            $response->headers = new ResponseHeaderBag();
        }

        if ($response->getStatusCode() >= 400) {
            $response->headers->set(
                'Access-Control-Expose-Headers',
                static::CorsHeaders['Access-Control-Expose-Headers']
            );
        }
        $response->headers->set('Access-Control-Allow-Origin', $request->header('Origin', '*'));
        return $response;
    }

}