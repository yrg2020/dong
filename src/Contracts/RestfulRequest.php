<?php
/**
 * restful request
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ParameterBag;


/**
 * restful request
 *
 * @property mixed              $resourceId
 * @property ParameterBag       $params
 * @property SortOption[]       $sorts
 * @property string[]           $withRels
 * @property ParameterBag| null $relsQuery
 * @property int                $limit
 * @property int                $page
 * @property  bool              $all
 */
class RestfulRequest extends Fluent
{

    /**
     * RestfulRequest constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $resourceId = '';
        list($_, $__, $routeParams) = $request->route();
        $routeParams = $routeParams ?: [];
        $key = $this->getKey();
        if (array_key_exists($this->getKey(), $routeParams)) {
            $resourceId = $routeParams[$key];
            unset($routeParams[$key]);
        }
        $pageSize = config('DONG2020.pageSize', 20);
        parent::__construct([
            'resourceId' => $resourceId,
            'params'     => new ParameterBag(
                $this->decodeUriParams($this->getJsonParams($request, 'query', []))
            ),
            'withRels'   => $this->getJsonParams($request, 'withRels', [], true),
            'relsQuery'  => new ParameterBag($this->getJsonParams($request, 'relsQuery', [], true)),
            'sorts'      => $this->parseSorts($this->getJsonParams($request, 'sorts', [])),
            'limit'      => $request->query->getInt('limit', $pageSize),
            'page'       => $request->query->getInt('page', 1),
            'all'        => $request->query->getBoolean('all', false)
        ]);

        if ($this->limit <= 0) {
            $this->limit = $pageSize;
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param                          $key
     * @param null                     $defaultValue
     * @param bool                     $camelCase
     * @return null
     */
    protected function getJsonParams(Request $request, $key, $defaultValue = null, $camelCase = false)
    {
        $content = $request->query->get($key, '');
        $params = null;
        try {
            $value = json_decode($content, true);
            $params = $value ?: $defaultValue;
        } catch (\Exception $o_o) {
            $params = $defaultValue;
        }
        if ($camelCase && $params !== null) {
            $camelCaseParams = [];
            foreach ($params as $key => $value) {
                $camelCaseParams[Str::camel($key)] = $value;
            }
            return $camelCaseParams;
        }
        return $params;
    }

    /**
     * decode uri params
     *
     * @param array $params
     *
     * @return array
     */
    protected function decodeUriParams(array $params)
    {
        return $params;
    }

    /**
     * @return string
     */
    protected function getKey()
    {
        return config('DONG2020.idKey', 'id');
    }


    /**
     * @param $sorts
     *
     * @return SortOption[]
     */
    protected function parseSorts($sorts)
    {
        $result = [];
        foreach ($sorts as $key => $value) {
            $result[] = new SortOption($key, $value);
        }
        return $result;
    }

}