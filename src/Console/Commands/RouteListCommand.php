<?php
/**
 * router list command
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace CXD2020\Console\Commands;

use Appzcoder\LumenRoutesList\RoutesCommand;

/**
 * router list command
 */
class RouteListCommand extends RoutesCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'route:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '显示路由表';

    /**
     * @return \CXD2020\Application
     */
    protected function getRealApplication()
    {
        global $app;
        if (!$app) {
            return app();
        }
        return $app;
    }

    /**
     * @param array $action
     * @return mixed|null|string
     */
    protected function getController(array $action)
    {
        $controller = parent::getController($action);
        if ($controller == 'None') {
            return null;
        }
        return $controller;
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $routes = $this->getRealApplication()->router->getRoutes();

        $rows = [];
        foreach ($routes as $route) {
            $key = $route['uri'];
            $row = isset($rows[$key]) ? $rows[$key] : [
                'verb'       => [],
                'path'       => $route['uri'],
                'controller' => $this->getController($route['action']) ?: 'Closure',
                'middleware' => $this->getMiddleware($route['action'])
            ];
            $row['verb'][] = $route['method'];
            $rows[$key] = $row;
        }
        foreach ($rows as &$row) {
            $row['verb'] = implode('|', $row['verb']);
        }

        $headers = array('Verb', 'Path', 'Controller', 'Middleware');
        $this->table($headers, array_values($rows));
    }

}
