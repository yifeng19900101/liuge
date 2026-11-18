<?php

namespace app\api\controller;

use Throwable;
use app\common\controller\Frontend;
use app\common\service\chat\ChatService;
use app\api\validate\Chat as ChatValidate;

/**
 * 会员与机器人的单会话聊天
 * 一次请求返回一次回复，不做消息轮询
 */
class Chat extends Frontend
{
    protected array $noNeedPermission = ['history', 'send', 'clear'];

    protected ChatService $chatService;

    public function initialize(): void
    {
        parent::initialize();
        $this->chatService = new ChatService();
    }

    /**
     * 当天聊天记录
     */
    public function history(): void
    {
        $this->success('', [
            'messages' => $this->chatService->history((int)$this->auth->id),
        ]);
    }

    /**
     * 发送消息并同步返回机器人回复
     */
    public function send(): void
    {
        $content = $this->request->post('content', '');
        if (!is_string($content)) {
            $this->error('请输入聊天内容');
        }
        $content = trim($content);

        try {
            $validate = new ChatValidate();
            $validate->check(['content' => $content]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('', [
            'messages' => $this->chatService->send((int)$this->auth->id, $content),
        ]);
    }

    /**
     * 清空聊天记录，仅保留欢迎语
     */
    public function clear(): void
    {
        $this->success('', [
            'messages' => $this->chatService->clear((int)$this->auth->id),
        ]);
    }
}
