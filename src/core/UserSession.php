<?php

namespace wing\core;

use wing\exception\BusinessException;

/**
 * UserSession class、
 *
 * @author Eric Wang, <fonqing@gmail.com>
 * @version 1.0.0
 */
class UserSession
{
    /**
     * @var BaseModel $user
     * @var UserInterface $user
     */
    private mixed $user;
    private array $userInfo = [];
    private string|array $idField;

    private array $privileges = [];

    public function __construct()
    {
    }

    /**
     * Set current user model
     *
     * @param mixed $user
     * @throws BusinessException
     */
    public function set(mixed $user): void
    {
        if ($user) {
            if (!($user instanceof BaseModel)) {
                throw new BusinessException("Model must be an instance of \\wing\\core\\BaseModel");
            }
            if (!method_exists($user, 'getPrivileges')) {
                throw new BusinessException("Auth Model must implements \\wing\\core\\UserInterface");
            }
            if (!method_exists($user, 'isSuperAdmin')) {
                throw new BusinessException("Auth Model must implements \\wing\\core\\UserInterface");
            }
            $this->user = $user;
            $this->userInfo = $user->toArray();
            $this->idField = $user->getPk();
            $this->privileges = $user->getPrivileges();
        }
    }

    /**
     * Get the user model
     *
     * @return BaseModel|null
     */
    public function getUser(): ?BaseModel
    {
        return $this->user;
    }

    /**
     * Check if current user is logged in
     *
     * @return bool
     */
    public function isLogin(): bool
    {
        if(!isset($this->user) || !$this->user){
            return false;
        }
        if(method_exists($this->user, 'isEmpty')) {
            return !$this->user->isEmpty();
        }
        return false;
    }

    /**
     * 超级用户判断
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        if (!$this->isLogin()) {
            return false;
        }
        if (!$this->user) {
            return false;
        }
        return $this->user->isSuperAdmin() ?? false;
    }

    /**
     * Get current user's primary id value
     *
     * @throws BusinessException
     */
    public function getUserId()
    {
        if (!$this->isLogin()) {
            return '';
        }
        if (is_string($this->idField)) {
            return $this->userInfo[$this->idField] ?? '';
        }
        throw new BusinessException("Unsupported union primary key");
    }

    /**
     * Get current user's information array
     *
     * @return array
     */
    public function getUserInfo(): array
    {
        return $this->userInfo;
    }

    /**
     * Get current user's privileges array
     *
     * @param string $module
     * @return array
     */
    public function getPrivileges(string $module = 'default'): array
    {
        return $this->privileges[$module] ?? [];
    }
}