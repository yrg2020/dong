<?php
/**
 * route map
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace CXD2020\Controller;

use Illuminate\Http\Request;
use CXD2020\Contracts\RestfulErrorMessage;
use CXD2020\Contracts\RestfulException;


/**
 * route map
 */
class RouteMapController extends ActionController
{
    /**
     * @var \CXD2020\Router
     */
    protected $router;

    /**
     * @return array
     */
    public static function doc(): array
    {
        return [
            'desc' => '路由表'
        ];
    }

    /**
     * RouteMapController constructor.
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->router = app()->router;
    }

    /**
     * @return \CXD2020\Contracts\Action
     * @throws \CXD2020\Contracts\RestfulException
     */
    public function get()
    {
        if (env('APP_ENV') === 'prod') {
            throw new RestfulException(RestfulErrorMessage::NotFound);
        }
        $urls = [];
        foreach ($this->router->getRoutes() as $route) {
            $uri = $route['uri'];
            if (!isset($urls[$uri])) {
                $urls[$uri] = [
                    'uri'     => $uri,
                    'methods' => [$route['method']]
                ];
            } else {
                $urls[$uri]['methods'][] = $route['method'];
            }
        }
        return $this->toResponse(array_values($urls));
    }

}
