<?php

namespace app\admin\controller\chat;

use app\common\controller\Backend;
use app\admin\library\traits\ReadOnlyBackend;
use app\admin\model\chat\Conversation as ConversationModel;

/**
 * 聊天会话（只读）
 */
class Conversation extends Backend
{
    use ReadOnlyBackend;

    /**
     * @var object
     * @phpstan-var ConversationModel
     */
    protected object $model;

    protected array $withJoinTable = ['user' => ['id', 'username', 'nickname', 'avatar']];

    protected string|array $quickSearchField = ['user_id', 'user.username', 'user.nickname'];

    protected string|array $defaultSortField = 'id,desc';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new ConversationModel();
    }
}
