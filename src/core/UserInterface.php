<?php

namespace wing\core;

interface UserInterface
{
    /**
     * 管理员模型必须实现此接口
     *
     * @param string $module
     * @return array
     */
    public function getPrivileges(string $module = 'default'): array;

    /**
     * 管理员模型或用户模型必须实现此接口
     *
     * @return bool
     */
    public function isSuperAdmin(): bool;
}