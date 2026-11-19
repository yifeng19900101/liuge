<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 会员结算参数：倍数、系数
 * 系数为后台配置比例（如 0.04 表示 4%），实际结算系数 = 1 - 系数
 */
class UserSettleConfig extends Migrator
{
    public function up(): void
    {
        if (!$this->hasTable('user')) return;

        $table   = $this->table('user');
        $changed = false;

        if (!$table->hasColumn('coefficient')) {
            $table->addColumn('coefficient', 'decimal', [
                'precision' => 5,
                'scale'     => 4,
                'default'   => 0.0400,
                'null'      => false,
                'comment'   => '系数（后台配置比例，结算系数 = 1 - 系数）',
            ]);
            $changed = true;
        }

        if (!$table->hasColumn('multiple')) {
            $table->addColumn('multiple', 'decimal', [
                'precision' => 10,
                'scale'     => 2,
                'default'   => 47.00,
                'null'      => false,
                'comment'   => '倍数',
            ]);
            $changed = true;
        }

        if ($changed) {
            $table->save();
        }

        // 历史会员补齐默认值
        Db::name('user')->where('coefficient', 0)->update(['coefficient' => 0.0400]);
        Db::name('user')->where('multiple', 0)->update(['multiple' => 47.00]);
    }

    public function down(): void
    {
        if (!$this->hasTable('user')) return;

        $table = $this->table('user');
        foreach (['coefficient', 'multiple'] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        $table->save();
    }
}
