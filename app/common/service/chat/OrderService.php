<?php

namespace app\common\service\chat;

use app\common\model\ChatOrder;
use app\common\model\ChatConversation;

/**
 * 下单服务：把解析出的号码与金额写入 chat_order，仅保留号码与金额
 */
class OrderService
{
    /**
     * 记录一条下单，解析失败时不写入
     * @param array $parsed LiuService::parseOrderCodes 的返回值
     */
    public function record(ChatConversation $conversation, array $parsed): ?ChatOrder
    {
        $list = (array)($parsed['list'] ?? []);
        if (!$list) return null;

        return ChatOrder::create([
            'user_id'         => (int)$conversation->user_id,
            'conversation_id' => (int)$conversation->id,
            'items'           => json_encode($list, JSON_UNESCAPED_UNICODE),
            'item_count'      => count($list),
            'total_amount'    => array_sum($list),
        ]);
    }

    /**
     * 汇总会话内全部下单的号码金额
     * @return array<int, int> 号码 => 累计金额
     */
    public function mergeList(int $conversationId): array
    {
        $list   = [];
        $orders = ChatOrder::where('conversation_id', $conversationId)->column('items');
        foreach ($orders as $items) {
            $decoded = json_decode((string)$items, true);
            if (!is_array($decoded)) continue;
            foreach ($decoded as $code => $amount) {
                $code        = (int)$code;
                $list[$code] = ($list[$code] ?? 0) + (int)$amount;
            }
        }
        ksort($list);
        return $list;
    }

    /**
     * 号码金额明细转文本，如 04×50、06×50
     * @param array<int, int> $items
     */
    public function formatItems(array $items): string
    {
        $parts = [];
        foreach ($items as $code => $amount) {
            $parts[] = str_pad((string)$code, 2, '0', STR_PAD_LEFT) . '×' . $amount;
        }
        return implode('、', $parts);
    }
}
