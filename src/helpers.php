<?php
/**
 *
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020;


use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ParameterBag;
use DONG2020\Contracts\IResourceRepository;
use DONG2020\Contracts\RestfulErrorMessage;
use DONG2020\Contracts\RestfulException;
use DONG2020\Controller\RestfulController;
use DONG2020\Controller\RestfulRelController;

/**
 * @param $path
 * @return string
 */
function stdPath($path)
{
    return str_replace(['//', '_'], ['/', '-'], Str::snake($path));
}

/**
 * @param Router  $router
 * @param string  $path
 * @param string  $controllerClass
 * @param  string $fullPath
 * @param array   $methods
 */
function api(Router $router, string $path, $controllerClass, string $fullPath, $methods = null)
{
    $fullControllerClass = $router->getFullControllerClass($controllerClass, $fullPath);
    $isRestController = is_a($fullControllerClass, RestfulController::class, true);
    if (!$isRestController) {
        throw new \InvalidArgumentException(sprintf("%s should be RestfulController", $fullControllerClass));
    }
    $action = sprintf('%s@%s', $controllerClass, 'handle');
    $fullControllerClass::setName($fullPath);

    $key = config('DONG2020.idKey', 'id');
    $documentPath = $isRestController ? sprintf('%s/{%s}', $path, $key) : null;

    $methods = $router->getControllerAllowedMethods($fullControllerClass, $methods);
    foreach ($methods as $method) {
        $router->{$method}($path, $action);
        if ($documentPath) {
            $router->{$method}($documentPath, $action);
            /** @var IResourceRepository $repo */
            $repo = app($fullControllerClass)->getRepository();
            foreach ($repo->getRelationships() as $rel) {
                $hasMany = Str::endsWith($rel, 's');
                $relPath = sprintf('%s/%s', $documentPath, $rel);
                $relAction = function () use ($fullControllerClass, $rel, $hasMany) {
                    /** @var IResourceRepository $repo */
                    $repo = app($fullControllerClass)->getRepository();
                    $params = new ParameterBag(func_get_args());
                    $parentId = $params->get(0);
                    $resourceId = $params->get(1);
                    $document = $repo->find($parentId);
                    if (!$document) {
                        throw new RestfulException(RestfulErrorMessage::NotFound);
                    }
                    if ($hasMany) {
                        return (new RestfulRelController(
                            app()->get(Request::class), $repo, $rel, $document,
                            $resourceId)
                        )->handle();
                    }
                    $childDocument = $document->{$rel}();
                    if (!$childDocument) {
                        throw new RestfulException(RestfulErrorMessage::NotFound);
                    }
                    return (new RestfulRelController(
                        app()->get(Request::class), $repo, $rel, $document,
                        $childDocument->document->getKey())
                    )->handle();
                };
                $router->{$method}(stdPath($relPath), $relAction);
                if ($hasMany) {
                    $relDocumentPath = sprintf('%s/%s/{%s-%s}', $documentPath, $rel, $rel, $key);
                    $router->{$method}(stdPath($relDocumentPath), $relAction);
                }
            }
        }
    }

}

/**
 * @param Router $router
 * @param string $path
 * @param string $controllerClass
 * @param string $fullPath
 * @param array  $methods
 */
function action(Router $router, string $path, $controllerClass, string $fullPath = '', $methods = null)
{
    $action = sprintf('%s@%s', $controllerClass, 'handle');
    $methods = $router->getControllerAllowedMethods($controllerClass, $methods);
    foreach ($methods as $method) {
        $router->{$method}($path, $action);
    }
}