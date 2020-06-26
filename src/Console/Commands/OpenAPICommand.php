<?php
/**
 * 生成open api 3.0 文档
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace CXD2020\Console\Commands;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use CXD2020\Controller\RestfulController;


/**
 * 生成open api 3.0 文档
 */
class OpenAPICommand extends RouteListCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'api:doc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成open api文档';

    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * @var array
     */
    protected $anonymousDefinitions = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $ignorePaths = ['/', '/doc.html'];


    /**
     * @param $method
     * @return bool
     */
    public function isIgnoreMethod($method)
    {
        return in_array($method, ['HEAD', 'OPTIONS']);
    }

    /**
     * handle
     */
    public function handle()
    {
        $apiControllers = [];
        $routes = $this->getRealApplication()->router->getRoutes();
        foreach ($routes as $route) {
            $controller = $this->getController($route['action']);
            if (!$controller || $this->isIgnoreMethod($route['method'])) {
                continue;
            }
            if (!isset($apiControllers[$route['uri']])) {
                $apiControllers[$route['uri']] = [
                    'controller' => $controller,
                    'methods'    => [],
                    'path'       => $route['uri'],
                    'isRestful'  => is_a($controller, RestfulController::class, true)
                ];
            }
            $apiControllers[$route['uri']]['methods'][] = $route['method'];
        }
        [$paths, $definitions, $tags] = $this->genDoc($apiControllers, \config('CXD2020.apiTagGroup'));

        if (count($this->errors) > 0) {
            foreach (array_unique(array_values($this->errors)) as $error) {
                $this->error($error);
            }
            if (app()->environment() != 'testing') {
                exit(-1);
            }
        }
        $this->writeDoc($paths, $definitions, $tags);
    }

    /**
     * @param $paths
     * @param $definitions
     * @param $tags
     */
    protected function writeDoc($paths, $definitions, $tags)
    {
        foreach ($this->anonymousDefinitions as $def) {
            $key = array_keys($def)[0];
            $definitions[$key] = $def[$key];
        }
        $api = json_decode(file_get_contents(dirname(__FILE__) . '/Stubs/openapi.json'), true);
        $api = array_merge($api, \config('CXD2020.apiDoc'));
        $api['tags'] = $tags;
        $api['paths'] = $paths;
        $api['definitions'] = $definitions;
        file_put_contents(\config('CXD2020.apiJsonFile'), json_encode($api));
    }

    /**
     * @param $apiControllers
     * @param $tagGroups
     *
     * @return array
     */
    protected function genDoc($apiControllers, $tagGroups)
    {
        $paths = [];
        $tags = [];

        foreach ($apiControllers as $path => $info) {

            if (in_array($path, $this->ignorePaths)) {
                continue;
            }

            $controller = $info['controller'];
            if ($info['isRestful']) {
                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = app(app($controller)->getRepository()->modelClass);
                $typeName = (new \ReflectionObject($model))->getShortName();
                $this->genDefinition($model, $typeName);
            } else {
                $typeName = '';
            }

            $doc = call_user_func([$controller, 'doc']);
            if (empty($doc)) {
                $this->errors[$controller] = sprintf("我的天啊！%s 居然没写文档", $controller);
                continue;
            }
            if (!isset($doc['desc'])) {
                $this->errors[$controller] = sprintf("夭寿啦！%s 的api文档没写\"desc\"", $controller);
                continue;
            }

            $tag = isset($doc['tag']) ? $doc['tag'] : $doc['desc'];
            $tagPrefix = explode('/', $path)[1];
            if ($tagPrefix) {
                $tag = isset($tagGroups[$tagPrefix]) ? sprintf('[%s]%s', $tagGroups[$tagPrefix], $tag) : $tag;
            }

            $pathInfo = [];
            $pathParam = [];
            $isCollection = true;

            if (Str::contains($path, '{')) {
                preg_match('/\{(\w+)\}/', $path, $pathParam);
                $isCollection = false;
                $pathParam = [
                    'in'          => 'path',
                    'name'        => $pathParam[1],
                    'description' => $pathParam[1],
                    'type'        => 'string',
                    'required'    => true
                ];
                $pathInfo['parameters'] = [$pathParam];
            }

            foreach ($this->sortMethods($info['methods']) as $method) {
                $method = strtoupper($method);
                [$desc, $params, $responses] = $this->parseMethodDesc(
                    $controller, $method, $doc, $info['isRestful'], $typeName
                );

                $controllerType = $info['isRestful'] ? ($isCollection ? 'Collection' : 'Document') : 'Action';
                $pathInfo[strtolower($method)] = [
                    "tags"        => [$tag],
                    "description" => sprintf('%s [%s]', $desc, $controllerType),
                    "consumes"    => ["application/json"],
                    "produces"    => ["application/json"],
                    "responses"   => $responses
                ];
                if (!empty($params)) {
                    $pathInfo[strtolower($method)]['parameters'] = $params;
                }
            }

            $paths[$path] = $pathInfo;
            $tags[$tag] = [
                'name'        => $tag,
                'description' => preg_replace('/\{(\w+)\}/', '', $path)
            ];
        }

        ksort($paths);
        usort($tags, function ($a, $b) {
            $pathA = $a['description'];
            $pathB = $b['description'];
            return strlen(explode('/', $pathA)[1]) < strlen(explode('/', $pathB)[1]) ? -1 : 1;
        });

        return [$paths, $this->definitions, array_values($tags)];
    }

    /**
     * @param array $methods
     * @return mixed
     */
    protected function sortMethods($methods)
    {
        usort($methods, function ($a, $b) {
            return strlen($a) < strlen($b) ? -1 : 1;
        });
        return $methods;
    }

    /**
     * @param $typeName
     * @return string
     */
    protected function mapDefType($typeName)
    {
        $typeName = strtolower($typeName);
        if (Str::contains($typeName, ['int', 'decimal'])) {
            return 'integer';
        }
        if (Str::contains($typeName, ['bool'])) {
            return 'boolean';
        }
        if (in_array($typeName, ['array', 'object'])) {
            return $typeName;
        }
        return 'string';
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $typeName
     * @return array
     */
    protected function genDefinition($model, $typeName)
    {
        if (!isset($this->definitions[$typeName])) {
            $table = $model->getTable();
            if ($model->getConnection()->getDriverName() === 'mysql') {
                $cols = DB::select(sprintf("SHOW FULL COLUMNS FROM `%s`", $table));
            } else {
                $cols = [];
            }
            $props = [];
            foreach ($cols as $col) {
                $props[$col->Field] = [
                    'type'        => $this->mapDefType($col->Type),
                    'description' => $this->checkColComment($table, $col->Field, $col->Comment)
                ];
            }
            $this->definitions[$typeName] = [
                'type'       => 'object',
                'properties' => $props
            ];
        }
        return $this->definitions[$typeName];
    }

    /**
     * @param $table
     * @param $field
     * @param $comment
     * @return mixed|null
     */
    protected function checkColComment($table, $field, $comment)
    {
        $fieldWordsMap = [
            'id'         => 'ID',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'deleted_at' => '删除时间'
        ];
        if (!isset($fieldWordsMap[$field]) && empty($comment)) {
            $this->errors[$table . $field] = sprintf('要死要死，表`%s`的字段`%s`没写注释！', $table, $field);
            return null;
        }
        return isset($fieldWordsMap[$field]) ? $fieldWordsMap[$field] : $comment;
    }

    /**
     * @param $controller
     * @param $method
     * @param $doc
     * @param $isRestful
     * @param $typeName
     * @return array
     */
    protected function parseMethodDesc($controller, $method, $doc, $isRestful, $typeName = '')
    {
        $methodWordsMap = ['GET' => '获取', 'POST' => '创建', 'PUT' => '替换', 'PATCH' => '更新', 'DELETE' => '删除'];

        $desc = $isRestful ? sprintf('%s%s', $methodWordsMap[$method], $doc['desc']) : $doc['desc'];
        $stage = in_array($method, ['POST', 'PUT', 'PATCH']) ? 'body' : 'query';
        $params = isset($doc['parameters'][$stage]) ?
            $this->parseParameters($controller, $stage, $doc['parameters'][$stage], $method) : [];

        if (isset($doc['response'])) {
            $responses = [];
            foreach ($doc['response'] as $code => $response) {
                if (!isset($response['desc'])) {
                    $response['desc'] = '';
                }
                $responses[$code] = ['description' => $response['desc']];
                if (isset($response['items'])) {
                    $rs = $this->createResponseSchema($controller, $response['items']);
                    if ($rs) {
                        $responses[$code]['schema'] = ['$ref' => $rs];
                    }
                }
            }
        } else {
            $responseDoc = ['description' => 'ok'];
            if ($isRestful) {
                $responseDoc['schema'] = ['$ref' => sprintf('#/definitions/%s', $typeName)];
            }
            $responses = [
                200 => $responseDoc,
                404 => ['description' => 'not found']
            ];
        }
        return [$desc, $params, $responses];
    }

    /**
     * @param $controller
     * @param $items
     * @return null|string
     */
    protected function createResponseSchema($controller, $items)
    {
        [$type, $itemType] = $items;
        if (!in_array($type, ['array', 'object'])) {
            $this->errors[] = sprintf(
                '出错啦！%s 的 response 类型 %s 不在["array", "object"]这个范围内!',
                $controller, $type
            );
        }
        if (is_a($itemType, Model::class, true)) {
            $model = app($itemType);
            $typeName = (new \ReflectionObject($model))->getShortName();
            $this->genDefinition($model, $typeName);
            return sprintf('#/definitions/%s', $typeName);
        }
        return sprintf('#/definitions/%s',
            $this->createAnonymousDefinition($controller, $itemType)
        );
    }

    /**
     * @param string $controller
     * @param string $stage
     * @param array  $params
     *
     * @param        $method
     * @return array
     */
    protected function parseParameters($controller, $stage, $params, $method)
    {
        $ret = [];
        if ($method != 'GET' && $stage == 'query') {
            return $ret;
        }

        foreach ($params as $paramCode) {
            $info = $this->parseParameter($controller, $paramCode, $stage);
            $paramInfo = [
                'name'        => $info['name'],
                'in'          => $stage,
                'description' => $info['desc'],
                'required'    => isset($info['required']) ? $info['required'] : false,
            ];
            if (isset($info['schema'])) {
                $paramInfo['schema'] = ['$ref' => sprintf('#/definitions/%s', $info['schema'])];
            }
            if (isset($info['type'])) {
                $paramInfo['type'] = $info['type'];
            }
            if (isset($info['items'])) {
                $paramInfo['items'] = $info['items'];
            }
            if (!isset($paramInfo['schema']) && !isset($paramInfo['type'])) {
                $this->errors[] = sprintf(
                    "哎哟！%s 的 %s 参数 %s 没有标注数据类型！", $controller, $stage, $info['name']
                );
            }
            if (!$paramInfo['required']) {
                unset($paramInfo['required']);
            }
            $ret[$info['name']] = $paramInfo;
        }

        if ($stage == 'body' && $method != 'GET') {
            return [
                [
                    'name'        => 'body',
                    'in'          => 'body',
                    'required'    => true,
                    'description' => '提交的数据',
                    'schema'      => [
                        '$ref' => sprintf('#/definitions/%s',
                            $this->createAnonymousDefinitionByProps($controller, $ret)
                        )
                    ]
                ]
            ];
        }
        return array_values($ret);
    }

    /**
     * @param $controller
     * @param $paramCode
     * @param $stage
     * @return array
     */
    protected function parseParameter($controller, $paramCode, $stage = null)
    {

        preg_match('/object\@\{(.+)\}/', $paramCode, $objectDef);
        $paramCode = preg_replace('/object\@\{(.+)\}/', 'object', $paramCode);

        $parts = explode(':', $paramCode);

        $ret = ['name' => $parts[0]];

        $desc = [];

        foreach ($parts as $part) {
            $pair = explode('@', trim($part));
            switch ($pair[0]) {
                case 'string':
                case 'int':
                case 'decimal':
                case 'file':
                case 'array':
                    $ret['type'] = $pair[0];
                    break;
                case 'object':
                    if ($objectDef) {
                        if ($stage == 'query') {
                            $ret['type'] = 'string';
                            foreach ($this->parseObjectStringProps($controller, $objectDef[1]) as $name => $val) {
                                $desc[] = sprintf('%s(%s) %s', $name, $val['type'], $val['description']);
                            }
                        } else {
                            $ret['schema'] = $this->createAnonymousDefinition($controller, $objectDef[1]);
                            $ret['type'] = 'object';
                        }
                    } else {
                        $ret['type'] = 'object';
                    }
                    break;
                case 'desc':
                    $desc[] = $pair[1];
                    break;
                case 'items':
                    $ret['items'] = ['type' => $pair[1]];
                    break;
                case 'required':
                case 'optional':
                    $ret['required'] = $pair[0] == 'required';
                    break;
            }
        }
        if (empty($desc)) {
            $ret['desc'] = '';
            $this->errors[] = sprintf('膨胀了！%s 的 参数 %s 没写文档注释！', $controller, $ret['name']);
        } else {
            $ret['desc'] = implode(' ', $desc);
        }
        if (isset($ret['type'])) {
            $ret['type'] = $this->mapDefType($ret['type']);
        }
        return $ret;
    }

    /**
     * @param $controller
     * @param $objectStr
     * @return array
     */
    protected function parseObjectStringProps($controller, $objectStr)
    {
        $props = [];

        $objectProps = is_array($objectStr) ? $objectStr : explode(',', $objectStr);
        foreach ($objectProps as $str) {
            $param = $this->parseParameter($controller, $str);
            if (!isset($param['type'])) {
                $this->errors[] = sprintf('controller %s 中的注释 %s 没有类型！', $controller, implode("|", $objectProps));
                return [];
            }
            $props[$param['name']] = [
                'type'        => $param['type'],
                'description' => $param['desc']
            ];
            if (isset($param['items'])) {
                $props[$param['name']]['items'] = $param['items'];
            }
        }
        return $props;
    }

    /**
     * @param string $controller
     * @param string $objectStr
     *
     * @return string
     */
    protected function createAnonymousDefinition($controller, $objectStr)
    {
        return $this->createAnonymousDefinitionByProps($controller,
            $this->parseObjectStringProps($controller, $objectStr)
        );
    }

    /**
     * @param       $controller
     * @param array $props
     * @return string
     */
    protected function createAnonymousDefinitionByProps($controller, $props)
    {
        $key = md5($controller . json_encode($props));
        if (isset($this->anonymousDefinitions[$key])) {
            return array_keys($this->anonymousDefinitions[$key])[0];
        }

        $defProps = [];
        foreach ($props as $name => $val) {
            if (!isset($val['type'])) {
                $this->errors[] = sprintf('哎呀，控制器%s的属性%s没有写type', $controller, $name);
                return '';
            }
            $defProps[$name] = [
                'type'        => $val['type'],
                'description' => $val['description']
            ];
            if (!empty($val['items'])) {
                $defProps[$name]['items'] = $val['items'];
            }
        }

        $defName = sprintf('Anonymous_Definition_%s', count($this->anonymousDefinitions) + 1);
        $def = [
            'type'       => 'object',
            'properties' => $defProps
        ];
        $this->anonymousDefinitions[$key] = [$defName => $def];

        return $defName;
    }
}
