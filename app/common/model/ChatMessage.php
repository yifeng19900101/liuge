<?php

namespace app\common\model;

use think\Model;

/**
 * 会员聊天消息模型
 * @property int    $id              消息ID
 * @property int    $user_id         会员ID
 * @property int    $conversation_id 会话ID
 * @property string $role            角色:user=用户,bot=机器人,admin=管理员
 * @property int    $admin_id        管理员ID（管理员消息）
 * @property string $content         消息内容
 * @property int    $create_time     创建时间
 */
class ChatMessage extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime         = false;
}
