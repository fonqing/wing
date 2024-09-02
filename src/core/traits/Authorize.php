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
        [$module, $contr, $action] = [
            $this->getModuleName(),
            $this->getControllerName(),
            $this->getActionName()
        ];

        // Parse action and get params
        $result = $this->parseAction($action);
        $action = $result['action'];
        $params = $result['params'];
        // Check anonymous access
        if ($this->isAllowed($contr, $action, $this->anonymousRules[$module] ?? [])) {
            return true;
        }
        // Check login
        if ($this->session->isLogin()) {
            // Check authorize whitelists
            if ($this->isAllowed($contr, $action, $this->uncheckRules[$module] ?? [])) {
                return true;
            }
            // Get all user privileges
            $rules = $this->session->getPrivileges($module);
            // Check if the action is in rules
            if ($this->isAllowed($contr, $action, $rules[$module] ?? [])) {
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
    protected function hasPrivilege(string $ca, string $module, array $params = []): bool
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
     * Shortcut for hasPrivilege.
     * Alias for hasPrivilege.
     *
     * @param string $ca
     * @param array $params
     * @param string $module
     * @return bool
     */
    protected function hasAuth(string $ca, string $module, array $params = []): bool
    {
        return $this->hasPrivilege($ca, $module, $params);
    }

    /**
     * Get module name.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        $module = app('http')->getName();
        if(empty($module)) {
            $module = Config::get('app.default_module', '');
        }
        if(empty($module)) {
            $module = Config::get('app.default_app', '');
        }
        return empty($module) ? 'index' : strtolower($module);
    }

    /**
     * Get controller name.
     *
     * @return string
     */
    protected function getControllerName(): string
    {
        $contr = $this->request->controller(true);
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
     * Get action name.
     *
     * @return string
     */
    protected function getActionName(): string
    {
        $action = $this->request->action(true);
        return empty($action) ? 'index' : strtolower($action);
    }

    /**
     * @param array $rules
     * @return void
     */
    protected function setAnonymousRules(array $rules): void
    {
        $this->anonymousRules = $rules;
    }

    /**
     * @param array $rules
     * @return void
     */
    protected function setUncheckRules(array $rules): void
    {
        $this->uncheckRules = $rules;
    }

    /**
     * @param string $action
     * @return void
     */
    protected function setUncheckAction(string $action): void
    {
        $module = $this->getModuleName();
        $contr = $this->getControllerName();
        if(array_key_exists($module, $this->uncheckRules)) {
            $this->uncheckRules[$module][] = [
                $contr . '/' . $action
            ];
        } else {
            $this->uncheckRules[$module] = [
                $contr . '/' . $action
            ] ;
        }
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
        if (empty($rules)) {
            return false;
        }
        if (in_array($c . '/' . $a, $rules) || in_array($c . '/*', $rules)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the params are in request & match the request.
     *
     * @param array $params
     * @return bool
     */
    private function matchParams(array $params): bool
    {
        if (empty($params)) {
            return true;
        }
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