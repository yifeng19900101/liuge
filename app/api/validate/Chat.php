<?php

namespace app\api\validate;

use think\Validate;

class Chat extends Validate
{
    protected $failException = true;

    protected $rule = [
        'content' => 'require|max:2000',
    ];

    protected $message = [
        'content.require' => '请输入聊天内容',
        'content.max'     => '聊天内容最多 2000 个字符',
    ];
}
