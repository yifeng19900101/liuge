<?php

namespace app\common\service\chat;

use app\common\model\ChatMessage;
use app\common\model\ChatConversation;

/**
 * 聊天记录服务：用户消息与机器人回复都写入 chat_message
 */
class MessageService
{
    /**
     * 写入一条消息
     */
    public function create(ChatConversation $conversation, string $role, string $content): array
    {
        $message = ChatMessage::create([
            'user_id'         => (int)$conversation->user_id,
            'conversation_id' => (int)$conversation->id,
            'role'            => $role,
            'content'         => $content,
        ]);
        return $message->toArray();
    }

    /**
     * 查询会话最近的消息（按时间正序返回）
     */
    public function listByConversation(int $conversationId, int $limit = 50): array
    {
        $messages = ChatMessage::where('conversation_id', $conversationId)
            ->field(['id', 'role', 'content', 'create_time'])
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        return array_reverse($messages);
    }

    /**
     * 清空会话的聊天记录
     */
    public function clear(int $conversationId): void
    {
        ChatMessage::where('conversation_id', $conversationId)->delete();
    }

    /**
     * 统一消息输出结构
     */
    public function format(array $message): array
    {
        return [
            'id'          => (int)$message['id'],
            'role'        => (string)$message['role'],
            'content'     => (string)$message['content'],
            'create_time' => (int)$message['create_time'],
        ];
    }
}
