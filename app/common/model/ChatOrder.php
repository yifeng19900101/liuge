<?php

namespace app\common\model;

use think\Model;

/**
 * 会员下单模型
 * @property int    $id              下单ID
 * @property int    $user_id         会员ID
 * @property int    $conversation_id 会话ID
 * @property string $items           号码金额明细JSON，如 {"4":50}
 * @property int    $item_count      号码数量
 * @property string $total_amount    下单总金额
 * @property int    $create_time     创建时间
 */
class ChatOrder extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime         = false;
}
