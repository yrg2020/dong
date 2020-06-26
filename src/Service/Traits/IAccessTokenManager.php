<?php
/**
 * access token manager
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright v.k
 */

namespace CXD2020\Service\Traits;

/**
 * Interface AccessTokenManager
 */
interface IAccessTokenManager
{
    /**
     * send token request
     * @return AccessToken|null
     */
    public function requestToken(): AccessToken;

    /**
     * get token from storage
     *
     * @return AccessToken|null
     */
    public function resolveToken(): ?AccessToken;

    /**
     * save token
     * @param AccessToken $token
     */
    public function saveToken(AccessToken $token): void;
}