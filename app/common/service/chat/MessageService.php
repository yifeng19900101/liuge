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
     * @param int $adminId 管理员消息时的管理员ID
     */
    public function create(ChatConversation $conversation, string $role, string $content, int $adminId = 0): array
    {
        $message = ChatMessage::create([
            'user_id'         => (int)$conversation->user_id,
            'conversation_id' => (int)$conversation->id,
            'role'            => $role,
            'admin_id'        => $adminId,
            'content'         => $content,
        ]);
        return $message->toArray();
    }

    /**
     * 查询会话最近的消息（按时间正序返回）
     * @param bool $includeAdmin 是否包含管理员操作消息（会员端不展示）
     */
    public function listByConversation(int $conversationId, int $limit = 50, bool $includeAdmin = true): array
    {
        $query = ChatMessage::where('conversation_id', $conversationId)
            ->field(['id', 'role', 'admin_id', 'content', 'create_time']);

        if (!$includeAdmin) {
            $query->whereIn('role', ['user', 'bot']);
        }

        $messages = $query->order('id', 'desc')
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
            'admin_id'    => (int)($message['admin_id'] ?? 0),
            'content'     => (string)$message['content'],
            'create_time' => (int)$message['create_time'],
        ];
    }
}
