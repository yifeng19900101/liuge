<?php

namespace app\admin\controller\chat;

use app\common\controller\Backend;
use app\admin\library\traits\ReadOnlyBackend;
use app\admin\model\chat\Message as MessageModel;

/**
 * 聊天记录（只读）
 */
class Message extends Backend
{
    use ReadOnlyBackend;

    /**
     * @var object
     * @phpstan-var MessageModel
     */
    protected object $model;

    protected array $withJoinTable = ['user' => ['id', 'username', 'nickname', 'avatar']];

    protected string|array $quickSearchField = ['user_id', 'conversation_id', 'content'];

    protected string|array $defaultSortField = 'id,desc';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new MessageModel();
    }
}
