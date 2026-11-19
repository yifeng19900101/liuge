<?php

namespace app\common\service;

/**
 * 聊天机器人回复文案
 * 只负责把解析结果转成文案，流程控制在 ChatService 中
 */
class ChatBot
{
    /**
     * 会话欢迎语
     */
    public static function welcome(): string
    {
        return "你好，我是机器人助手。\n发送下单指令即可，例如：4.6.8各50";
    }

    /**
     * 根据解析结果生成机器人回复
     * 号码一行一个，带生肖前缀；汇总行放在最后
     * 例：猴11×50 / 猴23×50 / 已识别 2 个号码，合计 100，用户已下单
     * @param array $result LiuService::parseOrderCodes 的返回值
     */
    public static function reply(array $result): string
    {
        if (($result['count'] ?? 0) < 1) {
            $reply = '未识别到有效号码，请按此格式发送：4.6.8各50';
            if ($result['warnings']) {
                $reply .= "\n未识别：" . self::formatWarnings($result['warnings']);
            }
            return $reply;
        }

        $lines = [];
        foreach ($result['list'] as $code => $amount) {
            $zodiac  = Liuconfig::$numToZodiac[$code] ?? '';
            $lines[] = $zodiac . str_pad((string)$code, 2, '0', STR_PAD_LEFT) . '×' . $amount;
        }

        if ($result['warnings']) {
            $lines[] = '未识别：' . self::formatWarnings($result['warnings']);
        }

        $lines[] = '已识别 ' . $result['count'] . ' 个号码，合计 ' . $result['quantity'] . '，用户已下单';

        return implode("\n", $lines);
    }

    /**
     * 格式化解析器的未匹配片段提示
     * @param array $warnings 解析器返回的警告列表
     */
    protected static function formatWarnings(array $warnings): string
    {
        $items = [];
        foreach ($warnings as $warning) {
            $items[] = trim((string)preg_replace('/^未匹配片段[:：]/u', '', (string)$warning));
        }
        return implode('；', array_filter($items));
    }
}
