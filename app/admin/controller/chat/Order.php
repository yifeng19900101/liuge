<?php

namespace app\admin\controller\chat;

use app\common\controller\Backend;
use app\admin\library\traits\ReadOnlyBackend;
use app\admin\model\chat\Order as OrderModel;

/**
 * 下单记录（只读）
 */
class Order extends Backend
{
    use ReadOnlyBackend;

    /**
     * @var object
     * @phpstan-var OrderModel
     */
    protected object $model;

    protected array $withJoinTable = ['user' => ['id', 'username', 'nickname', 'avatar']];

    protected string|array $quickSearchField = ['user_id', 'conversation_id', 'user.username', 'user.nickname'];

    protected string|array $defaultSortField = 'id,desc';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new OrderModel();
    }
}
