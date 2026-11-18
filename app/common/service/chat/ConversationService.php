<?php

namespace app\common\service\chat;

use Throwable;
use think\facade\Config;
use app\common\model\ChatConversation;

/**
 * 会话服务：每个会员每天最多一个会话，会话创建时冻结结算参数
 */
class ConversationService
{
    /**
     * 当天日期
     */
    public function todayDate(): string
    {
        return date('Y-m-d');
    }

    /**
     * 获取当天的会话
     */
    public function today(int $userId): ?ChatConversation
    {
        return ChatConversation::where('user_id', $userId)
            ->where('session_date', $this->todayDate())
            ->find();
    }

    /**
     * 获取当天会话，不存在时创建并冻结系数、倍数、风控金额
     */
    public function getOrCreate(int $userId): ChatConversation
    {
        $conversation = $this->today($userId);
        if ($conversation) return $conversation;

        $config = (array)Config::get('liu', []);
        try {
            return ChatConversation::create([
                'user_id'      => $userId,
                'session_date' => $this->todayDate(),
                'coefficient'  => $config['coefficient'] ?? 0.96,
                'multiple'     => $config['multiple'] ?? 47,
                'risk_amount'  => $config['risk_amount'] ?? 1000,
                'last_message' => '',
                'last_time'    => time(),
            ]);
        } catch (Throwable $e) {
            // 并发创建时会命中 (user_id, session_date) 唯一索引，重新读取即可
            $conversation = $this->today($userId);
            if ($conversation) return $conversation;
            throw $e;
        }
    }

    /**
     * 会话冻结的结算参数
     * @return array{coefficient: float, multiple: float, risk_amount: float}
     */
    public function settings(ChatConversation $conversation): array
    {
        return [
            'coefficient' => (float)$conversation->coefficient,
            'multiple'    => (float)$conversation->multiple,
            'risk_amount' => (float)$conversation->risk_amount,
        ];
    }

    /**
     * 更新会话的最后一条消息
     */
    public function touch(ChatConversation $conversation, array $message): void
    {
        $conversation->save([
            'last_message' => mb_substr((string)($message['content'] ?? ''), 0, 255),
            'last_time'    => (int)($message['create_time'] ?? time()),
        ]);
    }
}
