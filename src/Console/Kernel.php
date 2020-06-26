<?php
/**
 * CXD2020 console kernel
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace CXD2020\Console;

use Laravel\Lumen\Console\Kernel as ConsoleKernel;


/**
 * CXD2020 console kernel
 */
class Kernel extends ConsoleKernel
{
    /**
     * Kernel constructor.
     * @param \Laravel\Lumen\Application $app
     */
    public function __construct(\Laravel\Lumen\Application $app)
    {
        if ($app->environment() == 'local' || $app->environment() == 'testing') {
            $this->commands[] = \Mlntn\Console\Commands\Serve::class;     // php artisan serve
            $this->commands[] = \CXD2020\Console\Commands\RouteListCommand::class; // php artisan route:list
            $this->commands[] = \CXD2020\Console\Commands\OpenAPICommand::class; // php artisan doc
        }
        parent::__construct($app);
    }


}