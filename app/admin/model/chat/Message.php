<?php

namespace app\admin\model\chat;

use think\Model;
use think\model\relation\BelongsTo;
use app\admin\model\User;

/**
 * 聊天记录模型（后台）
 * @property int    $id              消息ID
 * @property int    $user_id         会员ID
 * @property int    $conversation_id 会话ID
 * @property string $role            角色:user=用户,bot=机器人,admin=管理员
 * @property int    $admin_id        管理员ID
 * @property string $content         消息内容
 * @property int    $create_time     创建时间
 */
class Message extends Model
{
    protected $name = 'chat_message';

    protected $autoWriteTimestamp = true;
    protected $updateTime         = false;

    public function user(): BelongsTo
    {
        // 只取展示需要的字段，避免关联返回密码等敏感信息
        return $this->belongsTo(User::class, 'user_id')->field(['id', 'username', 'nickname', 'avatar']);
    }
}
