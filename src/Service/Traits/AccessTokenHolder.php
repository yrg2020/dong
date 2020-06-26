<?php
/**
 * token holder trait
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright v.k
 */

namespace DONG2020\Service\Traits;

use Illuminate\Support\Fluent;

/**
 * Class AccessToken
 * @property string $token
 * @property int $expiresIn
 * @property int $createdTime
 */
class AccessToken extends Fluent
{
    public function __construct(string $token, int $expiresIn, int $createdTime)
    {
        parent::__construct([
            'token' => $token,
            'expiresIn' => $expiresIn,
            'createdTime' => $createdTime
        ]);
    }

    /**
     * @param int $time
     *
     * @return bool
     */
    public function isExpired(?int $time = null)
    {
        $time = $time ?: time();
        return $time >= ($this->createdTime + $this->expiresIn);
    }
}


/**
 * token holder trait
 */
trait AccessTokenHolder
{
    /**
     * @var IAccessTokenManager
     */
    private $_manager;

    /**
     * @var AccessToken
     */
    private $_token;

    /**
     * @param \DONG2020\Service\Traits\IAccessTokenManager $manager
     */
    protected function setAccessTokenManager(IAccessTokenManager $manager)
    {
        $this->_manager = $manager;
    }

    /**
     * get access token
     *
     * @return string|null
     * @throws \Exception
     */
    public function getAccessToken(): ?string
    {
        if (!$this->_manager) {
            throw new \Exception("IAccessTokenManager not set");
        }

        if (!$this->_token) {
            $this->_token = $this->_manager->resolveToken();
        }

        if (!$this->_token || $this->_token->isExpired()) {
            $this->_token = $this->_manager->requestToken();
            $this->_manager->saveToken($this->_token);
        }

        return $this->_token ? $this->_token->token : null;
    }

    /**
     * @return string
     */
    public function refreshAccessToken(): ?string
    {
        $this->_token = $this->_manager->requestToken();
        $this->_manager->saveToken($this->_token);
        return $this->_token->token;
    }
}
