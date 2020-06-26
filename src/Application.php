<?php
/**
 * CXD2020 application
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright v.k
 */

namespace CXD2020;

/**
 * CXD2020 application
 */
class Application extends \Laravel\Lumen\Application
{
    /**
     * bootstrap router
     */
    public function bootstrapRouter()
    {
        $this->router = new Router($this);
    }

}
