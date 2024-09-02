<?php
declare(strict_types=1);

namespace wing\core;

use think\{Request, Response, App};
use think\model\Collection;
use wing\exception\BusinessException;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var Request
     */
    protected Request $request;

    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * @var UserSession 会话用户
     */
    protected UserSession $session;

    /**
     * @var array 当前可用模型
     */
    protected array $__models__ = [];

    /**
     * @var array $anonymousRules 匿名可访问的 action
     */
    protected array $anonymousRules = [
        'admin' => [
            'user/login',
            'user/logout',
        ]
    ];

    /**
     * @var array $uncheckRules 不需要检查权限的 action
     */
    protected array $uncheckRules = [];

    /**
     * Request
     *
     * @var array
     */
    protected array $unblock = [];

    /**
     * Current model name
     *
     * @var string
     */
    protected string $modelName = '';

    /**
     * @var array $exportConfig 导出配置
     */
    protected array $exportConfig = [
        'name' => '', // 导出文件名
        'title' => '', // 导出表格标题
        'columns' => [], // 字段配置
    ];

    /**
     * Auto query filter based on config
     *
     * @var boolean
     */
    protected bool $autoQueryFilter = true;

    /**
     * @throws BusinessException
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        $this->session = new UserSession();
        $this->initialize();
    }

    /**
     * Setup method
     */
    protected function setup()
    {
    }

    /**
     * Accessible check method
     */
    protected function authorize()
    {
    }


    // 初始化
    /**
     * @throws BusinessException
     */
    protected function initialize(): void
    {
        if (!empty($this->modelName) && class_exists($this->modelName)) {
            $this->setModel($this->modelName);
        }
        // 初始化钩子
        if (method_exists($this, 'setup')) {
            $this->setup();
        }
        // 检查授权
        if (method_exists($this, 'authorize')) {
            $this->authorize();
        }
    }

    /**
     * Register model
     *
     * @param mixed $class
     * @param string $name
     * @throws BusinessException
     */
    protected function setModel(mixed $class, string $name = 'default'): void
    {
        if (isset($this->__models__[$name])) {
            throw new BusinessException('model overwritten');
        }
        if (
            is_string($class) &&
            class_exists($class) &&
            is_subclass_of($class, BaseModel::class)
        ) {
            $this->__models__[$name] = new $class();
            $this->autoConfigExport($name);
            return;
        }
        if (!is_scalar($class) && is_a($class, BaseModel::class)) {
            $this->__models__[$name] = $class;
            $this->autoConfigExport($name);
            return;
        }
        throw new BusinessException('Invalid model:' . htmlentities((string) $class));
    }

    /**
     * Get model instance
     *
     * @param string $name
     * @return BaseModel
     * @throws BusinessException
     */
    protected function getModel(string $name = 'default'): BaseModel
    {
        $name = $this->request->param('__model__/s', $name);
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $name);
        if (!isset($this->__models__[$name])) {
            throw new BusinessException('model not set');
        }
        return $this->__models__[$name];
    }

    protected function setAnonymousRules(array $rules): void
    {
        $this->anonymousRules = $rules;
    }

    protected function setUncheckRules(array $rules): void
    {
        $this->uncheckRules = $rules;
    }

    /**
     * @param string $name
     * @return void
     * @throws BusinessException
     */
    private function autoConfigExport(string $name): void
    {
        $model = $this->getModel($name);
        if (method_exists($model, 'getExportConfig')) {
            $this->exportConfig = $model->getExportConfig();
        }
    }

    /**
     * 控制器中返回 HTTP JSON RESPONSE
     *
     * @param mixed|null $data
     * @param string $msg
     * @param integer $code
     * @return Response
     */
    protected function success(mixed $data = null, string $msg = 'success', int $code = 1): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 控制器中返回 HTTP JSON RESPONSE
     *
     * @param string $msg
     * @param mixed|null $data
     * @param integer $code
     * @return Response
     */
    protected function error(string $msg = 'error', mixed $data = null, int $code = 0): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 合计二维数组某个键的值 支持 多维关联键
     *
     * @param array|Collection $array
     * @param string $key
     * @return float|int
     * @throws BusinessException
     */
    protected function sumField(Collection|array $array, string $key): float|int
    {
        if (!is_array($array) && method_exists($array, 'toArray')) {
            $array = $array->toArray();
        }
        if (empty($array)) {
            return 0;
        }
        if (strpos($key, '.') > 0) {
            $sum = 0;
            $keys = explode('.', $key);
            foreach ($array as $row) {
                $sum += match (count($keys)) {
                    2 => floatval($row[$keys[0]][$keys[1]] ?? 0),
                    3 => floatval($row[$keys[0]][$keys[1]][$keys[2]] ?? 0),
                    4 => floatval($row[$keys[0]][$keys[1]][$keys[2]][$keys[3]] ?? 0),
                    default => throw new BusinessException('后端合计逻辑位数不够'),
                };
            }
            return $sum;
        }
        return array_sum(array_column($array, $key));
    }

    /**
     * 获取请求参数种符合前缀的键值对
     *
     * @param  string $prefix
     * @return array
     */
    public function getParamByPrefix(string $prefix): array
    {
        $data = $this->request->all();
        $res = [];
        $len = strlen($prefix);
        foreach ($data as $key => $value) {
            if (substr($key, 0, $len) === $prefix) {
                $res[substr($key, $len)] = $value;
            }
        }
        return $res;
    }

    /**
     * 直接从请求中获取安全的 值大于0的int数组
     *
     * @param  string $name
     * @return array
     */
    public function getIntArray(string $name): array
    {
        $data = $this->request->param($name);
        if (empty($data)) {
            return [];
        }
        if (is_string($data)) {
            $data = explode(',', $data);
        }
        if (is_array($data)) {
            return array_unique(array_filter($data, function ($value) {
                $value = (int) $value;
                return $value > 0;
            }));
        }
        return [];
    }
}
