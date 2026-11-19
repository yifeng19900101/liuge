<?php

namespace app\admin\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * User 模型
 * @property int    $id          用户ID
 * @property string password     密码密文
 * @property float  $coefficient 系数（后台配置比例，如 0.04，实际结算系数 = 1 - 系数）
 * @property float  $multiple    倍数
 */
class User extends Model
{
    protected $autoWriteTimestamp = true;

    public function getAvatarAttr($value): string
    {
        return full_url($value, false, config('buildadmin.default_avatar'));
    }

    public function setAvatarAttr($value): string
    {
        return $value == full_url('', false, config('buildadmin.default_avatar')) ? '' : $value;
    }

    /**
     * 系数：后台填写比例（0 < 系数 < 1），非法值回落默认 0.04
     */
    public function getCoefficientAttr($value): float
    {
        return (float)$value;
    }

    public function setCoefficientAttr($value): string
    {
        $value = (float)$value;
        if ($value <= 0 || $value >= 1) {
            $value = 0.04;
        }
        return number_format($value, 4, '.', '');
    }

    /**
     * 倍数：必须大于 0，非法值回落默认 47
     */
    public function getMultipleAttr($value): float
    {
        return (float)$value;
    }

    public function setMultipleAttr($value): string
    {
        $value = (float)$value;
        if ($value <= 0) {
            $value = 47;
        }
        return number_format($value, 2, '.', '');
    }

    public function getMoneyAttr($value): string
    {
        return bcdiv($value, 100, 2);
    }

    public function setMoneyAttr($value): string
    {
        return bcmul($value, 100, 2);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    /**
     * 重置用户密码
     * @param int|string $uid         用户ID
     * @param string     $newPassword 新密码
     * @return int|User
     */
    public function resetPassword(int|string $uid, string $newPassword): int|User
    {
        return $this->where(['id' => $uid])->update(['password' => hash_password($newPassword), 'salt' => '']);
    }
}