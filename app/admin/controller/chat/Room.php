<?php

namespace app\admin\controller\chat;

use Throwable;
use app\common\controller\Backend;
use app\common\service\chat\AdminChatService;

/**
 * 后台聊天室：查看当天会话、进入会话聊天、发送风控与报表指令
 */
class Room extends Backend
{
    protected AdminChatService $chatService;

    public function initialize(): void
    {
        parent::initialize();
        $this->chatService = new AdminChatService();
    }

    /**
     * 当天全部会话
     */
    public function index(): void
    {
        $limit   = $this->request->get('limit/d', 50);
        $page    = $this->request->get('page/d', 1);
        $keyword = (string)$this->request->get('keyword/s', '');

        $this->success('', $this->chatService->listToday($limit, $page, trim($keyword)));
    }

    /**
     * 会话消息
     */
    public function messages(): void
    {
        $conversationId = (int)$this->request->get('conversation_id/d', 0);
        if ($conversationId < 1) {
            $this->error('请选择会话');
        }

        try {
            $data = $this->chatService->messages($conversationId);
        } catch (Throwable $e) {
            $this->error($e->getMessage() ?: '获取聊天记录失败');
        }

        $this->success('', $data);
    }

    /**
     * 发送风控或报表指令
     */
    public function send(): void
    {
        $conversationId = (int)$this->request->post('conversation_id/d', 0);
        $content        = (string)$this->request->post('content/s', '');
        if ($conversationId < 1) {
            $this->error('请选择会话');
        }

        try {
            $data = $this->chatService->send($conversationId, (int)$this->auth->id, $content);
        } catch (Throwable $e) {
            $this->error($e->getMessage() ?: '操作失败');
        }

        $this->success('', $data);
    }
}
