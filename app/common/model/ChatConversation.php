<?php

namespace app\common\model;

use think\Model;

/**
 * 会员聊天会话模型
 * @property int    $id           会话ID
 * @property int    $user_id      会员ID
 * @property string $session_date 会话日期
 * @property string $coefficient  系数（会话冻结）
 * @property string $multiple     倍数（会话冻结）
 * @property string $risk_amount  风控金额（会话冻结）
 * @property string $last_message 最后一条消息
 * @property int    $last_time    最后消息时间
 */
class ChatConversation extends Model
{
    protected $autoWriteTimestamp = true;
}
