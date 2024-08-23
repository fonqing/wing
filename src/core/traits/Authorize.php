<?php

namespace wing\core\traits;

use think\facade\Config;
use wing\core\BaseController;
use wing\exception\BusinessException;
use wing\libs\StringPlus;

/**
 * @mixin BaseController
 */
trait Authorize
{
    /**
     * @var array $anonymousRules 匿名可访问的 action
     */
    protected array $anonymousRules = [
        'default' => [
            'user/login',
            'user/logout',
        ]
    ];

    /**
     * @var array $uncheckRules 不需要检查权限的 action
     */
    protected array $uncheckRules = [];

    /**
     * Authorize the action.
     *
     * Support authorization by module/controller/action with params.
     *
     * @throws BusinessException
     */
    protected function authorize(): bool
    {
        if ($this->session->isSuperAdmin()) {
            return true;
        }
        // Get module/controller/action
        $module = strtolower(app('http')->getName() ?: Config::get('app.default_module', 'admin'));
        $contr = $this->normalizeController($this->request->controller(true));
        $action = strtolower($this->request->action(true) ?: 'index');

        // Parse action and get params
        $result = $this->parseAction($action);
        $action = $result['action'];
        $params = $result['params'];
        // Check anonymous access
        if ($this->isAllowed($contr, $action, $this->anonymousRules[$module] ?? $this->anonymousRules)) {
            return true;
        }
        // Check authorize whitelists
        if ($this->isAllowed($contr, $action, $this->uncheckRules[$module] ?? $this->uncheckRules)) {
            return true;
        }
        // Check login
        if ($this->session->isLogin()) {
            // Get all user privileges
            $rules = $this->session->getPrivileges();
            // Check if the action is in rules
            if ($this->isAllowed($contr, $action, $rules[$module] ?? $rules)) {
                if (empty($params)) {
                    return true;
                }
                // Check if the params matched with the request
                if ($this->matchParams($params)) {
                    return true;
                }
            }
        }
        throw new BusinessException('无权访问');
    }

    /**
     * @param string $ca
     * @param array $params
     * @param string $module
     * @return bool
     */
    public function hasPrivilege(string $ca, array $params = [], string $module = 'default'): bool
    {
        if ($this->session->isSuperAdmin()) {
            return true;
        }
        [$contr, $action] = explode('/', $ca);
        if ($this->isAllowed($contr, $action, $this->session->getPrivileges($module))) {
            if (empty($params)) {
                return true;
            }
            return $this->matchParams($params);
        }
        return false;
    }

    /**
     * @param $contr
     * @return string
     */
    private function normalizeController($contr): string
    {
        $contr = strtolower(trim(trim($contr), '/\\'));
        if (empty($contr)) {
            return 'index';
        }
        if (StringPlus::strContains($contr, '\\')) {
            $contr = substr($contr, strrpos($contr, '\\') + 1);
            if (StringPlus::strEndsWith($contr, 'controller')) {
                return substr($contr, 0, -10);
            }
        }
        return (string) $contr;
    }

    /**
     * Check if the action is in rules.
     *
     * @param string $c
     * @param string $a
     * @param array $rules
     * @return bool
     */
    private function isAllowed(string $c, string $a, array $rules): bool
    {
        if (in_array($c . '/' . $a, $rules) || in_array($c . '/*', $rules)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the params are in request.
     *
     * @param array $params
     * @return bool
     */
    private function matchParams(array $params): bool
    {
        foreach ($params as $key => $value) {
            $has = $this->request->param($key);
            if ($has !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse action and get params.
     *
     * @param string $action
     * @return array
     */
    private function parseAction(string $action): array
    {
        if (empty($action)) {
            $action = 'index';
        }
        $params = [];
        $pos = strpos($action, '?');
        if ($pos !== false) {
            $action = substr($action, 0, $pos);
            $query = substr($action, $pos + 1);
            parse_str($query, $params);
        }
        return [
            'action' => $action,
            'params' => $params
        ];
    }
}