<?php
/**
 * service provider
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use CXD2020\Console\Kernel;
use CXD2020\Contracts\IResourcePresenter;
use CXD2020\Middleware\Cors;
use CXD2020\Middleware\JwtAuth;
use CXD2020\Middleware\Transaction;
use CXD2020\Service\ExceptionHandler;
use CXD2020\Service\Jwt;


/**
 * service provider
 */
class CXD2020ServiceProvider extends ServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application|\Laravel\Lumen\Application
     */
    protected $app;

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            function () {
                return new Kernel($this->app);
            }
        );
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            function () {
                return new ExceptionHandler(
                    \env('APP_ENV') != 'prod',
                    $this->app->make(Request::class)
                );
            }
        );

        $this->app->singleton('CXD2020.jwt', function () {
            return new Jwt(\env('APP_KEY'));
        });
        $this->app->singleton(Jwt::class, function () {
            return $this->app->make('CXD2020.jwt');
        });

        $this->app->routeMiddleware([
            'cors'        => Cors::class,
            'jwt-auth'    => JwtAuth::class,
            'transaction' => Transaction::class
        ]);

    }

}