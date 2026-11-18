<?php

namespace app\admin\model\chat;

use think\Model;
use think\model\relation\BelongsTo;
use app\admin\model\User;

/**
 * 会员下单模型（后台）
 * @property int    $id              下单ID
 * @property int    $user_id         会员ID
 * @property int    $conversation_id 会话ID
 * @property string $items           号码金额明细JSON
 * @property int    $item_count      号码数量
 * @property string $total_amount    下单总金额
 * @property int    $create_time     创建时间
 */
class Order extends Model
{
    protected $name = 'chat_order';

    protected $autoWriteTimestamp = true;
    protected $updateTime         = false;

    public function user(): BelongsTo
    {
        // 只取展示需要的字段，避免关联返回密码等敏感信息
        return $this->belongsTo(User::class, 'user_id')->field(['id', 'username', 'nickname', 'avatar']);
    }
}
