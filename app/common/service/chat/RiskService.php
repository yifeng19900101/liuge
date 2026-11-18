<?php

namespace app\common\service\chat;

use think\facade\Config;
use app\common\model\ChatConversation;

/**
 * 会话风控：解析管理员「风控+金额」指令、锁定会话
 */
class RiskService
{
    protected string $command;

    public function __construct(?array $config = null)
    {
        $config        = $config ?: (array)Config::get('liu.risk', []);
        $this->command = (string)($config['command'] ?? '风控');
    }

    /**
     * 风控指令前缀
     */
    public function command(): string
    {
        return $this->command;
    }

    /**
     * 解析风控金额，非风控指令返回 null
     * 支持：风控6000、风控 6000、风控6000.5
     */
    public function parseAmount(string $content): ?float
    {
        $pattern = '/^' . preg_quote($this->command, '/') . '\s*([0-9]+(?:\.[0-9]{1,2})?)$/u';
        if (!preg_match($pattern, trim($content), $matches)) {
            return null;
        }

        $amount = (float)$matches[1];
        return $amount > 0 ? $amount : null;
    }

    /**
     * 会话是否已被风控
     */
    public function isRisked(ChatConversation $conversation): bool
    {
        return (int)$conversation->is_risk === 1;
    }

    /**
     * 锁定会话：覆盖风控金额并记录操作人与时间
     */
    public function lock(ChatConversation $conversation, float $amount, int $adminId): void
    {
        $conversation->save([
            'risk_amount'   => $amount,
            'is_risk'       => 1,
            'risk_time'     => time(),
            'risk_admin_id' => $adminId,
        ]);
    }

    /**
     * 风控成功后的机器人回复
     */
    public function replyText(float $amount): string
    {
        return '已风控：风控金额 ' . $this->formatAmount($amount) . "，本会话不可再下单，仅可发送报表";
    }

    /**
     * 金额格式化：整数不带小数，其余最多两位
     */
    protected function formatAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }
}
