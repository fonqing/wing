# wing

Fast development library for Thinkphp8

# requirements

- PHP >= 8.1
- Thinkphp >= 8.0

# Install

```
composer require fonqing/wing
```

## Usage

- This library is only for thinkphp8.0
- Your controller should extend `\wing\core\BaseController`
- Your model should extend `\wing\core\BaseModel`
- Use `use \wing\core\traits\AutoCrud;` to enable auto CRUD operations for controller
- Use `use \wing\core\traits\Authorize;` to enable login and RBAC check for controller
- In your controller class should implement `setup` method to set current model
## Controller example

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
        // When AutoCRUD enabled you should configure the Model class
        $this->setModel(News::class);
        // Set the uncheck(Skip RBAC check) action for current controller
        $this->setUncheckAction('dict');
        // Or you can set all uncheck rules for whole application
        $this->setUncheckRules([
            'module' => ['']
        ])
        // Set current use model for Authorize trait
        // The User model must extend \wing\core\BaseModel and implement \wing\core\UserInterface,
        // Help your self to get the user id form request token etc.
        $this->session->set(User::find(1));
    }

    // When AuthCRUD enabled, Follow controller action can be used
    /*
     news/create
     news/update
     news/index
     news/delete
     news/detail
     news/export (TODO)
     */

     /**
      * Callback before create data
      * @param array $data model data
      */
     public function beforeCreate($data) {
        $data['user_id'] = $this->session->getUserId();
        return $data;
     }
}
```

## Model example

The BaseModel class is extended from think-orm/Model, Add some new attributes or method for AutoCrud CRUD

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
        'cate_id.require' => 'Please select a category',
        'cate_id.integer' => 'Please select a category',
        'title.require'   => 'Please enter a title',
        'title.max'       => 'The title is too long',
        'content.require' => 'Please enter content'
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
    public static int $pageSize = 10; // Page size for index

    /**
     * @var bool
     */
    public static bool $cache = false; // Cache for index
}
```
