<?php

namespace app\common\service\chat;

use app\common\model\ChatConversation;
use app\common\service\LiuService;

/**
 * 报表服务：按会话冻结的参数汇总当天全部下单
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
     * @param array $settings 会话冻结的结算参数
     */
    public function todayReport(ChatConversation $conversation, array $settings): string
    {
        $title = '今日报表（' . $conversation->session_date . '）';
        $items = $this->orderService->mergeList((int)$conversation->id);
        if (!$items) {
            return $title . "\n今日暂无下单记录";
        }

        $settle   = LiuService::settleOrderCodes($items, $settings);
        $quantity = array_sum($items);
        $waifa    = 0;
        $settled  = 0;
        foreach ($settle['items'] as $item) {
            $waifa   += (float)$item['waifa'];
            $settled += (float)$item['settled'];
        }

        $lines = [
            $title,
            '下单：' . $this->orderService->formatItems($items) . '（共 ' . count($items) . ' 个号码，金额 ' . LiuService::formatAmount((float)$quantity) . '）',
            '外发合计：' . LiuService::formatAmount($waifa),
            '结算合计：' . LiuService::formatAmount($settled),
        ];
        return implode("\n", $lines);
    }
}
