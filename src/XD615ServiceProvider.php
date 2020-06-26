<?php
/**
 * service provider
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use DONG2020\Console\Kernel;
use DONG2020\Contracts\IResourcePresenter;
use DONG2020\Middleware\Cors;
use DONG2020\Middleware\JwtAuth;
use DONG2020\Middleware\Transaction;
use DONG2020\Service\ExceptionHandler;
use DONG2020\Service\Jwt;


/**
 * service provider
 */
class DONG2020ServiceProvider extends ServiceProvider
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

        $this->app->singleton('DONG2020.jwt', function () {
            return new Jwt(\env('APP_KEY'));
        });
        $this->app->singleton(Jwt::class, function () {
            return $this->app->make('DONG2020.jwt');
        });

        $this->app->routeMiddleware([
            'cors'        => Cors::class,
            'jwt-auth'    => JwtAuth::class,
            'transaction' => Transaction::class
        ]);

    }

}