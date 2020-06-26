<?php
/**
 * jwt service
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Service;

/**
 * jwt service
 */
class Jwt
{
    /**
     * @var string
     */
    protected $key;

    /**
     * Jwt constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * @param array  $tokenPayload
     * @param string $alg
     *
     * @return string
     */
    public function encode(array $tokenPayload, $alg = 'HS256')
    {
        return \Firebase\JWT\JWT::encode($tokenPayload, $this->key, $alg);
    }

    /**
     * @param string $token
     * @param string $alg
     *
     * @return object|null
     */
    public function decode(string $token, $alg = 'HS256')
    {
        try {
            return \Firebase\JWT\JWT::decode($token, $this->key, [$alg]);
        } catch (\Exception $e) {
            return null;
        }
    }

}