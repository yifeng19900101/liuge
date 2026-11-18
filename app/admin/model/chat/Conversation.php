<?php

namespace app\admin\model\chat;

use think\Model;
use think\model\relation\BelongsTo;
use app\admin\model\User;

/**
 * 聊天会话模型（后台）
 * @property int    $id           会话ID
 * @property int    $user_id      会员ID
 * @property string $session_date 会话日期
 * @property string $coefficient  系数
 * @property string $multiple     倍数
 * @property string $risk_amount  风控金额
 * @property int    $is_risk      是否已风控
 * @property int    $risk_time    风控时间
 * @property int    $risk_admin_id 风控操作管理员ID
 * @property string $last_message 最后一条消息
 * @property int    $last_time    最后消息时间
 */
class Conversation extends Model
{
    protected $name = 'chat_conversation';

    protected $autoWriteTimestamp = true;

    public function user(): BelongsTo
    {
        // 只取展示需要的字段，避免关联返回密码等敏感信息
        return $this->belongsTo(User::class, 'user_id')->field(['id', 'username', 'nickname', 'avatar']);
    }
}
