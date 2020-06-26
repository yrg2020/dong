<?php
/**
 * jwt auth middleware
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Middleware;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use CXD2020\Service\Jwt;


/**
 * jwt auth middleware
 */
class JwtAuth
{

    /**
     * The header name.
     *
     * @var string
     */
    protected $header = 'authorization';

    /**
     * The header prefix.
     *
     * @var string
     */
    protected $prefix = 'bearer';

    /**
     * The query param name
     *
     * @var string
     */
    protected $queryTokenName = 'token';

    /**
     * @var Jwt
     */
    protected $jwt;

    /**
     * JwtAuth constructor.
     * @param Jwt $jwt
     */
    public function __construct(Jwt $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {
        $token = $this->parseToken($request);

        if ($token === null || !($user = $this->getUser($token))) {
            throw new UnauthorizedHttpException('jwt-auth');
        }
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        return $next($request);
    }

    /**
     * @param string $token
     * @return mixed|null
     */
    protected function getUser($token)
    {
        return $this->jwt->decode($token);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return string|null
     */
    protected function parseToken($request)
    {
        $token = null;
        $header = $request->headers->get($this->header);
        if ($header && preg_match('/' . $this->prefix . '\s*(\S+)\b/i', $header, $matches)) {
            $token = $matches[1];
        }
        if (!$token) {
            $token = $request->query->get($this->queryTokenName);
        }
        return $token;
    }
}