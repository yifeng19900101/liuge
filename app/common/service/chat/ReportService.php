<?php

namespace app\common\service\chat;

use app\common\model\ChatConversation;
use app\common\service\LiuService;
use app\common\service\Liuconfig;

/**
 * 报表服务：按会话冻结的参数汇总当天全部下单
 * 输出格式：
 * 狗04=50   【-2206】
 * 小计:150   氺6
 * 系47氵0.04
 * ###总计:150  总氺:6###
 */
class ReportService
{
    protected OrderService $orderService;

    public function __construct(?OrderService $orderService = null)
    {
        $this->orderService = $orderService ?: new OrderService();
    }

    /**
     * 生成今日报表文本
     * 收入 = 小计 × 系数；每行结算 = 收入 - 本号码金额 × 倍数；水钱 = 小计 × 水率
     * @param array $settings 会话冻结的结算参数
     */
    public function todayReport(ChatConversation $conversation, array $settings): string
    {
        $title = '今日报表（' . $conversation->session_date . '）';
        $items = $this->orderService->mergeList((int)$conversation->id);
        if (!$items) {
            return $title . "\n今日暂无下单记录";
        }

        $coefficient = (float)($settings['coefficient'] ?? 0.96);
        // 水率使用会话冻结值
        $waterRate   = (float)($settings['water_rate'] ?? round(1 - $coefficient, 4));
        $multiple    = (float)($settings['multiple'] ?? 47);

        // 小计 = 全部号码下单金额之和
        $subtotal = (float)array_sum($items);
        // 收入 = 小计 × 系数；水钱 = 小计 × 水率
        $income    = $subtotal * $coefficient;
        $water     = $subtotal * $waterRate;

        // 金额从大到小排列，亏损多的号码在前
        uksort($items, fn($a, $b) => $items[$b] <=> $items[$a] ?: $b <=> $a);

        $lines = [$title];
        foreach ($items as $code => $amount) {
            $settled = $income - (float)$amount * $multiple;
            $lines[] = $this->line((int)$code, (float)$amount, $settled);
        }
        $lines[] = '小计:' . LiuService::formatAmount($subtotal) . '   氺' . LiuService::formatAmount($water);
        $lines[] = '系' . LiuService::formatAmount($multiple) . '氵' . $this->formatRate($waterRate);
        $lines[] = '###总计:' . LiuService::formatAmount($subtotal) . '  总氺:' . LiuService::formatAmount($water) . '###';

        return implode("\n", $lines);
    }

    /**
     * 明细行：生肖 + 号码=下单金额   【结算金额】
     */
    protected function line(int $code, float $amount, float $settled): string
    {
        $zodiac = Liuconfig::$numToZodiac[$code] ?? '';
        return $zodiac . str_pad((string)$code, 2, '0', STR_PAD_LEFT) . '=' . LiuService::formatAmount($amount) .
            '   【' . LiuService::formatAmount($settled) . '】';
    }

    /**
     * 水率格式化：最多四位小数，去掉末尾零
     */
    protected function formatRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 4, '.', ''), '0'), '.');
    }
}
