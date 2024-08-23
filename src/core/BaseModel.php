<?php
namespace wing\core;

use wing\libs\StringPlus;
use think\Request;
use think\db\Query;
use think\facade\Cache;
use think\facade\Validate;
use think\Model;

/**
 * @method static where(mixed $field, $op = null, $condition = null)
 * @method static queryFilter() static
 */
class BaseModel extends Model
{

    /**
     * 模型字段配置
     *
     * @var array table fields setting
     */
    public static array $fields = [
        'create' => [],//array or string joined by comma
        'update' => [],
        'index' => [],
        'search' => [],
    ];

    /**
     * 模型验证规则
     *
     * @var array 验证规则
     */
    public static array $rules = [
        'create' => [],
        'update' => []
    ];

    /**
     * @var array
     */
    public static array $order = [];

    public static array $messages = [];

    /**
     * @var int
     */
    public static int $pageSize = 10;

    /**
     * @var bool
     */
    public static bool $cache = false;


    /**
     * @var array 模型验证错误信息
     */
    protected array $error = [];

    /**
     * 获取错误信息
     *
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * 获取模型字段与字段注释的键值对
     *
     * @return array
     * @throws \Throwable
     */
    public static function getFieldLabels(): array
    {
        $model = new static();
        $table = $model->getTable();
        $key = 'field_labels_' . $table;
        return Cache::tag('db_fields_cache')->remember($key, function () use ($model) {
            $fields = $model->getFields();
            $data = [];
            foreach ($fields as $id => $field) {
                $type = trim(str_ireplace(['signed', 'unsigned'], '', $field['type']));
                $data[$id] = [
                    'type' => StringPlus::strContains($type, '(') ? substr($type, 0, strpos($type, '(')) : $type,
                    'label' => str_replace([
                        'id',
                        'ID',
                        'iD',
                        'Id',
                    ], '', $field['comment']),
                ];
            }
            unset($fields, $model);
            return $data;
        }, 0);
    }

    /**
     * 检查模型可用字段
     *
     * 参数为字符串时 当存在此字段则返回该字段名，否则返回空
     * 参数为数组时 按数组顺序检查存在哪个就返回哪个，都不存在返回空
     *
     * @param  string|array $field
     * @return string
     */
    public function hasField(string|array $field): string
    {
        $fields = $this->checkAllowFields();
        if (is_string($field)) {
            return in_array($field, $fields) ? $field : '';
        }
        foreach ($field as $row) {
            if (in_array($row, $fields)) {
                return $row;
            }
        }
        return '';
    }

    /**
     * 适用于不同的模型有不同的时间字段的情况下，进行开始时间大于等于条件查询
     *
     * @alias byTimeStart
     *
     * @param  Query      $query
     * @param  int|string $start 开始时间字符串或者时间戳
     * @param  mixed      $field 附加的时间字段
     * @return void
     */
    public function scopeByTimeStart(Query $query, int|string $start, mixed $field = ''): void
    {
        if (!is_numeric($start)) {
            $start = strtotime($start);
        }
        $list = ['approval_time', 'update_time', 'create_time', 'created_at', 'updated_at'];
        if (!empty($field) && (is_array($field) || is_string($field))) {
            $fields = is_array($field) ? $field : [$field];
            array_unshift($list, ...$fields);
        }
        $cond = $this->hasField($list);
        if (!empty($cond)) {
            $query->whereTime($cond, '>=', $start);
        }
    }

    /**
     * 基于指定字段的时间段查询
     *
     * @param  Query  $query
     * @param  string $field          时间字段名
     * @param  mixed  $range          起始时间数组
     * @param  bool   $withFieldCheck 是否严格检查字敦是否存在
     * @return void
     */
    public function scopeByTimeRange(Query $query, string $field, mixed $range, bool $withFieldCheck = false): void
    {
        if (is_array($range) && 2 == count($range) && !empty($range[0]) && !empty($range[1])) {
            $start = date('Y-m-d', is_int($range[0]) ? $range[0] : strtotime($range[0]));
            $end = date('Y-m-d 23:59:59', is_int($range[1]) ? $range[1] : strtotime($range[1]));
            if ($withFieldCheck) {
                $cond = $this->hasField($field);
                if (!empty($cond)) {
                    $query->whereTime($cond, 'between', [$start, $end]);
                }
            } else {
                $query->whereTime($field, 'between', [$start, $end]);
            }
        }
    }

    /**
     * 针对 float integer double decimal 字段范围查询
     *
     * @param  Query  $query
     * @param  string $field
     * @param  mixed  $range
     * @return void
     */
    public function scopeByNumberRange(Query $query, string $field, mixed $range): void
    {
        if (is_array($range) && 2 == count($range)) {
            $min = floatval($range[0]);
            $max = floatval($range[1]);
            if ($max > $min) {
                $query->whereBetween($field, [$min, $max]);
            } elseif ($max === $min) {
                $query->where($field, '=', $min);
            }
        }
    }

    /**
     * 使用验证规则验证数据
     *
     * @param  array  $data
     * @param  string $scene
     * @return bool
     */
    public function isValid(array $data, string $scene): bool
    {
        $rules = static::$rules[$scene] ?? [];
        if (empty($rules)) {
            return true;
        }

        $validate = new Validate();
        $message = static::$messages ?? [];
        $result = $validate->message($message)->check($data, $rules);
        if ($result) {
            return true;
        }
        $error = $validate->getError();
        if (is_string($error)) {
            $this->error[] = $error;
        } else {
            $this->error = array_merge($this->error, $error);
        }
        return false;
    }

    /**
     * 自动根据 query string 进行查询
     *
     * @param Query $model
     */
    public function scopeQueryFilter(Query $model): void
    {
        $fields = static::$fields['search'] ?? [];
        if (!empty($fields)) {
            $query = request()->all();
            foreach ($fields as $field => $rule) {
                $value = $query[$field] ?? '';
                if (empty($value)) {
                    continue;
                }
                switch ($rule) {
                    case 'equal':
                    case 'eq':
                    case '=':
                        if (!is_string($value) && !is_numeric($value)) {
                            break;
                        }
                        $model->where($field, $query[$field]);
                        break;
                    case 'like':
                        if (is_string($value)) {
                            $model->whereLike($field, '%' . $query[$field] . '%');
                        }
                        break;
                    case 'datetime':
                        if (is_array($value) && 2 === sizeof($value)) {
                            $model->whereBetweenTime($field, $value[0], $value[1]);
                        }
                        break;
                    case 'time_start':
                        $model->whereTime($field, '>=', $value);
                        break;
                    case 'time_end':
                        $model->whereTime($field, '<=', $value);
                        break;
                    case 'in':
                        if (is_array($value)) {
                            $model->whereIn($field, $value);
                        }
                    // no break
                    case 'ex':
                        if (is_array($value)) {
                            $model->whereNotIn($field, $value);
                        }
                        break;
                }
            }
        }
    }
}