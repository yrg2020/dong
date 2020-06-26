<?php
/**
 * db transaction middleware
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Middleware;

use Illuminate\Support\Facades\DB;


/**
 * db transaction middleware
 */
class Transaction
{

    const IgnoreTransactionMethods = ['OPTIONS', 'GET', 'HEAD'];

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {
        $method = strtoupper($request->method());
        if (in_array($method, self::IgnoreTransactionMethods)) {
            return $next($request);
        }

        DB::beginTransaction();
        try {
            $response = $next($request);
            if (isset($response->exception) && $response->exception != null) {
                throw $response->exception;
            }
            DB::commit();
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

}