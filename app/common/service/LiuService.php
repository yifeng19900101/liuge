<?php

namespace app\common\service;

use think\facade\Config;

class LiuService
{
//https://vbnbhptvzp.111192hi.app:3172/welcome.html#dh1

    /**
     * 文字选号别名：兰、篮 统一为 蓝；兰单/篮单 => 蓝单，兰双/篮双 => 蓝双
     */
    private static array $wordAlias = [
        '兰'   => '蓝',
        '篮'   => '蓝',
        '兰单' => '蓝单',
        '篮单' => '蓝单',
        '兰双' => '蓝双',
        '篮双' => '蓝双',
    ];

    /**
     * 岁数占位词：解析前把 门N / 门N岁 / N岁 替换为该词，再走文字选号通道
     */
    private static function ageToken(int $age): string
    {
        return '§' . $age . '§';
    }

    /**
     * 文字选号配置：生肖 + 五行 + 合数单双 + 波色 + 岁数
     * @return array<string, int[]>
     */
    private static function getWordMap(): array
    {
        $map = array_merge(
            Liuconfig::$zodiacMap,
            Liuconfig::$wuxingMap,
            Liuconfig::$heshuDanShuang,
            Liuconfig::$boMap,
        );

        // 波色单双：波色号码按号码本身单偶切分，如 蓝单 = 蓝波中的单数
        foreach (Liuconfig::$boMap as $color => $codes) {
            $map[$color . '单'] = array_values(array_filter($codes, fn($code) => $code % 2 === 1));
            $map[$color . '双'] = array_values(array_filter($codes, fn($code) => $code % 2 === 0));
        }

        // 岁数选号：$numToZodiac 定义岁数对应的生肖，再用 $zodiacMap 取出该生肖全部号码
        foreach (Liuconfig::$numToZodiac as $age => $zodiac) {
            $map[self::ageToken((int)$age)] = Liuconfig::$zodiacMap[$zodiac];
        }

        return $map;
    }

