<?php

namespace app\common\service\chat;

use think\facade\Config;

/**
 * 时间限制服务：每天指定时间之后只允许发送报表指令
 */
class TimeLimitService
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: (array)Config::get('liu.report', []);
    }

    /**
     * 报表指令
     */
    public function command(): string
    {
        return (string)($this->config['command'] ?? '报表');
    }

    /**
     * 限制起始时间，如 21:30:00
     */
    public function cutoff(): string
    {
        return (string)($this->config['cutoff'] ?? '21:30:00');
    }

    /**
     * 是否已进入限制时段
     */
    public function isRestricted(?int $timestamp = null): bool
    {
        $timestamp  = $timestamp ?: time();
        $cutoffTime = strtotime(date('Y-m-d') . ' ' . $this->cutoff());
        if ($cutoffTime === false) return false;
        return $timestamp >= $cutoffTime;
    }

    /**
     * 是否为报表指令（内容需完全一致）
     */
    public function isReportCommand(string $content): bool
    {
        return trim($content) === $this->command();
    }

    /**
     * 限制时段的统一回复
     */
    public function restrictedMessage(): string
    {
        return '当前不可操作';
    }
}
