# wing

Fast developement library for Thinkphp8

# requirements

- PHP >= 8.1
- Thinkphp >= 8.0

# Install

```
composer require fonqing/wing
```

## 使用注意事项

- 使用本类库只能配合 thinkphp8.0 使用
- 控制器继承 `\wing\core\BaseController` 类
- 模型继承 `\wing\core\BaseModel` 类
- 控制器内通过 `use \wing\core\traits\AutoCrud;` 启用自动 CRUD 操作
- 控制器内通过 `use \wing\core\traits\Authorize;` 启用权限控制
- 控制器内通过 `setup`方法进行必要的配置

## 控制器示例

```php
<?php
namespace app\controller;

use wing\core\BaseController;
use wing\core\traits\AutoCrud;
use wing\core\traits\Authorize;
use app\model\News;
use app\model\User;


class NewsController extends BaseController
{
    use AutoCrud, Authorize;

    public function setup()
    {
        // 开启自动CRUD操作时,需要设置当前控制器操作的模型类
        $this->setModel(News::class);
        // 设置当前session的用户模型实例
        // 必须继承\wing\core\BaseModel类,
        // 且实现\wing\core\UserInterface接口
        $this->session->set(User::find(1));
    }

    // 启用自动CRUD后, 以下 action 即可自动工作
    /*
     news/create
     news/update
     news/index
     news/delete
     news/detail
     news/export (需要在模型配置导出字段)
     */

     /**
      * 自定义创建数据前的操作
      * @param array $data 数据数组
      */
     public function beforeCreate($data) {
        $data['user_id'] = $this->session->getUserId();
        return $data;
     }
}
```

## 模型代码示例

在 think-orm 模型的基础上扩展增加了部分模型属性, 用于自动 CRUD 和导出

```php
<?php
namespace app\model;

use wing\core\BaseModel;

class News extends BaseModel {
    protected $table = 'news';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $dateFormat = 'Y-m-d H:i:s';
   /**
     * Model fields definition, used for search, create, update, index.
     *
     * @var array
     */
    public static array $fields = [
        'create' => ['cate_id', 'title', 'cover_image', 'content'],// allow to create fields
        'update' => ['cate_id', 'title', 'cover_image', 'content'], // allow update fields
        'index'  => ['id','cate_id', 'title', 'cover_image', 'content', 'create_time'], // allow index fields
    ];

    /**
     * Model data validation rules, used for create and update.
     *
     * @var array
     */
    public static array $rules = [
        'create' => [
            'cate_id' => 'require|integer',
            'title'   => 'require|max:240',
            'content' => 'require'
        ],
        'update' => [
            'cate_id' => 'require|integer',
            'title'   => 'require|max:240',
            'content' => 'require'
        ]
    ];

    /**
     * Validation messages
     *
     * @var array
     */
    public static array $messages = [
        'cate_id.require' => '请选择分类',
        'cate_id.integer' => '请选择分类',
        'title.require'   => '请输入标题',
        'title.max'       => '标题不能超过240字',
        'content.require' => '请输入内容'
    ];

    /**
     * Order way, used for index.
     *
     * @var array
     */
    protected static array $order = [
       'id' => 'desc'
    ];

    /**
     * @var int
     */
    public static int $pageSize = 10; // 列表页每页显示条数

    /**
     * @var bool
     */
    public static bool $cache = false; // 列表页是否开启缓存
}
```