    /**
     * 解析号码下单内容，兼容点号、顿号、空格等分隔符与多种数量词
     * 支持写法：4.6.8各50。05.17各30 / 4、6、8各50 / 4 6 8 每个数 50 / 4.6.8=50 5.17 下30
     * 金额可带单位（元、块、米、闷、斤、刀、¥、井），也可不带：4.6.8各50元 / 4.6.8各¥50 / 4.6.8各50
     * 无效字符（岁、税）按分隔符处理，多段内容可连续发送：6.10.11.1岁各25。4.8.12岁各50
     * 文字选号（生肖、五行、合数单双）按 Liuconfig 配置展开为号码：马猴鼠狗猪各15。虎蛇各5。
     * 岁数选号（门N / 门N岁，必须带「门」）按 $numToZodiac 定位生肖，再按 $zodiacMap 展开号码：门7岁7各10
     * 号码有效范围 1-49，超出范围的号码单独剔除并写入 warnings：门60各10 / 4.60.8各10
     * @param string $input 用户输入的下单内容
     * @return array{list: array<int, int>, count: int, quantity: int, warnings: array}
     */
    public static function parseOrderCodes(string $input): array
    {
        // 数量词，长词在前避免被短词截断
        $keywords = '各数|数各|个数|数个|每个数|各个|每个|个号|号个|各号|号各|一个|各|个|下|买|押|压|要|搞|砸|=';
        // 分隔符：点号、顿号、空格、逗号、句号、分号、斜杠、竖线、连字符、下划线、中点
        // 连字符兼容各种抄写变体：- － – — − ‐ ‑ ﹣ ~ ～ 〜（微信/手机粘贴常被自动替换）
        // 岁、税为无效字符，同按分隔符处理（不直接删除，避免 1岁0 被拼成 10）
        // 门为岁数选号前缀，非法岁数（如 门60）时其残留的 门 也按分隔符处理
        $separator = '[\s\.、,，。;；\/\\\\|·\-_~～〜－–—−‐‑﹣岁税门]+';

        // 文字选号：生肖、五行、合数单双（含 兰单/兰双 别名）
        $wordMap  = self::getWordMap();
        $wordKeys = array_merge(array_keys($wordMap), array_keys(self::$wordAlias));
        // 长词优先，保证 兰单 不被 单 截断
        usort($wordKeys, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $wordPattern = implode('|', array_map(fn($word) => preg_quote($word, '/'), $wordKeys));

        // 金额单位：元、块、米、闷、斤、刀、¥（含全角 ￥）、井；可带可不带，也允许写在数字前面（各¥50）
        $unitPattern = '[元块米闷斤刀¥￥井]';

        $numberPattern = '\d{1,3}(?:' . $separator . '\d{1,3})*';
        $itemPattern   = '(?:' . $numberPattern . '|' . $wordPattern . ')';
        $groupPattern  = '(?:' . $itemPattern . '|' . $separator . ')+';
        $pattern       = '/(' . $groupPattern . ')\s*(' . $keywords . ')\s*' . $unitPattern . '?\s*(\d+)\s*' . $unitPattern . '?/u';
        $itemRegex     = '/(\d+)|(' . $wordPattern . ')/u';
        $warnRegex     = '/\d|' . $wordPattern . '/u';

        // 全角数字转半角
        $input = strtr($input, [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
            '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        ]);

        // 岁数选号：门N / 门N岁 => 该岁数所属生肖的全部号码（先替换为占位词，避免 1岁 被数字链吞掉）
        // 必须带「门」字，裸写 7岁 保持旧行为（岁 按分隔符处理）
        $ageLabels = [];
        $input     = preg_replace_callback('/门\s*(\d{1,3})\s*岁?/u', function ($matches) use (&$ageLabels) {
            $age = (int)$matches[1];
            if (!isset(Liuconfig::$numToZodiac[$age])) {
                return $matches[0];
            }
            $token             = self::ageToken($age);
            $ageLabels[$token] = $matches[0];
            return $token;
        }, $input);

        $list     = [];
        $warnings = [];
        $offset   = 0;
        $length   = strlen($input);
        while ($offset < $length) {
            if (!preg_match($pattern, $input, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                $rest = substr($input, $offset);
                if (preg_match($warnRegex, $rest)) {
                    $warnings[] = '未匹配片段: ' . trim(strtr($rest, $ageLabels));
                }
                break;
            }

            // 两次匹配之间被跳过的内容，若含数字或文字选号说明有无法识别的片段
            $gap = substr($input, $offset, $matches[0][1] - $offset);
            if (preg_match($warnRegex, $gap)) {
                $warnings[] = '未匹配片段: ' . trim(strtr($gap, $ageLabels));
            }

            preg_match_all($itemRegex, $matches[1][0], $itemMatches, PREG_SET_ORDER);
            $amount  = (int)$matches[3][0];
            $codes   = [];
            $invalid = [];
            foreach ($itemMatches as $item) {
                if (isset($item[1]) && $item[1] !== '') {
                    // 数字号码，前导零归一（05 => 5）
                    $code = (int)$item[1];
                    // 号码必须在 1-49 之间，超范围的剔除并提示
                    if ($code < 1 || $code > 49) {
                        $invalid[] = $code;
                        continue;
                    }
                    $codes[] = $code;
                } else {
                    // 文字号码，查配置数组展开
                    $word = self::$wordAlias[$item[2]] ?? $item[2];
                    foreach ($wordMap[$word] as $code) {
                        $codes[] = $code;
                    }
                }
            }
            if ($invalid) {
                $warnings[] = '号码超出范围(1-49): ' . implode('、', array_unique($invalid));
            }
            // 组内去重，跨组同号码累加
            foreach (array_unique($codes) as $code) {
                $list[$code] = ($list[$code] ?? 0) + $amount;
            }

            $offset = $matches[0][1] + strlen($matches[0][0]);
        }
        ksort($list);

        return [
            'list'     => $list,
            'count'    => count($list),
            'quantity' => array_sum($list),
            'warnings' => $warnings,
        ];
    }

    /**
     * 结算流程（分两步，不迭代）：
     * 1. 第一步外发金额全部为 0，用用户下单金额算出各号码结算金额，据此得到外发金额 waifa
     *    waifa =（亏损额 - 风控金额）/ 倍数，向上取整；未超过风控金额为 0
     * 2. 实际下单金额 = 用户下单金额 - waifa；参数A = 全部 waifa 之和
     *    方括号金额改按实际下单金额计算：实际下单金额之和 × 系数 - 实际下单金额 × 倍数 - 参数A × 系数
     * 配置项位于 config/liu.php：coefficient（系数）、multiple（倍数）、risk_amount（风控金额）
     * @param array<int, int> $list 号码 => 下单金额
     * @param array|null      $config 结算参数，报表时传入会话冻结值，缺省读取 config/liu.php
     * @return array{items: array, lines: array, text: string, income: float, param_a: float}
     */
    public static function settleOrderCodes(array $list, ?array $config = null): array
    {
        $config   = $config ?: self::settleConfig();
        $multiple = (float)($config['multiple'] ?? 47);
        $coefficient = (float)($config['coefficient'] ?? 0.96);
        $riskAmount  = (float)($config['risk_amount'] ?? 1000);

        // 第一步：外发金额为 0，先用用户下单金额算出结算金额，再判断是否触发风控
        $income = array_sum($list) * $coefficient;
        $rows   = [];
        foreach ($list as $code => $amount) {
            $settled = round($income - $amount * $multiple, 2);
            // 风控：仅亏损且亏损额超过风控金额时产生外发金额
            $waifa = 0;
            if ($settled < 0 && abs($settled) > $riskAmount) {
                $waifa = (int)ceil((abs($settled) - $riskAmount) / $multiple);
            }
            $rows[$code] = [
                'amount' => $amount,
                'waifa'  => $waifa,
                'actual' => $amount - $waifa,
            ];
        }

        // 参数A = 全部外发金额之和；总收入与支出都改用实际下单金额
        $paramA    = array_sum(array_column($rows, 'waifa'));
        $income    = array_sum(array_column($rows, 'actual')) * $coefficient;
        $paramCost = $paramA * $coefficient;

        $items = [];
        $lines = [];
        foreach ($rows as $code => $row) {
            $settled = round($income - $row['actual'] * $multiple - $paramCost, 2);
            $zodiac  = Liuconfig::$numToZodiac[$code] ?? '';
            // 有外发时显示「用户下单金额/实际下单金额」
            $amountText = self::formatAmount($row['amount']);
            if ($row['waifa'] > 0) {
                $amountText .= '/' . self::formatAmount($row['actual']);
            }
            $line = $zodiac . $code . '=' . $amountText . ' 【' . self::formatAmount($settled) . '】';

            $items[] = [
                'code'    => $code,
                'zodiac'  => $zodiac,
                'amount'  => $row['amount'],
                'waifa'   => $row['waifa'],
                'actual'  => $row['actual'],
                'settled' => $settled,
                'line'    => $line,
            ];
            $lines[] = $line;
        }

        return [
            'items'   => $items,
            'lines'   => $lines,
            'text'    => implode("\n", $lines),
            'income'  => round($income, 2),
            'param_a' => round($paramA, 2),
        ];
    }

    /**
     * 结算配置：系数、倍数、风控金额（缺省值与 config/liu.php 保持一致）
     * @return array{coefficient: float, multiple: float, risk_amount: float}
     */
    private static function settleConfig(): array
    {
        $config = (array)Config::get('liu', []);

        return [
            'coefficient' => isset($config['coefficient']) ? (float)$config['coefficient'] : 0.96,
            'multiple'    => isset($config['multiple']) ? (float)$config['multiple'] : 47,
            'risk_amount' => isset($config['risk_amount']) ? (float)$config['risk_amount'] : 1000,
        ];
    }

    /**
     * 金额格式化：保留两位小数，去掉多余的 0（369.6 => 369.6，150.00 => 150）
     */
    public static function formatAmount(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
