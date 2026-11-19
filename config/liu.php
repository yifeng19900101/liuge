<?php

// 号码下单结算与聊天相关配置
return [
    // 系数：总收入 = 实际下单金额之和 × 系数；参数A支出 = 参数A × 系数
    // 后台会员配置的「系数」是比例（默认 0.04），建会话时冻结为 1 - 0.04 = 0.96，此处的全局值仅作兜底
    'coefficient' => 0.96,
    // 倍数：本号码支出 = 实际下单金额 × 倍数（会员未配置时使用此全局默认值）
    'multiple'    => 47,
    // 风控金额：单个号码亏损超过该金额时，外发金额 =（亏损额 - 风控金额）/ 倍数，向上取整；未超过时为 0
    // 参数A 由全部外发金额求和得到，不再单独配置
    'risk_amount' => 1000,

    // 会员端接口签名
    'api_sign'    => [
        // 签名密钥，需与前端的 VITE_API_SIGN_SECRET 保持一致
        'secret'  => (string)env('api_sign.secret', ''),
        // 时间戳允许的最大偏差（秒）
        'ttl'     => (int)env('api_sign.ttl', 300),
        // 无需签名的接口（后缀匹配）
        'exclude' => [
            'install/index',
            'install/check',
            'install/run',
            'common/captcha',
            'ajax/buildSuffixSvg',
            'ajax/upload',
        ],
    ],

    // 报表与时间限制
    'report'      => [
        // 该时间之后只允许发送报表指令
        'cutoff'  => (string)env('report.cutoff', '21:15:00'),
        // 报表指令（需完全相同）
        'command' => (string)env('report.command', '报表'),
    ],

    // 管理员风控指令
    'risk'        => [
        // 指令前缀，完整格式如：风控6000
        'command' => '风控',
    ],
];
