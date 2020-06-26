<?php
/**
 * DONG2020 router
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright v.k
 */

namespace DONG2020;

use Illuminate\Support\Str;
use DONG2020\Controller\ApiDocController;
use DONG2020\Controller\RouteMapController;

/**
 * DONG2020 router
 */
class Router extends \Laravel\Lumen\Routing\Router
{
    /**
     * Router constructor.
     * @param \Laravel\Lumen\Application $app
     */
    public function __construct(\Laravel\Lumen\Application $app)
    {
        parent::__construct($app);
        $this->get('/', sprintf('%s@get', RouteMapController::class));
        $this->get('/doc.html', sprintf('%s@get', ApiDocController::class));
    }

    /**
     * @param array $rules
     */
    protected function sortRoutes(array &$rules)
    {
        uksort($rules, function ($a, $b) {
            return strlen($a) > strlen($b) ? -1 : 1;
        });
    }

    /**
     * @param array $rules
     */
    public function addRules(array $rules)
    {
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
        $this->sortRoutes($this->routes);
    }

    /**
     * @param array $rule
     */
    public function addRule(array $rule)
    {
        $groupOptions = isset($rule['options']) ? $rule['options'] : [];
        $rules = isset($rule['rules']) ? $rule['rules'] : [];
        $children = isset($rule['children']) ? $rule['children'] : [];
        if (empty($rules) && empty($children)) {
            return;
        }
        $this->group($groupOptions, function () use ($rules, $children, $groupOptions) {
            foreach ($rules as $path => $opts) {
                list($controllerName, $methods, $type) = $opts;
                if (is_array($methods)) {
                    $methods = implode("|", array_map(function ($m) {
                        return $m;
                    }, $methods));
                }
                $controllerClass = sprintf('%sController', $controllerName);
                $path = Str::replaceFirst('/', '', $path);
                call_user_func_array(sprintf('\DONG2020\%s', $type), [
                    $this,
                    $path,
                    $controllerClass,
                    $this->getFullPath($groupOptions, $path),
                    array_unique(explode('|', $methods))
                ]);
            }
            foreach ($children as $rule) {
                $this->addRule($rule);
            }
        });
    }

    /**
     * @var array
     */
    private $_classMap = [];

    /**
     * @param string $controllerClass
     * @param string $fullPath
     * @return string
     */
    public function getFullControllerClass(string $controllerClass, $fullPath): string
    {
        $key = $controllerClass . $fullPath;
        if (!isset($this->_classMap[$key])) {
            $fullControllerClass = null;
            if (!class_exists($controllerClass) && $this->hasGroupStack()) {
                $namespace = $this->getCurrentNamespace();
                if ($namespace) {
                    $fullControllerClass = sprintf('%s\\%s', $namespace, $controllerClass);
                }
            } else {
                $fullControllerClass = $controllerClass;
            }
            $this->_classMap[$key] = $fullControllerClass;
        }
        return $this->_classMap[$key];
    }

    /**
     * @param string     $fullControllerClass
     * @param array|null $strictMethods
     * @return array
     */
    public function getControllerAllowedMethods(string $fullControllerClass, ?array $strictMethods = null): array
    {
        if ($strictMethods) {
            return array_merge($strictMethods, ['OPTIONS']);
        }
        $methods = property_exists($fullControllerClass, 'allowedMethods') ?
            $fullControllerClass::$allowedMethods : [];
        return array_merge($methods, ['OPTIONS']);
    }

    /**
     * @param array  $groupOptions
     * @param string $path
     * @return string
     */
    protected function getFullPath($groupOptions, string $path): string
    {
        $path = $path != '' ? sprintf('/%s', $path) : $path;
        $path = isset($groupOptions['prefix']) ? $groupOptions['prefix'] . $path : $path;
        return sprintf('/%s', $path);
    }

    /**
     * @return string|null
     */
    protected function getCurrentNamespace(): ?string
    {
        if ($this->hasGroupStack()) {
            $group = last($this->groupStack);
            return isset($group['namespace']) ? $group['namespace'] : null;
        }
        return null;
    }

}
