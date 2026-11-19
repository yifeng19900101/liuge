<?php

namespace app\common\service\chat;

use Throwable;
use think\facade\Config;
use app\common\model\User;
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
     * 获取当天会话，不存在时创建并冻结系数、水率、倍数、风控金额
     */
    public function getOrCreate(int $userId): ChatConversation
    {
        $conversation = $this->today($userId);
        if ($conversation) return $conversation;

        $settings = $this->userSettings($userId);
        try {
            return ChatConversation::create([
                'user_id'      => $userId,
                'session_date' => $this->todayDate(),
                'coefficient'  => $settings['coefficient'],
                'water_rate'   => $settings['water_rate'],
                'multiple'     => $settings['multiple'],
                'risk_amount'  => $settings['risk_amount'],
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
     * 会员的结算参数，用于创建会话时冻结
     * 后台系数为水率（如 0.04 表示 4%），实际结算系数 = 1 - 水率
     * @return array{coefficient: float, water_rate: float, multiple: float, risk_amount: float}
     */
    public function userSettings(int $userId): array
    {
        $config = (array)Config::get('liu', []);
        $user   = User::field(['coefficient', 'multiple'])->find($userId);

        $rate = $user ? (float)$user->coefficient : 0.0;
        if ($rate <= 0 || $rate >= 1) {
            // 兜底：全局配置中的 coefficient 已是结算系数（如 0.96），水率反推得到
            $coefficient = (float)($config['coefficient'] ?? 0.96);
            $waterRate   = round(1 - $coefficient, 4);
        } else {
            $waterRate   = round($rate, 4);
            $coefficient = round(1 - $waterRate, 4);
        }

        $multiple = $user ? (float)$user->multiple : 0.0;
        if ($multiple <= 0) {
            $multiple = (float)($config['multiple'] ?? 47);
        }

        return [
            'coefficient' => $coefficient,
            'water_rate'  => $waterRate,
            'multiple'    => $multiple,
            'risk_amount' => (float)($config['risk_amount'] ?? 1000),
        ];
    }

    /**
     * 会话冻结的结算参数
     * @return array{coefficient: float, water_rate: float, multiple: float, risk_amount: float}
     */
    public function settings(ChatConversation $conversation): array
    {
        $coefficient = (float)$conversation->coefficient;
        $waterRate   = $conversation->water_rate;
        // 兼容未回填水率的历史会话
        $waterRate = $waterRate === null ? round(1 - $coefficient, 4) : (float)$waterRate;

        return [
            'coefficient' => $coefficient,
            'water_rate'  => $waterRate,
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
